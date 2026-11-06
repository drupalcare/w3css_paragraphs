<?php

declare(strict_types=1);

namespace Drupal\Tests\w3css_paragraphs\Unit;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\State\StateInterface;
use Drupal\w3css_paragraphs\Hook\W3cssDynamicCssHooks;
use Drupal\w3css_paragraphs\Hook\W3cssPreprocessHooks;
use Drupal\w3css_paragraphs\Service\Css\W3cssCssBuilderInterface;
use Drupal\w3css_paragraphs\Service\Css\W3cssCssFileManagerInterface;
use Drupal\w3css_paragraphs\Service\Css\W3cssCssValidator;
use Drupal\w3css_paragraphs\Service\Css\W3cssDynamicLibraryRegistrarInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The default-revision rule, the hash rule and the sink guard.
 */
#[AllowMockObjectsWithoutExpectations]
#[CoversClass(W3cssDynamicCssHooks::class)]
#[Group('w3css_paragraphs')]
final class W3cssDynamicCssHooksTest extends TestCase {

  private const KEY = 'paragraph-w3css-simple-42';

  private const ELEMENT = 'w3css-paragraph-w3css-simple-42';

  private const CSS = "#w3css-paragraph-w3css-simple-42{height:1px;}\n";

  /**
   * The file manager.
   */
  private W3cssCssFileManagerInterface&MockObject $files;

  /**
   * The cache tag invalidator.
   */
  private CacheTagsInvalidatorInterface&MockObject $invalidator;

  /**
   * The logger.
   */
  private LoggerInterface&MockObject $logger;

