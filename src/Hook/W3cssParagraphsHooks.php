<?php

namespace Drupal\w3css_paragraphs\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for w3css_paragraphs.
 */
class W3cssParagraphsHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.w3css_paragraphs':
        // dirname(__DIR__, 2), NOT __DIR__. This code used to live in the
        // module's .module file at the project root, where __DIR__ was the
        // module directory. Moving it into src/Hook/ for the OOP hook
        // migration silently repointed it at src/Hook/README.md, which does
        // not exist: file_get_contents() returned FALSE, and the help page
        // rendered as an empty <pre></pre> with no error on screen.
        $readme = dirname(__DIR__, 2) . '/README.md';
        $text = is_readable($readme) ? file_get_contents($readme) : FALSE;

        // A missing or unreadable README is now NULL rather than an empty
        // box, so it cannot look like a rendered page that just has nothing
        // in it.
        if ($text === FALSE) {
          return NULL;
        }
        if (!\Drupal::moduleHandler()->moduleExists('markdown')) {
          return '<pre>' . $text . '</pre>';
        }
        // Use the Markdown filter to render the README.
        $filter_manager = \Drupal::service('plugin.manager.filter');
        $settings = \Drupal::configFactory()->get('markdown.settings')->getRawData();
        $config = [
          'settings' => $settings,
        ];
        $filter = $filter_manager->createInstance('markdown', $config);
        return $filter->process($text, 'en');
    }
    return NULL;
  }

  /**
   * Implements hook_preprocess_page().
   */
  #[Hook('preprocess_page')]
  public function preprocessPage(&$variables) {
    if (!\Drupal::config('system.theme')->get('admin')) {
      $admin_theme = \Drupal::config('system.theme')->get('default');
    }
    else {
      $admin_theme = \Drupal::config('system.theme')->get('admin');
    }
    // No admin theme selected or value not existing. Cancel.
    if (empty($admin_theme) || !\Drupal::service('theme_handler')->themeExists($admin_theme)) {
      return;
    }
    $admin_theme_name = \Drupal::service('theme_handler')->getName($admin_theme);
    /** @var \Drupal\Core\Routing\AdminContext $admin_context */
    $admin_context = \Drupal::service('router.admin_context');
    if ($admin_context->isAdminRoute()) {
      if ($admin_theme_name == 'Seven') {
        $variables['#attached']['library'][] = "w3css_paragraphs/w3css-paragraphs-admin-seven";
      }
      else {
        $variables['#attached']['library'][] = "w3css_paragraphs/w3css-paragraphs-admin-claro";
      }
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) {
    return [
      'paragraph__default' => [
        'base hook' => 'paragraph',
      ],
      'paragraph__w3css_simple' => [
        'base hook' => 'paragraph',
      ],
      'paragraph__w3css_image' => [
        'base hook' => 'paragraph',
      ],
      'field__entity_reference_revisions' => [
        'base hook' => 'paragraph',
      ],
    ];
  }

}
