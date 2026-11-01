<?php

declare(strict_types=1);

namespace Drupal\Tests\w3css_paragraphs\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Update 9005 of the 3D carousel: corrects a stored NULL, nothing else.
 *
 * @group w3css_paragraphs
 */
#[RunTestsInSeparateProcesses]
class W3cssParagraphsCarouselUpdateTest extends KernelTestBase {

  /**
   * The carousel body field config name.
   */
  protected const FIELD = 'field.field.paragraph.w3css_3d_carousel.w3css_content_3d_car_body';

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
   * Installs the carousel module on a fresh site.
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
    $this->container->get('module_installer')->install(['w3css_paragraphs_3d_carousel']);
    $this->container->get('module_handler')->loadInclude('w3css_paragraphs_3d_carousel', 'install');
  }

  /**
   * Writes the drag-drop value straight to storage, bypassing the schema check.
   *
   * Stands in for a site installed by a release that shipped the value empty.
   */
  protected function storeDragDrop(mixed $value): void {
    $storage = $this->container->get('config.storage');
    $data = $storage->read(self::FIELD);
    $data['settings']['handler_settings']['target_bundles_drag_drop'] = $value;
    $storage->write(self::FIELD, $data);
    $this->container->get('config.factory')->reset(self::FIELD);
    $this->container->get('entity_type.manager')->getStorage('field_config')->resetCache();
  }

  /**
   * Reads the stored drag-drop value back from storage.
   */
  protected function readDragDrop(): mixed {
    return $this->container->get('config.storage')->read(self::FIELD)['settings']['handler_settings']['target_bundles_drag_drop'];
  }

  /**
   * A fresh install stores an empty sequence, not NULL.
   */
  public function testFreshInstallStoresEmptySequence(): void {
    $this->assertSame([], $this->readDragDrop());
  }

  /**
   * A stored NULL becomes an empty sequence, and the field then passes schema.
   */
  public function testUpdate9005CorrectsNull(): void {
    $this->storeDragDrop(NULL);
    $this->assertNull($this->readDragDrop());
    w3css_paragraphs_3d_carousel_update_9005();
    $this->assertSame([], $this->readDragDrop());
  }

  /**
   * A value the site set itself is left alone.
   */
  public function testUpdate9005LeavesOtherValuesAlone(): void {
    $custom = ['w3css_simple' => ['enabled' => TRUE, 'weight' => 3]];
    $this->storeDragDrop($custom);
    w3css_paragraphs_3d_carousel_update_9005();
    $this->assertSame($custom, $this->readDragDrop());
  }

}
