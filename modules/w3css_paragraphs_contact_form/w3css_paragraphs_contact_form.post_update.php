<?php

/**
 * @file
 * Post update functions for W3CSS Paragraphs Contact Form.
 */

declare(strict_types=1);

/**
 * Switches contact paragraph displays to the module's own contact form formatter.
 */
function w3css_paragraphs_contact_form_post_update_own_formatter(): string {
  // The contact_formatter module has no release for Drupal 11.3 or later.
  // Every paragraph display that renders a contact form reference with its
  // formatter moves to w3css_contact_form, which renders the same form. The
  // site can then uninstall contact_formatter.
  $storage = \Drupal::entityTypeManager()->getStorage('entity_view_display');
  $changed = 0;
  foreach ($storage->loadByProperties(['targetEntityType' => 'paragraph']) as $display) {
    $updated = FALSE;
    foreach ($display->getComponents() as $name => $component) {
      if (($component['type'] ?? '') === 'contact_field_formatter') {
        $component['type'] = 'w3css_contact_form';
        $component['settings'] = [];
        $display->setComponent($name, $component);
        $updated = TRUE;
      }
    }
    if ($updated) {
      $display->save();
      $changed++;
    }
  }
  return (string) t('Contact form formatter switched on @count paragraph displays.', ['@count' => $changed]);
}
