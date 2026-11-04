<?php

/**
 * @file
 * Post update functions for W3CSS Paragraphs Custom Block.
 */

declare(strict_types=1);

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;

/**
 * Adds the custom block field to sites that do not have it.
 */
function w3css_paragraphs_custom_block_post_update_restore_block_field(): string {
  // Installing Block Content brings this module's optional config only where
  // it is absent, so a form or view display a site already has never gained the
  // field. Nothing that exists is replaced: a hidden field stays hidden and a
  // component stays as the site set it.
  $module = 'w3css_paragraphs_custom_block';
  $field = 'w3css_content_custom_block';
  $moduleHandler = \Drupal::moduleHandler();
  if (!$moduleHandler->moduleExists('block_content')) {
    return (string) t('Block Content is not installed; the custom block field was not checked.');
  }

  // The parent's helper creates each config entity only when it is absent.
  $moduleHandler->loadInclude('w3css_paragraphs', 'install');
  $form = 'core.entity_form_display.paragraph.w3css_custom_block.default';
  $view = 'core.entity_view_display.paragraph.w3css_custom_block.default';
  _w3css_paragraphs_update_fields_from_yml($module, [
    'field.storage.paragraph.' . $field,
    'field.field.paragraph.w3css_custom_block.' . $field,
    $form,
    $view,
  ]);

  $files = new FileStorage($moduleHandler->getModule($module)->getPath() . '/config/optional');
  $entityTypeManager = \Drupal::entityTypeManager();
  $added = 0;
  foreach ([$form => 'entity_form_display', $view => 'entity_view_display'] as $configName => $entityType) {
    $record = $files->read($configName);
    $display = $entityTypeManager->getStorage($entityType)->load('paragraph.w3css_custom_block.default');
    if (!is_array($record) || !$display instanceof EntityDisplayInterface || !isset($record['content'][$field])) {
      continue;
    }
    if ($display->getComponent($field) !== NULL || isset($display->get('hidden')[$field])) {
      continue;
    }
    $display->setComponent($field, $record['content'][$field]);
    // Without its group the widget would print below the form's tabs.
    if ($display instanceof EntityFormDisplayInterface) {
      foreach ($record['third_party_settings']['field_group'] ?? [] as $name => $shipped) {
        $group = $display->getThirdPartySetting('field_group', $name);
        if (is_array($group) && in_array($field, $shipped['children'] ?? [], TRUE) && !in_array($field, $group['children'] ?? [], TRUE)) {
          $group['children'][] = $field;
          $display->setThirdPartySetting('field_group', $name, $group);
        }
      }
    }
    $display->save();
    $added++;
  }
  return (string) t('Checked the custom block field; added it to @count existing displays.', ['@count' => $added]);
}
