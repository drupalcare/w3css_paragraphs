<?php

/**
 * @file
 * Post update functions for W3CSS Paragraphs.
 */

declare(strict_types=1);

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\paragraphs\ParagraphsTypeInterface;
use Drupal\w3css_paragraphs\Hook\W3cssDynamicCssHooks;

/**
 * Finds the Display tab among a form display's field groups.
 *
 * @param array<string, array<string, mixed>> $groups
 *   The field groups.
 *
 * @return string|null
 *   The group name; 23 bundles say w3css_tab_display, 3 say group_display.
 */
function _w3css_paragraphs_display_tab(array $groups): ?string {
  foreach ($groups as $name => $group) {
    if (($group['format_type'] ?? '') === 'tab' && ($group['format_settings']['classes'] ?? '') === 'w3css-tab-display') {
      return $name;
    }
  }
  return NULL;
}

/**
 * Lists the groups inside the Display tab, at any depth.
 *
 * @param array<string, array<string, mixed>> $groups
 *   The field groups.
 * @param string $tab
 *   The Display tab's group name.
 *
 * @return array<string, array<string, mixed>>
 *   The groups under the tab, keyed by name.
 */
function _w3css_paragraphs_display_subgroups(array $groups, string $tab): array {
  $found = [];
  $parents = [$tab];
  while ($parents !== []) {
    $parent = array_shift($parents);
    foreach ($groups as $name => $group) {
      if (($group['parent_name'] ?? '') === $parent && !isset($found[$name])) {
        $found[$name] = $group;
        $parents[] = $name;
      }
    }
  }
  return $found;
}

/**
 * Lists the widgets inside the Display tab, at any depth.
 *
 * @param array<string, array<string, mixed>> $groups
 *   The field groups.
 * @param string $tab
 *   The Display tab's group name.
 *
 * @return list<string>
 *   The field names, sorted.
 */
function _w3css_paragraphs_display_fields(array $groups, string $tab): array {
  $fields = [];
  foreach ([$tab => $groups[$tab] ?? []] + _w3css_paragraphs_display_subgroups($groups, $tab) as $group) {
    foreach ($group['children'] ?? [] as $child) {
      if (!isset($groups[$child])) {
        $fields[] = $child;
      }
    }
  }
  sort($fields);
  return $fields;
}

/**
 * Tells whether a form display's groups are still as a 1.x site had them.
 *
 * @param \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display
 *   The site's form display.
 * @param array<string, array<string, mixed>> $current
 *   The site's field groups.
 * @param array<string, array<string, mixed>> $shipped
 *   The field groups this version ships.
 * @param string $tab
 *   The Display tab's group name.
 *
 * @return bool
 *   TRUE when only the shipped layout can be lost by reorganizing it.
 */
function _w3css_paragraphs_display_groups_unmodified(EntityFormDisplayInterface $display, array $current, array $shipped, string $tab): bool {
  // The shipped subgroups of the Display tab hold exactly the fields 1.x listed
  // in the tab itself, so the shipped YAML is the only list to keep.
  $subgroups = _w3css_paragraphs_display_subgroups($shipped, $tab);
  $legacy = array_diff_key($shipped, $subgroups);
  $legacy[$tab]['children'] = _w3css_paragraphs_display_fields($shipped, $tab);
  ksort($legacy);
  ksort($current);
  if (array_keys($legacy) !== array_keys($current)) {
    return FALSE;
  }
  foreach ($legacy as $name => $group) {
    foreach (['label', 'format_type', 'parent_name'] as $key) {
      if (($current[$name][$key] ?? NULL) !== ($group[$key] ?? NULL)) {
        return FALSE;
      }
    }
    if (($current[$name]['format_settings']['classes'] ?? NULL) !== ($group['format_settings']['classes'] ?? NULL)) {
      return FALSE;
    }
    // Sets, not lists: 1.x update hooks appended children, so the order varies
    // on untouched sites. The widgets' weights set the order on the form and
    // are not changed. A child with no widget is the stale 1.x quicklinks entry.
    $children = $current[$name]['children'] ?? [];
    if ($name !== $tab) {
      $children = array_filter($children, static fn(string $child): bool => isset($current[$child]) || $display->getComponent($child) !== NULL);
    }
    $expected = $group['children'] ?? [];
    sort($children);
    sort($expected);
    if ($children !== $expected) {
      return FALSE;
    }
  }
  return TRUE;
}

