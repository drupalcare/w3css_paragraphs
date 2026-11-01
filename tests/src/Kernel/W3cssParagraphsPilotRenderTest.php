<?php

declare(strict_types=1);

namespace Drupal\Tests\w3css_paragraphs\Kernel;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\KernelTests\KernelTestBase;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Renders the pilot bundle through the real hooks, container and Twig.
 *
 * Goes red if the template prints CSS again, or if the library stops being
 * attached, which would lose the color with no error.
 *
 * @group w3css_paragraphs
 */
#[Group('w3css_paragraphs')]
#[RunTestsInSeparateProcesses]
final class W3cssParagraphsPilotRenderTest extends KernelTestBase {

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
    $this->installEntitySchema('paragraph');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['system', 'field', 'filter', 'node']);
    // The module is already enabled by $modules, so its optional config has to
    // be installed here.
    $this->container->get('config.installer')->installOptionalConfig();
    $this->assertNotNull($this->container->get('entity_type.manager')
      ->getStorage('paragraphs_type')->load('w3css_simple'));
    $this->container->get('theme_installer')->install(['stark']);
    $this->config('system.theme')->set('default', 'stark')->save();
  }

  /**
   * Saves a pilot paragraph.
   */
  private function pilot(string $color, ?string $opacity = NULL): ParagraphInterface {
    $paragraph = Paragraph::create([
      'type' => 'w3css_simple',
      'w3css_display_bg_color' => $color,
      'w3css_display_opacity' => $opacity,
    ]);
    $paragraph->save();
    return $paragraph;
  }

  /**
   * Renders a paragraph in a clean request state.
   *
   * @return array{0: string, 1: array<string, mixed>}
   *   The markup and its attachments.
   */
  private function renderParagraph(ParagraphInterface $paragraph): array {
    // The file manager and the library collector remember one request. A new
    // page view starts without either.
    $this->container->get('kernel')->rebuildContainer();
    $this->container = \Drupal::getContainer();
    $build = $this->container->get('entity_type.manager')->getViewBuilder('paragraph')->view($paragraph);
    $html = (string) $this->container->get('renderer')->renderRoot($build);
    return [$html, BubbleableMetadata::createFromRenderArray($build)->getAttachments()];
  }

  /**
   * Lists generated files.
   *
   * @return list<string>
   *   File names in the generated CSS directory.
   */
  private function files(): array {
    $dir = $this->container->get('file_system')->realpath('public://w3css_paragraphs');
    return $dir === FALSE || !is_dir($dir) ? [] : array_values(array_diff((array) scandir($dir), ['.', '..']));
  }

  /**
   * Tests a saved paragraph: file written, library attached, no CSS in markup.
   */
  public function testSavedPilotUsesItsLibrary(): void {
    $paragraph = $this->pilot('w3-244-67-54', '50');
    $id = (int) $paragraph->id();
    $key = 'paragraph-w3css-simple-' . $id;
    $files = $this->files();
    $this->assertCount(1, $files);
    $this->assertStringStartsWith($key . '.', $files[0]);
    $css = file_get_contents('public://w3css_paragraphs/' . $files[0]);
    $this->assertSame('#w3css-' . $key . "{background-color:rgba(244,67,54,0.50);}\n", $css);

    [$html, $attached] = $this->renderParagraph($paragraph);
    $this->assertStringNotContainsString('style=', $html);
    $this->assertStringNotContainsString('<style', $html);
    $this->assertStringNotContainsString('rgba', $html);
    $this->assertStringContainsString('id="w3css-' . $key . '"', $html);
    $this->assertStringContainsString('p-container-inner', $html, 'The module template rendered.');
    $this->assertContains('w3css_paragraphs/dynamic--' . $key, $attached['library']);
    $this->assertArrayNotHasKey('html_head', $attached);

    // The library resolves to the file, so the link would print.
    $library = $this->container->get('library.discovery')->getLibraryByName('w3css_paragraphs', 'dynamic--' . $key);
    $this->assertIsArray($library);
    $this->assertSame('public://w3css_paragraphs/' . $files[0], $library['css'][0]['data']);
  }

  /**
   * Tests that no opacity is fully opaque, as the 1.x arithmetic was.
   */
  public function testNoOpacity(): void {
    $this->pilot('w3-33-150-243');
    $css = file_get_contents('public://w3css_paragraphs/' . $this->files()[0]);
    $this->assertStringContainsString('background-color:rgba(33,150,243,1)', $css);
  }

  /**
   * Tests the fallback: a missing file puts the same rule in the page head.
   */
  public function testMissingFileFallsBackToThePageHead(): void {
    $paragraph = $this->pilot('w3-244-67-54', '50');
    $this->container->get('file_system')->delete('public://w3css_paragraphs/' . $this->files()[0]);
    [$html, $attached] = $this->renderParagraph($paragraph);
    $this->assertStringNotContainsString('style=', $html);
    $this->assertStringNotContainsString('dynamic--', implode(',', $attached['library'] ?? []));
    $this->assertStringContainsString('rgba(244,67,54,0.50)', (string) $attached['html_head'][0][0]['#value']);
  }

  /**
   * Tests a hostile stored value: no file, no id, no CSS anywhere.
   *
   * @dataProvider hostileProvider
   */
  #[DataProvider('hostileProvider')]
  public function testHostileValueEmitsNothing(string $color): void {
    $paragraph = $this->pilot($color);
    $this->assertSame([], $this->files());
    [$html, $attached] = $this->renderParagraph($paragraph);
    $this->assertStringNotContainsString('style=', $html);
    $this->assertStringNotContainsString('<script', $html);
    $this->assertStringNotContainsString('id="w3css-', $html);
    $this->assertArrayNotHasKey('html_head', $attached);
  }

  /**
   * Data provider: values no form would offer.
   *
   * @return array<string, array{string}>
   *   Hostile stored colors.
   */
  public static function hostileProvider(): array {
    return [
      'closes the style' => ['w3-1-2-3</style><script>alert(1)</script>'],
      'adds a rule' => ['w3-1-2-3;}body{display:none'],
      'out of range' => ['w3-999-0-0'],
      'trailing newline' => ["w3-1-2-3\n"],
    ];
  }

  /**
   * Tests that a draft revision writes nothing and shows its own color inline.
   */
  public function testDraftKeepsThePublishedFile(): void {
    $paragraph = $this->pilot('w3-244-67-54', '50');
    $published = $this->files();

    $paragraph->set('w3css_display_bg_color', 'w3-33-150-243');
    $paragraph->setNewRevision();
    $paragraph->isDefaultRevision(FALSE);
    $paragraph->save();
    $this->assertSame($published, $this->files());

    [, $attached] = $this->renderParagraph($paragraph);
    $this->assertStringContainsString('rgba(33,150,243', (string) $attached['html_head'][0][0]['#value']);
  }

  /**
   * Tests the pilot's template source, whatever a render happens to reach.
   */
  public function testPilotTemplateHasNoInlineCss(): void {
    $source = file_get_contents(dirname(__DIR__, 3) . '/templates/paragraph--w3css-simple.html.twig');
    $this->assertSame(0, preg_match('/style\s*=\s*["\']|<style\b/i', (string) $source));
    // Control: the pattern finds what it is for.
    $this->assertSame(1, preg_match('/style\s*=\s*["\']|<style\b/i', '<div style="color:red">'));
  }

}
