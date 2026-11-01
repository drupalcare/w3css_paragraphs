<?php

declare(strict_types=1);

namespace Drupal\Tests\w3css_paragraphs\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Update path of the parent module, from a simulated 1.0.x site.
 *
 * No real 1.0.x database dump exists in the repository, so the old state is
 * simulated: the link title attribute fields (added by update 9001) are
 * removed, then the update hooks run. This proves 9001 restores them and that
 * the whole 9001-9004 chain can run twice. It does not prove a real 1.0.x
 * site upgrades.
 *
 * @group w3css_paragraphs
 */
#[RunTestsInSeparateProcesses]
class W3cssParagraphsUpdatePathTest extends KernelTestBase {

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
   * Installs the parent module on a fresh site.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installEntitySchema('node');
    $this->installEntitySchema('paragraph');
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('user', ['users_data']);
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['system', 'field', 'filter', 'node']);
    $this->container->get('module_installer')->install(['w3css_paragraphs']);
    \Drupal::moduleHandler()->loadInclude('w3css_paragraphs', 'install');
  }

  /**
   * Update 9001 restores fields a 1.0.0 site lacks.
   */
  public function testUpdate9001RestoresRemovedFields(): void {
    foreach (['w3css_simple', 'w3css_image'] as $bundle) {
      FieldConfig::loadByName('paragraph', $bundle, 'w3css_content_link_title_attr')->delete();
    }
    // Deleting the last field of a storage deletes the storage as well.
    $this->assertNull(FieldStorageConfig::loadByName('paragraph', 'w3css_content_link_title_attr'));

    w3css_paragraphs_update_9001();

    $this->assertNotNull(FieldStorageConfig::loadByName('paragraph', 'w3css_content_link_title_attr'));
    foreach (['w3css_simple', 'w3css_image'] as $bundle) {
      $this->assertNotNull(FieldConfig::loadByName('paragraph', $bundle, 'w3css_content_link_title_attr'), "Field restored on $bundle.");
    }
  }

  /**
   * The whole chain can run on an up-to-date site, twice, without throwing.
   */
  public function testUpdateChainIsRerunnable(): void {
    foreach ([1, 2] as $unused) {
      w3css_paragraphs_update_9001();
      w3css_paragraphs_update_9002();
      w3css_paragraphs_update_9003();
      w3css_paragraphs_update_9004();
    }
    $this->addToAssertionCount(1);
  }

  /**
   * Update 9004 adds the field group child exactly once to a 1.0.0 display.
   */
  public function testUpdate9004AddsChildOnce(): void {
    $display = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('paragraph.w3css_simple.default');
    $settings = $display->getThirdPartySetting('field_group', 'w3css_tab_content');
    $settings['children'] = array_values(array_diff($settings['children'], ['w3css_content_link_title_attr']));
    $display->setThirdPartySetting('field_group', 'w3css_tab_content', $settings);
    $display->save();

    w3css_paragraphs_update_9004();

    $display = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('paragraph.w3css_simple.default');
    $children = $display->getThirdPartySetting('field_group', 'w3css_tab_content')['children'];
    $this->assertSame(1, count(array_keys($children, 'w3css_content_link_title_attr', TRUE)));
  }

  /**
   * Update 9004 run on a site that already has the child adds no duplicate.
   */
  public function testUpdate9004DoesNotDuplicateChildren(): void {
    w3css_paragraphs_update_9004();
    $display = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('paragraph.w3css_simple.default');
    $children = $display->getThirdPartySetting('field_group', 'w3css_tab_content')['children'];
    $this->assertSame(array_values(array_unique($children)), array_values($children));
  }

}