/**
 * Organizes the Display tab of unmodified paragraph forms into groups.
 */
function w3css_paragraphs_post_update_organize_display_groups(): string {
  // Only field groups move: no widget, weight, field or stored value changes.
  // A form a site changed is left alone and named in the result. The shipped
  // config/optional YAML of each enabled w3css module is the target, so a
  // sub-module that is not enabled has no form to touch and no list is kept.
  $storage = \Drupal::entityTypeManager()->getStorage('entity_form_display');
  $organized = 0;
  $customized = [];
  foreach (\Drupal::moduleHandler()->getModuleList() as $module => $extension) {
    if (!str_starts_with($module, 'w3css_paragraphs')) {
      continue;
    }
    $files = new FileStorage($extension->getPath() . '/config/optional');
    foreach ($files->listAll('core.entity_form_display.paragraph.') as $configName) {
      $record = $files->read($configName);
      $shipped = is_array($record) ? ($record['third_party_settings']['field_group'] ?? []) : [];
      $tab = _w3css_paragraphs_display_tab($shipped);
      $display = $tab === NULL ? NULL : $storage->load($record['id']);
      if (!$display instanceof EntityFormDisplayInterface) {
        continue;
      }
      $current = $display->getThirdPartySettings('field_group');
      // A rerun, or a site that imported the new config, has nothing to do.
      if (array_diff_key($shipped, $current) === []) {
        continue;
      }
      if (!_w3css_paragraphs_display_groups_unmodified($display, $current, $shipped, $tab)) {
        $customized[] = $record['id'];
        continue;
      }
      // The Content tab and the wrapper keep the site's settings; only a child
      // with no widget, the stale 1.x quicklinks entry, leaves them.
      foreach ($current as $name => $group) {
        if ($name !== $tab) {
          $group['children'] = array_values(array_filter($group['children'] ?? [], static fn(string $child): bool => isset($current[$child]) || $display->getComponent($child) !== NULL));
          $display->setThirdPartySetting('field_group', $name, $group);
        }
      }
      $current[$tab]['children'] = $shipped[$tab]['children'];
      $display->setThirdPartySetting('field_group', $tab, $current[$tab]);
      foreach (_w3css_paragraphs_display_subgroups($shipped, $tab) as $name => $group) {
        $display->setThirdPartySetting('field_group', $name, $group);
      }
      $display->save();
      $organized++;
    }
  }
  if ($customized === []) {
    return (string) t('Organized @count paragraph forms into groups.', ['@count' => $organized]);
  }
  sort($customized);
  \Drupal::logger('w3css_paragraphs')->notice('Left these customized paragraph forms as they are: @forms', ['@forms' => implode(', ', $customized)]);
  return (string) t('Organized @count paragraph forms into groups. Left these customized forms as they are: @forms', [
    '@count' => $organized,
    '@forms' => implode(', ', $customized),
  ]);
}

/**
 * Generates CSS files for existing paragraphs and content nodes.
 */
