<?php

declare(strict_types=1);

namespace Drupal\Tests\w3css_paragraphs\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\KernelTests\KernelTestBase;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\w3css_paragraphs\Form\W3cssParagraphsSettingsForm;
use Drupal\w3css_paragraphs\Hook\W3cssDynamicCssHooks;
use Drupal\w3css_paragraphs\Hook\W3cssHeadingHooks;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Yaml\Yaml;

/**
 * Covers the heading setting and every bundle moved to a dynamic library.
 *
 * Renders through the real hooks and Twig, so it goes red if a template prints
 * CSS again, loses its id, or hard-codes a heading.
 *
 * @group w3css_paragraphs
 */
#[Group('w3css_paragraphs')]
#[RunTestsInSeparateProcesses]
final class W3cssParagraphsRolloutTest extends KernelTestBase {

  /**
   * Templates still allowed to print CSS, until their step of the plan.
   */
  private const STILL_INLINE = [
    'node--w3css-paragraphs-content.html.twig',
    'paragraph--default.html.twig',
    'paragraph--w3css-3d-flip-box.html.twig',
    'paragraph--w3css-parallax-section.html.twig',
  ];

  /**
   * Bundles whose color is on an inner element: the id suffix.
   */
  private const INNER = [
    'w3css_hero' => '--inner',
    'w3css_hero_full_width' => '--inner',
    'w3css_image' => '--inner',
    'w3css_image_overlay' => '--overlay',
    'w3css_responsive_image_narrow' => '--inner',
    'w3css_responsive_image_wide' => '--inner',
  ];

