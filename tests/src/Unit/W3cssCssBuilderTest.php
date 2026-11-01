<?php

declare(strict_types=1);

namespace Drupal\Tests\w3css_paragraphs\Unit;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\w3css_paragraphs\Service\Css\W3cssCssBuilder;
use Drupal\w3css_paragraphs\Service\Css\W3cssCssValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Stored values through to generated CSS.
 */
#[CoversClass(W3cssCssBuilder::class)]
#[Group('w3css_paragraphs')]
final class W3cssCssBuilderTest extends TestCase {

  private const UUID = '123e4567-e89b-12d3-a456-426614174000';

  /**
   * Builds the builder under test.
   *
   * @param list<string>|null $shown
   *   The components of the view display, or NULL for no display at all.
   * @param \Psr\Log\LoggerInterface|null $logger
   *   The logger.
   * @param array<string, list<string>> $byMode
   *   Components of other enabled view modes.
   */
  private function builder(?array $shown = NULL, ?LoggerInterface $logger = NULL, array $byMode = []): W3cssCssBuilder {
    $storage = $this->createStub(ConfigEntityStorageInterface::class);
    $storage->method('load')->willReturnCallback(function (string $id) use ($shown, $byMode) {
      $mode = substr((string) strrchr($id, '.'), 1);
      if ($mode === 'default') {
        $components = $shown;
      }
      else {
        $components = $byMode[$mode] ?? NULL;
      }
      if ($components === NULL) {
        return NULL;
      }
      $display = $this->createStub(EntityViewDisplayInterface::class);
      $display->method('status')->willReturn(TRUE);
      $display->method('getComponent')->willReturnCallback(
        static fn (string $field): ?array => in_array($field, $components, TRUE) ? ['type' => 'list_key'] : NULL,
      );
      return $display;
    });
    $manager = $this->createStub(EntityTypeManagerInterface::class);
    $manager->method('getStorage')->willReturn($storage);
    return new W3cssCssBuilder(new W3cssCssValidator(), $manager, $logger ?? $this->createStub(LoggerInterface::class));
  }

  /**
   * Builds an entity.
   *
   * @param string $type
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param mixed $id
   *   The entity id.
   * @param array<string, mixed> $values
   *   The field values.
   */
  private function entity(string $type, string $bundle, mixed $id, array $values): FieldableEntityInterface {
    $entity = $this->createStub(FieldableEntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn($type);
    $entity->method('bundle')->willReturn($bundle);
    $entity->method('id')->willReturn($id);
    $entity->method('uuid')->willReturn(self::UUID);
    $entity->method('hasField')->willReturnCallback(static fn (string $field): bool => array_key_exists($field, $values));
    $entity->method('get')->willReturnCallback(function (string $field) use ($values) {
      $item = $this->createStub(FieldItemInterface::class);
      $item->method('getValue')->willReturn(['value' => $values[$field]]);
      $list = $this->createStub(FieldItemListInterface::class);
      $list->method('isEmpty')->willReturn($values[$field] === NULL || $values[$field] === '');
      $list->method('first')->willReturn($item);
      return $list;
    });
    return $entity;
  }

  /**
   * Tests the color rule on the outer element, for a bundle with no map entry.
   */
  public function testColorOnSiteOwnBundle(): void {
    $values = ['w3css_display_bg_color' => 'w3-244-67-54', 'w3css_display_opacity' => '50'];
    $css = $this->builder(['w3css_display_bg_color', 'w3css_display_opacity'])->build(
      $this->entity('paragraph', 'site_own', 42, $values),
    );
    $this->assertSame("#w3css-paragraph-site-own-42{background-color:rgba(244,67,54,0.50);}\n", $css);
  }

  /**
   * Tests that every shipped color and opacity equals the 1.x Twig arithmetic.
   *
   * @dataProvider shippedColorStates
   */
  #[DataProvider('shippedColorStates')]
  public function testEqualsOldTwigArithmetic(string $color, ?string $opacity): void {
    $values = ['w3css_display_bg_color' => $color, 'w3css_display_opacity' => $opacity];
    $css = $this->builder(['w3css_display_bg_color', 'w3css_display_opacity'])->build(
      $this->entity('paragraph', 'w3css_simple', 1, $values),
    );
    // Same steps as templates/paragraph--default.html.twig:61-68. The '100' of
    // no opacity is clamped to fully opaque by browsers, which is our 1.
    $old = str_replace('-', ',', substr($color, 3)) . ',' . ($opacity === NULL ? '1' : '0.' . $opacity);
    $this->assertSame('#w3css-paragraph-w3css-simple-1{background-color:rgba(' . $old . ");}\n", $css);
  }

  /**
   * Gives each stored color and opacity state, read from the shipped config.
   *
   * @return \Generator<string, array{string, string|null}>
   *   Color and opacity.
   */
  public static function shippedColorStates(): \Generator {
    $config = dirname(__DIR__, 3) . '/config/optional/';
    $colors = Yaml::decode((string) file_get_contents($config . 'field.storage.paragraph.w3css_display_bg_color.yml'))['settings']['allowed_values'];
    $opacities = Yaml::decode((string) file_get_contents($config . 'field.storage.paragraph.w3css_display_opacity.yml'))['settings']['allowed_values'];
    // Guards the provider itself: an empty list would pass with no assertion.
    assert(count($colors) === 30 && count($opacities) === 11);
    foreach ($colors as $color) {
      yield $color['value'] . ' none' => [$color['value'], NULL];
      foreach ($opacities as $opacity) {
        yield $color['value'] . ' ' . $opacity['value'] => [$color['value'], $opacity['value']];
      }
    }
  }

  /**
   * Tests hostile stored values: nothing emitted, and the value is not logged.
   *
   * @dataProvider hostileStoredValues
   */
  #[DataProvider('hostileStoredValues')]
  public function testHostileValuesEmitNothing(string $type, string $bundle, array $values, string $canary): void {
    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())->method('warning')->with(
      $this->stringContains('Rejected'),
      $this->callback(fn (array $context): bool => !str_contains(serialize($context), $canary)),
    );
    $shown = [
      'w3css_display_bg_color',
      'w3css_display_opacity',
      'w3css_display_3d_height',
    ];
    $css = $this->builder($shown, $logger)->build($this->entity($type, $bundle, 9, $values));
    $this->assertSame('', $css);
  }

