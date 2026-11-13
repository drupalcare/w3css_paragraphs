<?php

declare(strict_types=1);

namespace Drupal\Tests\w3css_paragraphs\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Symfony\Component\Yaml\Yaml;

/**
 * Install, render and uninstall for the parent module and its sub-modules.
 *
 * One data provider row per module. A row whose required contrib module is
 * absent from the test site is skipped with a message naming that module.
 *
 * @group w3css_paragraphs
 */
#[RunTestsInSeparateProcesses]
class W3cssParagraphsLifecycleTest extends KernelTestBase {

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
    'options',
    'text',
    'filter',
    'views',
    'node',
    'block',
    'entity_reference_revisions',
    'paragraphs',
    'field_group',
  ];

  /**
   * Machine names of the parent and the 23 sub-modules.
   *
   * @return string[]
   *   Module machine names.
   */
  protected static function moduleNames(): array {
    $root = dirname(__DIR__, 3);
    $names = ['w3css_paragraphs'];
    foreach (glob($root . '/modules/*', GLOB_ONLYDIR) as $dir) {
      // An obsolete stub (w3css_paragraphs_menu) cannot be installed by
      // design; its removal is covered by the update path, not this test.
      $info = Yaml::parseFile($dir . '/' . basename($dir) . '.info.yml');
      if (($info['lifecycle'] ?? '') === 'obsolete') {
        continue;
      }
      $names[] = basename($dir);
    }
    return $names;
  }

  /**
   * Data provider: one row per module.
   */
  public static function moduleProvider(): array {
    $rows = [];
    foreach (self::moduleNames() as $name) {
      $rows[$name] = [$name];
    }
    return $rows;
  }

  /**
   * Returns the directory of a module inside the lane repo layout.
   */
  protected function moduleDir(string $module): string {
    $root = dirname(__DIR__, 3);
    return $module === 'w3css_paragraphs' ? $root : $root . '/modules/' . $module;
  }

  /**
   * Paragraph type ids a module ships in config/optional.
   *
   * @return string[]
   *   Paragraph type ids.
   */
  protected function shippedTypes(string $module): array {
    $ids = [];
    foreach (glob($this->moduleDir($module) . '/config/optional/paragraphs.paragraphs_type.*.yml') as $file) {
      $data = Yaml::parseFile($file);
      $ids[] = $data['id'];
    }
    return $ids;
  }

  /**
   * Install, render every shipped bundle, then uninstall.
   *
   * @dataProvider moduleProvider
   */
  public function testLifecycle(string $module): void {
    // Skip visibly when a required contrib module is not on this site.
    $info = Yaml::parseFile($this->moduleDir($module) . '/' . $module . '.info.yml');
    $discovered = $this->container->get('extension.list.module')->getList();
    foreach ($info['dependencies'] ?? [] as $dep) {
      [, $dep_name] = array_pad(explode(':', $dep), -2, NULL);
      if (!isset($discovered[$dep_name])) {
        $this->markTestSkipped("Row $module needs the module $dep_name, which is not on this test site.");
      }
    }
    $this->assertTrue(isset($discovered[$module]), "$module is discoverable (test run from a site that carries the module).");

    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installEntitySchema('node');
    $this->installEntitySchema('paragraph');
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('user', ['users_data']);
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['system', 'field', 'filter', 'node']);

    // Real install path: dependencies, config/optional and hook_install.
    $installer = $this->container->get('module_installer');
    $this->assertTrue($installer->install([$module]), "$module installs.");
    $this->assertTrue($this->container->get('module_handler')->moduleExists($module));

    // Install is idempotent in its config: shipped types exist.
    $types = $this->shippedTypes($module);
    foreach ($types as $id) {
      $this->assertNotNull(ParagraphsType::load($id), "Paragraph type $id exists after install.");
    }

    // Render each shipped bundle through the real view builder and theme.
    $this->container->get('theme_installer')->install(['stark']);
    $this->config('system.theme')->set('default', 'stark')->save();
    $renderer = $this->container->get('renderer');
    foreach ($types as $id) {
      // Saved, as on a real page: templates read paragraph.id.value.
      $paragraph = Paragraph::create(['type' => $id]);
      $paragraph->save();
      $build = $this->container->get('entity_type.manager')
        ->getViewBuilder('paragraph')
        ->view($paragraph);
      $html = (string) $renderer->renderRoot($build);
      $this->assertNotSame('', trim($html), "Bundle $id rendered something.");
      // A bundle with its own template file must show W3.CSS markup; one
      // without falls back to the generic paragraph markup.
      $template = 'paragraph--' . str_replace('_', '-', $id) . '.html.twig';
      if (is_file($this->moduleDir($module) . '/templates/' . $template)) {
        $this->assertStringContainsString('w3-', $html, "Bundle $id rendered through the module template.");
      }
    }

    // Uninstall: paragraph content first, as the paragraph module requires.
    foreach (Paragraph::loadMultiple() as $p) {
      $p->delete();
    }
    $dependents = array_keys($this->container->get('module_installer')->validateUninstall([$module]) ?: []);
    $this->assertSame([], $dependents, "$module has no uninstall blockers.");
    // The container is rebuilt by install: fetch the installer again.
    $this->assertTrue($this->container->get('module_installer')->uninstall([$module]), "$module uninstalls.");
    $this->assertFalse($this->container->get('module_handler')->moduleExists($module));
  }

}