  /**
   * The state store.
   */
  private StateInterface&MockObject $state;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->files = $this->createMock(W3cssCssFileManagerInterface::class);
    $this->invalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->state = $this->createMock(StateInterface::class);
    if (!defined('CSS_AGGREGATE_THEME')) {
      define('CSS_AGGREGATE_THEME', 100);
    }
  }

  /**
   * Builds the hooks class around fixed builder answers.
   */
  private function hooks(string $css = self::CSS, ?string $key = self::KEY, ?string $elementId = self::ELEMENT): W3cssDynamicCssHooks {
    $builder = $this->createStub(W3cssCssBuilderInterface::class);
    $builder->method('build')->willReturn($css);
    $builder->method('storageKey')->willReturn($key);
    $builder->method('elementId')->willReturn($elementId);
    $registrar = $this->createStub(W3cssDynamicLibraryRegistrarInterface::class);
    $registrar->method('libraryId')->willReturnCallback(static fn (string $k): string => 'w3css_paragraphs/dynamic--' . $k);
    return new W3cssDynamicCssHooks($this->files, $builder, new W3cssCssValidator(), $registrar, $this->invalidator, $this->logger, $this->state);
  }

  /**
   * Builds a saved entity.
   */
  private function entity(bool $defaultRevision = TRUE, string $bundle = 'w3css_simple'): ContentEntityInterface&MockObject {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('bundle')->willReturn($bundle);
    $entity->method('isDefaultRevision')->willReturn($defaultRevision);
    $entity->method('getUntranslated')->willReturnSelf();
    return $entity;
  }

  /**
   * Tests that a draft revision does not replace the published file.
   */
  public function testDraftRevisionWritesNothing(): void {
    $this->files->expects($this->never())->method('write');
    $this->files->expects($this->never())->method('delete');
    $this->invalidator->expects($this->never())->method('invalidateTags');
    $this->hooks()->paragraphUpdate($this->entity(FALSE));
    $this->hooks()->paragraphInsert($this->entity(FALSE));
    $this->hooks()->nodeUpdate($this->entity(FALSE));
    $this->hooks()->nodeInsert($this->entity(FALSE));
  }

  /**
   * Tests that the default revision writes, and a changed file invalidates.
   */
  public function testDefaultRevisionWrites(): void {
    $this->files->method('fileUri')->willReturnOnConsecutiveCalls('', 'public://w3css_paragraphs/new.css');
    $this->files->expects($this->once())->method('write')->with(self::KEY, self::CSS)->willReturn(TRUE);
    $this->invalidator->expects($this->once())->method('invalidateTags')->with(['library_info']);
    $this->hooks()->paragraphUpdate($this->entity());
  }

  /**
   * Tests that a save with the same CSS does not invalidate anything.
   */
  public function testUnchangedFileDoesNotInvalidate(): void {
    $this->files->method('fileUri')->willReturn('public://w3css_paragraphs/same.css');
    $this->files->expects($this->once())->method('write');
    $this->invalidator->expects($this->never())->method('invalidateTags');
    $this->hooks()->paragraphUpdate($this->entity());
  }

  /**
   * Tests that the file is built from the default translation.
   */
  public function testBuildsFromTheDefaultTranslation(): void {
    $original = $this->entity();
    $translation = $this->createMock(ContentEntityInterface::class);
    $translation->method('isDefaultRevision')->willReturn(TRUE);
    $translation->expects($this->once())->method('getUntranslated')->willReturn($original);

    $builder = $this->createMock(W3cssCssBuilderInterface::class);
    $builder->method('storageKey')->willReturn(self::KEY);
    $builder->expects($this->once())->method('build')->with($this->identicalTo($original))->willReturn(self::CSS);
    $hooks = new W3cssDynamicCssHooks($this->files, $builder, new W3cssCssValidator(), $this->createStub(W3cssDynamicLibraryRegistrarInterface::class), $this->invalidator, $this->logger, $this->state);
    $hooks->paragraphUpdate($translation);
  }

  /**
   * Tests that a value removed from an entity removes its file.
   */
  public function testEmptyCssRemovesTheFile(): void {
    $this->files->method('fileUri')->willReturn('public://w3css_paragraphs/old.css');
    $this->files->expects($this->never())->method('write');
    $this->files->expects($this->once())->method('delete')->with(self::KEY);
    $this->invalidator->expects($this->once())->method('invalidateTags')->with(['library_info']);
    $this->hooks('')->paragraphUpdate($this->entity());
  }

  /**
   * Tests that an unsaved or invalid entity writes nothing.
   */
  public function testNoKeyWritesNothing(): void {
    $this->files->expects($this->never())->method('write');
    $this->hooks(self::CSS, NULL)->paragraphInsert($this->entity());
  }

  /**
   * Tests that an entity that is not fieldable is ignored.
   */
  public function testOtherEntitiesAreIgnored(): void {
    $this->files->expects($this->never())->method('write');
    $this->files->expects($this->never())->method('delete');
    $entity = $this->createStub(EntityInterface::class);
    $this->hooks()->paragraphInsert($entity);
    $this->hooks()->paragraphDelete($entity);
  }

  /**
   * Tests the sink guard on the file path.
   */
  public function testAngleBracketIsNeverWritten(): void {
    $this->files->expects($this->never())->method('write');
    $this->logger->expects($this->once())->method('error');
    $this->hooks('#a{}</style><script>alert(1)</script>')->paragraphUpdate($this->entity());
  }

  /**
   * Tests that deleting an entity deletes its file.
   */
  public function testDelete(): void {
    $this->files->method('fileUri')->willReturn('public://w3css_paragraphs/x.css');
    $this->files->expects($this->exactly(2))->method('delete')->with(self::KEY);
    $this->invalidator->expects($this->exactly(2))->method('invalidateTags')->with(['library_info']);
    $this->hooks()->paragraphDelete($this->entity());
    $this->hooks()->nodeDelete($this->entity());
  }

  /**
   * Tests that deleting an entity with no file invalidates nothing.
   */
  public function testDeleteWithNoFile(): void {
    $this->files->method('fileUri')->willReturn('');
    $this->files->expects($this->never())->method('delete');
    $this->invalidator->expects($this->never())->method('invalidateTags');
    $this->hooks()->paragraphDelete($this->entity());
  }

  /**
   * Tests attaching: the library is used when the hash matches.
   */
  public function testAttachUsesLibraryOnMatch(): void {
    $this->files->method('matches')->with(self::KEY, self::CSS)->willReturn(TRUE);
    $variables = [];
    $this->hooks()->attachDynamicCss($variables, $this->entity());
    $this->assertSame(['w3css_paragraphs/dynamic--' . self::KEY], $variables['#attached']['library']);
    $this->assertArrayNotHasKey('html_head', $variables['#attached']);
    $this->assertSame(self::ELEMENT, $variables['w3css_element_id']);
    $this->assertSame(self::ELEMENT, $variables['attributes']['id']);
  }

  /**
   * Tests preprocess: any bundle with CSS gets it, one without is left alone.
   *
   * The list of migrated bundles is gone: the builder decides by field, so a
   * site's own bundle is handled like a shipped one.
   */
  public function testPreprocessFollowsTheBuilder(): void {
    $this->files->method('matches')->willReturn(TRUE);
    $variables = ['paragraph' => $this->entity(TRUE, 'site_own')];
    $this->hooks()->preprocessParagraph($variables);
    $this->assertSame(['w3css_paragraphs/dynamic--' . self::KEY], $variables['#attached']['library']);
    $this->assertSame(self::ELEMENT, $variables['attributes']['id']);

    $variables = ['paragraph' => $this->entity(TRUE, 'w3css_3d_flip_box')];
    $this->hooks('')->preprocessParagraph($variables);
    $this->assertSame(['paragraph', 'w3css_element_id'], array_keys($variables));
    $this->assertNull($variables['w3css_element_id']);

    $variables = [];
    $this->hooks()->preprocessParagraph($variables);
    $this->assertSame([], $variables);
  }

  /**
   * Tests the core floor against what the code relies on.
   *
   * The file manager uses FileExists (10.3). The module ships no procedural
   * hook stubs: attribute hooks fire on their own from 11.1, so the floor must
   * stay at or above it, or no hook in this module runs.
   */
  public function testCoreFloorCoversTheCode(): void {
    $root = dirname(__DIR__, 3);
    $info = Yaml::decode((string) file_get_contents($root . '/w3css_paragraphs.info.yml'));
    $composer = json_decode((string) file_get_contents($root . '/composer.json'), TRUE);
    // CI replaces the root composer.json with its own project file; compare
    // only when the file is this module's.
    if (($composer['name'] ?? '') === 'drupal/w3css_paragraphs') {
      $this->assertSame($info['core_version_requirement'], $composer['require']['drupal/core']);
    }
    $this->assertSame(1, preg_match('/^\^(\d+\.\d+) /', $info['core_version_requirement'], $floor));
    $this->assertTrue(version_compare($floor[1], '11.1', '>='), $info['core_version_requirement']);
  }

  /**
   * Tests attaching: a draft, preview or stale file uses the page head.
   */
  public function testAttachFallsBackOnMismatch(): void {
    $this->files->method('matches')->willReturn(FALSE);
    $variables = [];
    $this->hooks()->attachDynamicCss($variables, $this->entity());
    $this->assertArrayNotHasKey('library', $variables['#attached']);
    [$element, $id] = $variables['#attached']['html_head'][0];
    $this->assertSame('style', $element['#tag']);
    $this->assertSame(self::CSS, (string) $element['#value']);
    $this->assertSame('w3css-dynamic-' . self::ELEMENT, $id);
  }

  /**
   * Tests attaching an unsaved paragraph: inline, with no file lookup.
   */
  public function testAttachUnsavedUsesThePageHead(): void {
    $this->files->expects($this->never())->method('matches');
    $variables = [];
    $this->hooks(self::CSS, NULL, 'w3css-paragraph-a-uabc')->attachDynamicCss($variables, $this->entity());
    $this->assertArrayNotHasKey('library', $variables['#attached']);
    $this->assertSame('w3css-paragraph-a-uabc', $variables['w3css_element_id']);
    $this->assertCount(1, $variables['#attached']['html_head']);
  }

  /**
   * Tests the sink guard on the page head.
   */
  public function testAngleBracketIsNeverPrinted(): void {
    $this->files->method('matches')->willReturn(FALSE);
    $this->logger->expects($this->once())->method('error');
    $variables = [];
    $this->hooks('#a{}</style><script>alert(1)</script>')->attachDynamicCss($variables, $this->entity());
    $this->assertArrayNotHasKey('#attached', $variables);
    $this->assertNull($variables['w3css_element_id']);
    $this->assertArrayNotHasKey('attributes', $variables);
  }

  /**
   * Tests attaching with no CSS or no valid id.
   */
  public function testAttachWithNothingToDo(): void {
    $variables = [];
    $this->hooks('')->attachDynamicCss($variables, $this->entity());
    $this->assertNull($variables['w3css_element_id']);
    $this->assertArrayNotHasKey('attributes', $variables);
    $this->assertArrayNotHasKey('#attached', $variables);

    $variables = [];
    $this->hooks(self::CSS, NULL, NULL)->attachDynamicCss($variables, $this->entity());
    $this->assertNull($variables['w3css_element_id']);
    $this->assertArrayNotHasKey('#attached', $variables);
  }

  /**
   * Tests that the CSS order hook moves only files in our directory.
   */
  public function testCssAlterMatchesOurDirectoryOnly(): void {
    $ours = 'public://w3css_paragraphs/paragraph-a-1.0123456789.css';
    $css = [
      $ours => ['data' => $ours, 'group' => 0, 'weight' => 0.0],
      'static' => ['data' => 'modules/w3css_paragraphs/css/x.css', 'group' => 0, 'weight' => 0.0],
      'sibling directory' => ['data' => 'public://w3css_paragraphs_other/x.css', 'group' => 0, 'weight' => 0.0],
      'public://w3css-x.css' => ['data' => 'public://w3css-x.css', 'group' => 0, 'weight' => 0.0],
      'no data' => ['group' => 0, 'weight' => 0.0],
    ];
    $this->hooks()->cssAlter($css);
    $this->assertSame(CSS_AGGREGATE_THEME, $css[$ours]['group']);
    $this->assertSame(1000.0, $css[$ours]['weight']);
    unset($css[$ours]);
    foreach ($css as $asset) {
      $this->assertSame(0, $asset['group']);
      $this->assertSame(0.0, $asset['weight']);
    }
  }

  /**
   * Tests that uninstalling this module, and only this one, clears the files.
   */
  public function testPreuninstall(): void {
    $this->files->expects($this->once())->method('deleteAll')->willReturn(2);
    $this->state->expects($this->once())->method('delete')->with('w3css_paragraphs.css_files_seen');
    $this->invalidator->expects($this->once())->method('invalidateTags')->with(['library_info']);
    $this->hooks()->modulePreuninstall('other_module');
    $this->hooks()->modulePreuninstall('w3css_paragraphs');
  }

  /**
   * Tests that every hook is declared.
   */
  public function testHookAttributes(): void {
    $found = [];
    foreach ((new \ReflectionClass(W3cssDynamicCssHooks::class))->getMethods() as $method) {
      foreach ($method->getAttributes(Hook::class) as $attribute) {
        $found[] = $attribute->getArguments()[0];
      }
    }
    sort($found);
    $this->assertSame([
      'cron',
      'css_alter',
      'entity_update',
      'library_info_build',
      'module_preuninstall',
      'node_delete',
      'node_insert',
      'node_update',
      'paragraph_delete',
      'paragraph_insert',
      'paragraph_update',
    ], $found);
  }

  /**
   * Tests services.yml against the constructors: drift a green suite misses.
   */
  public function testServiceDefinitionsMatchConstructors(): void {
    $services = Yaml::decode((string) file_get_contents(dirname(__DIR__, 3) . '/w3css_paragraphs.services.yml'))['services'];
    $checked = 0;
    foreach ($services as $id => $definition) {
      if (isset($definition['alias'])) {
        $this->assertArrayHasKey($definition['alias'], $services, $id);
        $this->assertTrue(interface_exists($id), $id);
        $this->assertTrue(is_subclass_of($services[$definition['alias']]['class'], $id), $id);
        continue;
      }
      if (!isset($definition['class']) || isset($definition['autowire'])) {
        continue;
      }
      $this->assertTrue(class_exists($definition['class']), $definition['class']);
      $constructor = (new \ReflectionClass($definition['class']))->getConstructor();
      $parameters = $constructor === NULL ? [] : $constructor->getParameters();
      $arguments = $definition['arguments'] ?? [];
      $this->assertCount(count($parameters), $arguments, $definition['class']);
      foreach ($parameters as $i => $parameter) {
        $type = (string) $parameter->getType();
        $argument = substr($arguments[$i], 1);
        if (isset($services[$argument]['class'])) {
          $this->assertTrue(is_a($services[$argument]['class'], $type, TRUE), $definition['class'] . ' argument ' . $i);
        }
      }
      $checked++;
    }
    $this->assertSame(9, $checked);
  }

}