  /**
   * Gives hostile stored values.
   *
   * @return array<string, array{string, string, array<string, mixed>, string}>
   *   Entity type, bundle, values and a string that must not reach the log.
   */
  public static function hostileStoredValues(): array {
    $color = 'w3css_display_bg_color';
    $opacity = 'w3css_display_opacity';
    $height = 'w3css_display_3d_height';
    $node = 'w3css_paragraphs_content';
    return [
      'color' => ['paragraph', 'w3css_simple', [$color => 'w3-1-2-3;}x{', $opacity => '50'], 'x{'],
      'color with tag' => ['paragraph', 'w3css_simple', [$color => '</style><script>', $opacity => NULL], 'script'],
      'opacity' => ['paragraph', 'w3css_simple', [$color => 'w3-1-2-3', $opacity => '50);}x{'], 'x{'],
      'flip box height' => ['paragraph', 'w3css_3d_flip_box', [$height => '5px;}x{'], 'x{'],
      'flip box over the limit' => ['paragraph', 'w3css_3d_flip_box', [$height => 10001], '10001'],
      'node color' => ['node', $node, ['w3css_paragraphs_node_bg' => 'w3-1-2-3}x{'], 'x{'],
      'node opacity' => [
        'node',
        $node,
        ['w3css_paragraphs_node_bg' => 'w3-1-2-3', 'w3css_paragraphs_node_bgopacity' => 'x{'],
        'x{',
      ],
      'node max width' => ['node', $node, ['w3css_paragraphs_max_width' => '1;}x{'], 'x{'],
    ];
  }

  /**
   * Tests that one bad value does not stop a good one on the same entity.
   */
  public function testGoodValueSurvivesBadOne(): void {
    $css = $this->builder([])->build($this->entity('node', 'w3css_paragraphs_content', 3, [
      'w3css_paragraphs_node_bg' => 'bad}',
      'w3css_paragraphs_max_width' => 900,
    ]));
    $this->assertSame("#w3css-node-w3css-paragraphs-content-3--article{max-width:900px;}\n", $css);
  }

  /**
   * Tests that a hostile bundle or id never reaches a selector.
   *
   * @dataProvider hostileIdentifiers
   */
  #[DataProvider('hostileIdentifiers')]
  public function testHostileIdentifiers(string $bundle, mixed $id): void {
    $builder = $this->builder(['w3css_display_bg_color']);
    $entity = $this->entity('paragraph', $bundle, $id, ['w3css_display_bg_color' => 'w3-1-2-3']);
    $this->assertSame('', $builder->build($entity));
    $this->assertNull($builder->elementId($entity));
    $this->assertNull($builder->storageKey($entity));
  }

  /**
   * Gives hostile bundles and ids.
   *
   * @return array<string, array{string, mixed}>
   *   Bundle and id.
   */
  public static function hostileIdentifiers(): array {
    return [
      'bundle selector' => ['a{}b', 1],
      'bundle traversal' => ['../../x', 1],
      'bundle tag' => ['</style>', 1],
      'id selector' => ['w3css_simple', '1{}'],
      'id traversal' => ['w3css_simple', '../1'],
      'id zero' => ['w3css_simple', 0],
    ];
  }

  /**
   * Tests that an unsupported entity type gets nothing.
   */
  public function testUnsupportedEntityType(): void {
    $builder = $this->builder(['w3css_display_bg_color']);
    $entity = $this->entity('user', 'user', 1, ['w3css_display_bg_color' => 'w3-1-2-3']);
    $this->assertSame('', $builder->build($entity));
    $this->assertNull($builder->elementId($entity));
    $this->assertNull($builder->storageKey($entity));
  }