  /**
   * The inline CSS pattern used by the 1.x templates.
   */
  private const INLINE = '/style\s*=\s*["\']|<style\b/i';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'file',
    'image',
    'link',
    'media',
    'media_library',
    'options',
    'text',
    'filter',
    'views',
    'node',
    'block',
    // The custom block bundle's field and display are skipped without it.
    'block_content',
    'entity_reference_revisions',
    'paragraphs',
    'field_group',
    'w3css_paragraphs',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installEntitySchema('node');
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('paragraph');
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('user', ['users_data']);
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['system', 'field', 'filter', 'node', 'w3css_paragraphs']);
    $this->container->get('config.installer')->installOptionalConfig();
    $this->container->get('module_installer')->install([
      'w3css_paragraphs_card',
      'w3css_paragraphs_3d_carousel',
      'w3css_paragraphs_tabs',
      'w3css_paragraphs_custom_block',
      'w3css_paragraphs_accordion',
      'w3css_paragraphs_one_column',
      'w3css_paragraphs_three_columns',
      'w3css_paragraphs_slideshow',
      'w3css_paragraphs_block',
      'w3css_paragraphs_two_columns',
      'w3css_paragraphs_modal',
      'w3css_paragraphs_responsive_image',
      'w3css_paragraphs_hero',
      'w3css_paragraphs_hero_full_width',
      'w3css_paragraphs_image_overlay',
    ]);
    $this->container = \Drupal::getContainer();
    $this->container->get('theme_installer')->install(['stark']);
    $this->config('system.theme')->set('default', 'stark')->save();
  }

  /**
   * Renders a paragraph in a clean request state.
   *
   * @return array{0: string, 1: \Drupal\Core\Render\BubbleableMetadata}
   *   The markup and its metadata.
   */
  private function renderParagraph(ParagraphInterface $paragraph): array {
    $this->container->get('kernel')->rebuildContainer();
    $this->container = \Drupal::getContainer();
    $build = $this->container->get('entity_type.manager')->getViewBuilder('paragraph')->view($paragraph);
    $html = (string) $this->container->get('renderer')->renderRoot($build);
    return [$html, BubbleableMetadata::createFromRenderArray($build)];
  }

  /**
   * Lists generated files whose name starts with a prefix.
   *
   * @return list<string>
   *   Stream URIs of the files.
   */
  private function files(string $prefix = ''): array {
    $dir = $this->container->get('file_system')->realpath('public://w3css_paragraphs');
    if ($dir === FALSE || !is_dir($dir)) {
      return [];
    }
    $names = array_diff((array) scandir($dir), ['.', '..']);
    $matching = array_filter($names, fn($name) => str_starts_with((string) $name, $prefix));
    return array_values(array_map(fn($name) => 'public://w3css_paragraphs/' . $name, $matching));
  }

  /**
   * Reads the saved heading setting from storage.
   *
   * The editable config object also holds what a refused form put in it.
   */
  private function savedTag(): mixed {
    return $this->container->get('config.storage')->read(W3cssHeadingHooks::CONFIG)[W3cssHeadingHooks::KEY] ?? NULL;
  }

  /**
   * Lists every Twig file the project ships.
   *
   * @return array<string, string>
   *   Source keyed by file name.
   */
  private function templates(): array {
    $root = dirname(__DIR__, 3);
    $sources = [];
    $paths = array_merge(glob($root . '/templates/*.twig') ?: [], glob($root . '/modules/*/templates/*.twig') ?: []);
    foreach ($paths as $path) {
      $sources[basename($path)] = (string) file_get_contents($path);
    }
    $this->assertGreaterThan(20, count($sources), 'The scan found the templates.');
    return $sources;
  }

  /**
   * Tests each migrated bundle: its value in a file, none in the markup.
   */
  public function testEveryMigratedBundleUsesItsLibrary(): void {
    $this->assertCount(18, W3cssDynamicCssHooks::MIGRATED_BUNDLES);
    foreach (W3cssDynamicCssHooks::MIGRATED_BUNDLES as $bundle) {
      $paragraph = Paragraph::create([
        'type' => $bundle,
        'w3css_display_bg_color' => 'w3-244-67-54',
        'w3css_display_opacity' => '50',
      ]);
      $paragraph->save();
      $key = 'paragraph-' . str_replace('_', '-', $bundle) . '-' . $paragraph->id();
      $files = $this->files($key . '.');
      $this->assertCount(1, $files, $bundle);
      $target = '#w3css-' . $key . (self::INNER[$bundle] ?? '');
      $this->assertStringContainsString($target . '{background-color:rgba(244,67,54,0.50);}', (string) file_get_contents($files[0]), $bundle);

      [$html, $metadata] = $this->renderParagraph($paragraph);
      $this->assertSame(0, preg_match(self::INLINE, $html), $bundle . ' printed CSS.');
      $this->assertStringNotContainsString('rgba', $html, $bundle);
      $this->assertSame(1, substr_count($html, 'id="' . substr($target, 1) . '"'), $bundle . ' must print its id once.');
      $this->assertContains('w3css_paragraphs/dynamic--' . $key, $metadata->getAttachments()['library'], $bundle);
      $this->assertArrayNotHasKey('html_head', $metadata->getAttachments(), $bundle);
    }
  }

  /**
   * Tests a hostile stored value on each migrated bundle: nothing is emitted.
   */
  public function testHostileValueEmitsNothingOnEveryBundle(): void {
    foreach (W3cssDynamicCssHooks::MIGRATED_BUNDLES as $bundle) {
      $paragraph = Paragraph::create([
        'type' => $bundle,
        'w3css_display_bg_color' => 'w3-1-2-3;}</style><script>alert(1)</script>',
      ]);
      $paragraph->save();
      [$html, $metadata] = $this->renderParagraph($paragraph);
      $this->assertSame(0, preg_match(self::INLINE, $html), $bundle);
      $this->assertStringNotContainsString('alert(1)', $html, $bundle);
      $this->assertStringNotContainsString('id="w3css-', $html, $bundle);
      $this->assertArrayNotHasKey('html_head', $metadata->getAttachments(), $bundle);
    }
    $this->assertSame([], $this->files());
  }

  /**
   * Tests that only the named templates still print CSS.
   */
  public function testOnlyNamedTemplatesPrintCss(): void {
    $inline = [];
    foreach ($this->templates() as $name => $source) {
      if (preg_match(self::INLINE, $source) === 1) {
        $inline[] = $name;
      }
    }
    sort($inline);
    $this->assertSame(self::STILL_INLINE, $inline);
    // Control: the pattern finds what it is for.
    $this->assertSame(1, preg_match(self::INLINE, '<div style="color:red">'));
  }

  /**
   * Tests that no template hard-codes the title heading.
   */
  public function testTitleHeadingHasOneSource(): void {
    $using = 0;
    foreach ($this->templates() as $name => $source) {
      $this->assertSame(0, preg_match('/<h[1-6]\b[^>]*content-title/', $source), $name);
      if (str_contains($source, '<{{ content_title_tag }}')) {
        $using++;
      }
    }
    $this->assertSame(20, $using);
    // Control: the pattern finds what it is for.
    $this->assertSame(1, preg_match('/<h[1-6]\b[^>]*content-title/', '<h3 class="content-title">'));
  }

  /**
   * Tests the title under the default, a chosen and a hostile setting.
   */
  public function testTitleFollowsTheSetting(): void {
    $paragraph = Paragraph::create(['type' => 'w3css_simple', 'w3css_content_title' => 'Heading']);
    $paragraph->save();

    [$html, $metadata] = $this->renderParagraph($paragraph);
    $this->assertStringContainsString('<h3 class="content-title">', $html);
    $this->assertContains('config:' . W3cssHeadingHooks::CONFIG, $metadata->getCacheTags());

    $this->config(W3cssHeadingHooks::CONFIG)->set(W3cssHeadingHooks::KEY, 'h2')->save();
    [$html] = $this->renderParagraph($paragraph);
    $this->assertStringContainsString('<h2 class="content-title">', $html);
    $this->assertStringNotContainsString('<h3', $html);

    // Written past the schema, as a bad import or a direct write could be.
    $storage = $this->container->get('config.storage');
    $data = $storage->read(W3cssHeadingHooks::CONFIG);
    $data[W3cssHeadingHooks::KEY] = 'script onload=alert(1)';
    $storage->write(W3cssHeadingHooks::CONFIG, $data);
    $this->container->get('config.factory')->reset(W3cssHeadingHooks::CONFIG);
    [$html] = $this->renderParagraph($paragraph);
    $this->assertStringContainsString('<h3 class="content-title">', $html);
    $this->assertStringNotContainsString('alert(1)', $html);
  }

  /**
   * Tests the accordion item heading: below a title, level with none, h6 stops.
   */
  public function testAccordionItemFollowsTheSetting(): void {
    $section = fn() => Paragraph::create([
      'type' => 'w3css_accordion_section',
      'w3css_content_accordion_title' => 'Item',
    ]);
    $titled = Paragraph::create([
      'type' => 'w3css_accordion',
      'w3css_content_title' => 'Group',
      'w3css_content_accordion_section' => [$section()],
    ]);
    $titled->save();
    $untitled = Paragraph::create(['type' => 'w3css_accordion', 'w3css_content_accordion_section' => [$section()]]);
    $untitled->save();

    $cases = ['h3' => ['h4', 'h3'], 'h2' => ['h3', 'h2'], 'h6' => ['h6', 'h6']];
    foreach ($cases as $setting => [$withTitle, $withoutTitle]) {
      $this->config(W3cssHeadingHooks::CONFIG)->set(W3cssHeadingHooks::KEY, $setting)->save();
      [$html] = $this->renderParagraph($titled);
      $this->assertStringContainsString('<' . $withTitle . ' class="w3-row w3-accs-button', $html, $setting);
      [$html] = $this->renderParagraph($untitled);
      $this->assertStringContainsString('<' . $withoutTitle . ' class="w3-row w3-accs-button', $html, $setting);
    }
  }

  /**
   * Tests that the code, schema and shipped value agree.
   */
  public function testSchemaAndInstallConfigMatchTheCode(): void {
    $root = dirname(__DIR__, 3);
    $schema = Yaml::parseFile($root . '/config/schema/w3css_paragraphs.schema.yml');
    $mapping = $schema[W3cssHeadingHooks::CONFIG]['mapping'][W3cssHeadingHooks::KEY];
    $this->assertSame(W3cssHeadingHooks::ALLOWED_TAGS, $mapping['constraints']['Choice']['choices']);
    $shipped = Yaml::parseFile($root . '/config/install/' . W3cssHeadingHooks::CONFIG . '.yml');
    $this->assertSame([W3cssHeadingHooks::KEY => W3cssHeadingHooks::DEFAULT_TAG], $shipped);
    $this->assertSame(W3cssHeadingHooks::DEFAULT_TAG, $this->config(W3cssHeadingHooks::CONFIG)->get(W3cssHeadingHooks::KEY));
  }

  /**
   * Tests the update: a site without the setting gets h3, a chosen one stays.
   */
  public function testUpdateAddsTheDefaultOnlyWhenMissing(): void {
    $this->container->get('module_handler')->loadInclude('w3css_paragraphs', 'install');
    $this->config(W3cssHeadingHooks::CONFIG)->delete();
    $this->assertTrue($this->config(W3cssHeadingHooks::CONFIG)->isNew());

    w3css_paragraphs_update_9005();
    $this->assertSame('h3', $this->config(W3cssHeadingHooks::CONFIG)->get(W3cssHeadingHooks::KEY));

    $this->config(W3cssHeadingHooks::CONFIG)->set(W3cssHeadingHooks::KEY, 'h5')->save();
    w3css_paragraphs_update_9005();
    $this->assertSame('h5', $this->config(W3cssHeadingHooks::CONFIG)->get(W3cssHeadingHooks::KEY));
  }

  /**
   * Tests the form: a listed level saves, anything else is refused.
   */
  public function testFormSavesOnlyListedLevels(): void {
    $state = (new FormState())->setValues([W3cssHeadingHooks::KEY => 'h4']);
    $this->container->get('form_builder')->submitForm(W3cssParagraphsSettingsForm::class, $state);
    $this->assertSame([], $state->getErrors());
    $this->assertSame('h4', $this->savedTag());

    $state = (new FormState())->setValues([W3cssHeadingHooks::KEY => 'script']);
    $this->container->get('form_builder')->submitForm(W3cssParagraphsSettingsForm::class, $state);
    $this->assertNotSame([], $state->getErrors());
    $this->assertSame('h4', $this->savedTag());
  }

}