function w3css_paragraphs_post_update_generate_css(array &$sandbox): string {
  // Paragraphs first, then nodes: a node's references name the revision that
  // is public, which wins where 1.x left a published paragraph non-default.
  // Until a file exists the page head fallback keeps the look, so a failure
  // here costs a <style> element and never a color.
  $entityTypeManager = \Drupal::entityTypeManager();
  if (!isset($sandbox['types'])) {
    $sandbox['types'] = array_values(array_filter(
      ['paragraph', 'node'],
      static fn(string $type): bool => $entityTypeManager->hasDefinition($type),
    ));
    $sandbox['last_id'] = 0;
    $sandbox['done'] = 0;
    $sandbox['failed'] = 0;
    $sandbox['total'] = 0;
    foreach ($sandbox['types'] as $type) {
      $sandbox['total'] += (int) $entityTypeManager->getStorage($type)->getQuery()->accessCheck(FALSE)->count()->execute();
    }
  }
  if ($sandbox['types'] === []) {
    $sandbox['#finished'] = 1;
    return (string) t('No paragraphs or content items to process.');
  }

  $type = $sandbox['types'][0];
  $storage = $entityTypeManager->getStorage($type);
  $idKey = (string) $storage->getEntityType()->getKey('id');
  $ids = $storage->getQuery()
    ->accessCheck(FALSE)
    ->condition($idKey, $sandbox['last_id'], '>')
    ->sort($idKey)
    ->range(0, 50)
    ->execute();

  $hooks = \Drupal::service(W3cssDynamicCssHooks::class);
  foreach ($ids as $id) {
    $sandbox['last_id'] = (int) $id;
    $sandbox['done']++;
    // One bad entity must not stop the run.
    try {
      $entity = $storage->load($id);
      if ($entity instanceof FieldableEntityInterface) {
        $hooks->rebuildForEntity($entity);
      }
    }
    catch (\Throwable $e) {
      $sandbox['failed']++;
      \Drupal::logger('w3css_paragraphs')->error('Could not generate CSS for @type @id: @message', [
        '@type' => $type,
        '@id' => $id,
        '@message' => $e->getMessage(),
      ]);
    }
  }
  $storage->resetCache($ids);

  if ($ids === []) {
    array_shift($sandbox['types']);
    $sandbox['last_id'] = 0;
  }
  if ($sandbox['types'] !== []) {
    $sandbox['#finished'] = $sandbox['total'] > 0 ? min(0.99, $sandbox['done'] / $sandbox['total']) : 0.99;
    return '';
  }

  $sandbox['#finished'] = 1;
  \Drupal::service('cache_tags.invalidator')->invalidateTags(['library_info']);
  return (string) t('Processed @done entities for generated CSS; @failed failed and keep the page head fallback.', [
    '@done' => $sandbox['done'],
    '@failed' => $sandbox['failed'],
  ]);
}

/**
 * Regroups the Display tab into Background, Text, Border and Spacing groups.
 */
function w3css_paragraphs_post_update_regroup_display_tab(): string {
  // For sites that already ran organize_display_groups on a 2.0 development
  // build: those forms carry the interim Colors, Border, Spacing, Size and
  // Advanced groups. Only field groups move: no widget, weight, field or
  // stored value changes. A form a site changed is left alone and named.
  $interim = [
    'group_w3css_display_colors' => 'w3css-display-colors',
    'group_w3css_display_border' => 'w3css-display-border',
    'group_w3css_display_spacing' => 'w3css-display-spacing',
    'group_w3css_display_size' => 'w3css-display-size',
    'group_w3css_display_advanced' => 'w3css-display-advanced',
  ];
  $storage = \Drupal::entityTypeManager()->getStorage('entity_form_display');
  $regrouped = 0;
  $customized = [];
  foreach (\Drupal::moduleHandler()->getModuleList() as $module => $extension) {
    if (!str_starts_with($module, 'w3css_paragraphs')) {
      continue;
    }
    $files = new FileStorage($extension->getPath() . '/config/optional');
    foreach ($files->listAll('core.entity_form_display.paragraph.') as $configName) {
      $record = $files->read($configName);
      $shipped = is_array($record) ? ($record['third_party_settings']['field_group'] ?? []) : [];
      $tab = _w3css_paragraphs_display_tab($shipped);
      $display = $tab === NULL ? NULL : $storage->load($record['id']);
      if (!$display instanceof EntityFormDisplayInterface) {
        continue;
      }
      $current = $display->getThirdPartySettings('field_group');
      $currentTab = _w3css_paragraphs_display_tab($current);
      $shippedSub = _w3css_paragraphs_display_subgroups($shipped, $tab);
      // Already regrouped, or never organized (1.x layout, handled above).
      if (array_diff_key($shippedSub, $current) === []) {
        continue;
      }
      $currentSub = $currentTab === $tab ? _w3css_paragraphs_display_subgroups($current, $tab) : [];
      // No subgroups is the 1.x layout: organize_display_groups owns it.
      if ($currentSub === []) {
        continue;
      }
      $unmodified = _w3css_paragraphs_display_fields($current, $tab) === _w3css_paragraphs_display_fields($shipped, $tab);
      foreach ($currentSub as $name => $group) {
        // A bundle's own group, such as Additional, must be as shipped.
        if (isset($interim[$name])) {
          $unmodified = $unmodified
            && ($group['format_type'] ?? '') === 'details'
            && ($group['parent_name'] ?? '') === $tab
            && ($group['format_settings']['classes'] ?? '') === $interim[$name];
        }
        else {
          $unmodified = $unmodified && isset($shippedSub[$name]) && $group == $shippedSub[$name];
        }
      }
      if (!$unmodified) {
        $customized[] = $record['id'];
        continue;
      }
      foreach (array_keys($currentSub) as $name) {
        $display->unsetThirdPartySetting('field_group', $name);
      }
      $tabGroup = $current[$tab];
      $tabGroup['children'] = $shipped[$tab]['children'];
      $display->setThirdPartySetting('field_group', $tab, $tabGroup);
      foreach ($shippedSub as $name => $group) {
        $display->setThirdPartySetting('field_group', $name, $group);
      }
      $display->save();
      $regrouped++;
    }
  }
  if ($customized === []) {
    return (string) t('Regrouped the Display tab of @count paragraph forms.', ['@count' => $regrouped]);
  }
  sort($customized);
  \Drupal::logger('w3css_paragraphs')->notice('Left these customized paragraph forms as they are: @forms', ['@forms' => implode(', ', $customized)]);
  return (string) t('Regrouped the Display tab of @count paragraph forms. Left these customized forms as they are: @forms', [
    '@count' => $regrouped,
    '@forms' => implode(', ', $customized),
  ]);
}