  /**
   * Tests that a color hidden in the view display stays hidden.
   */
  public function testHiddenFieldEmitsNothing(): void {
    $entity = $this->entity('paragraph', 'w3css_simple', 1, ['w3css_display_bg_color' => 'w3-1-2-3']);
    $this->assertSame('', $this->builder([])->build($entity));
    // No view display at all shows no field.
    $this->assertSame('', $this->builder(NULL)->build($entity));
  }

  /**
   * Tests that a hidden opacity counts as absent, as it does in 1.x.
   */
  public function testHiddenOpacityIsAbsent(): void {
    $values = ['w3css_display_bg_color' => 'w3-1-2-3', 'w3css_display_opacity' => '50'];
    $css = $this->builder(['w3css_display_bg_color'])->build(
      $this->entity('paragraph', 'w3css_simple', 1, $values),
    );
    $this->assertStringContainsString('rgba(1,2,3,1)', $css);
  }

  /**
   * Tests the view mode: its own display, the default one, or a bad name.
   */
  public function testViewModes(): void {
    $entity = $this->entity('paragraph', 'w3css_simple', 1, ['w3css_display_bg_color' => 'w3-1-2-3']);
    $builder = $this->builder(['w3css_display_bg_color'], NULL, ['teaser' => []]);
    $this->assertSame('', $builder->build($entity, 'teaser'));
    $this->assertNotSame('', $builder->build($entity, 'no_such_mode'));
    $this->assertNotSame('', $builder->build($entity, 'bad.mode'));
  }

  /**
   * Tests the bundles whose values go on a second element.
   */
  public function testSecondElements(): void {
    $shown = ['w3css_display_bg_color', 'w3css_display_3d_height', 'w3css_display_image_overlay'];
    $color = ['w3css_display_bg_color' => 'w3-1-2-3'];

    $flip = $this->builder($shown)->build($this->entity('paragraph', 'w3css_3d_flip_box', 5, $color + ['w3css_display_3d_height' => 400]));
    $this->assertSame("#w3css-paragraph-w3css-3d-flip-box-5{background-color:rgba(1,2,3,1);}\n#w3css-paragraph-w3css-3d-flip-box-5--box{height:400px;}\n", $flip);

    $parallax = $this->builder($shown)->build($this->entity('paragraph', 'w3css_parallax_section', 5, $color));
    $this->assertSame("#w3css-paragraph-w3css-parallax-section-5--text{background-color:rgba(1,2,3,1);}\n", $parallax);

    $fade = $this->builder($shown)->build($this->entity('paragraph', 'w3css_image_overlay', 5, $color + ['w3css_display_image_overlay' => 'fade']));
    $this->assertSame("#w3css-paragraph-w3css-image-overlay-5--overlay{background-color:rgba(1,2,3,1);}\n", $fade);

    $title = $this->builder($shown)->build($this->entity('paragraph', 'w3css_image_overlay', 5, $color + ['w3css_display_image_overlay' => 'title']));
    $this->assertSame("#w3css-paragraph-w3css-image-overlay-5--overlay{background-color:rgba(1,2,3,1);}\n#w3css-paragraph-w3css-image-overlay-5--title{background-color:rgba(1,2,3,1);}\n", $title);
  }

  /**
   * Tests a node: read from the entity, and only the module's own bundle.
   */
  public function testNodes(): void {
    $values = [
      'w3css_paragraphs_node_bg' => 'w3-1-2-3',
      'w3css_paragraphs_node_bgopacity' => '95',
      'w3css_paragraphs_max_width' => '900',
    ];
    $css = $this->builder(NULL)->build($this->entity('node', 'w3css_paragraphs_content', 7, $values));
    $this->assertSame("#w3css-node-w3css-paragraphs-content-7{background-color:rgba(1,2,3,0.95);}\n#w3css-node-w3css-paragraphs-content-7--article{max-width:900px;}\n", $css);
    $this->assertSame('', $this->builder(NULL)->build($this->entity('node', 'article', 7, $values)));
  }

  /**
   * Tests an unsaved paragraph: a UUID-based id and no storage key.
   */
  public function testUnsavedParagraph(): void {
    $builder = $this->builder(['w3css_display_bg_color']);
    $entity = $this->entity('paragraph', 'w3css_simple', NULL, ['w3css_display_bg_color' => 'w3-1-2-3']);
    $this->assertNull($builder->storageKey($entity));
    $this->assertSame('w3css-paragraph-w3css-simple-u123e4567e89b12d3a456426614174000', $builder->elementId($entity));
    $this->assertStringContainsString('#w3css-paragraph-w3css-simple-u123e4567e89b12d3a456426614174000{', $builder->build($entity));
  }

}
