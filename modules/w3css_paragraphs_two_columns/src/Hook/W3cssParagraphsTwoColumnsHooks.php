<?php

namespace Drupal\w3css_paragraphs_two_columns\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for w3css_paragraphs_two_columns.
 */
class W3cssParagraphsTwoColumnsHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.w3css_paragraphs_two_columns':
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
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) {
    return [
      'paragraph__w3css_two_columns' => [
        'base hook' => 'paragraph',
      ],
    ];
  }

}