/**
 * Closes the Display tab groups of W3CSS paragraph forms by default.
 */
function w3css_paragraphs_post_update_close_display_groups(): string {
  // The Display tab is long; each group now starts closed so an editor opens
  // only the one being changed. Only groups the module styles as Display tab
  // groups (class w3css-display-*) are touched.
  $storage = \Drupal::entityTypeManager()->getStorage('entity_form_display');
  $closed = 0;
  foreach ($storage->loadByProperties(['targetEntityType' => 'paragraph']) as $display) {
    $groups = $display->getThirdPartySettings('field_group');
    $changed = FALSE;
    foreach ($groups as $name => $group) {
      $classes = (string) ($group['format_settings']['classes'] ?? '');
      if (($group['format_type'] ?? '') !== 'details' || !str_contains($classes, 'w3css-display')) {
        continue;
      }
      if (!empty($group['format_settings']['open'])) {
        $group['format_settings']['open'] = FALSE;
        $display->setThirdPartySetting('field_group', $name, $group);
        $changed = TRUE;
        $closed++;
      }
    }
    if ($changed) {
      $display->save();
    }
  }
  return (string) t('Closed @count Display tab groups.', ['@count' => $closed]);
}

/**
 * Sets the product logo as the icon of each shipped bundle that has none.
 */
function w3css_paragraphs_post_update_bundle_type_icons(): string {
  // New installs get the icon from the shipped type config (icon_uuid and
  // icon_default). This fills sites that installed the types before the icon
  // shipped. A type that already has any icon is left alone. Paragraphs
  // creates the icon file from icon_default when the type is saved.
  $root = \Drupal::service('extension.list.module')->getPath('w3css_paragraphs');
  $storage = \Drupal::entityTypeManager()->getStorage('paragraphs_type');
  $set = [];
  foreach (\Drupal::moduleHandler()->getModuleList() as $extension) {
    $path = $extension->getPath();
    if ($path !== $root && !str_starts_with($path, $root . '/')) {
      continue;
    }
    $files = new FileStorage($path . '/config/optional');
    foreach ($files->listAll('paragraphs.paragraphs_type.') as $name) {
      $record = $files->read($name);
      if (!is_array($record) || empty($record['icon_uuid']) || empty($record['icon_default'])) {
        continue;
      }
      $type = $storage->load($record['id']);
      if (!$type instanceof ParagraphsTypeInterface || $type->get('icon_uuid')) {
        continue;
      }
      $type->set('icon_uuid', $record['icon_uuid']);
      $type->set('icon_default', $record['icon_default']);
      $type->save();
      $set[] = (string) $type->id();
    }
  }
  return $set === []
    ? (string) t('No paragraph type needed an icon.')
    : (string) t('Set the logo icon on: @types.', ['@types' => implode(', ', $set)]);
}
