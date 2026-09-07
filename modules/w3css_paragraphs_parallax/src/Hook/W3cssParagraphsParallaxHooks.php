<?php

namespace Drupal\w3css_paragraphs_parallax\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for w3css_paragraphs_parallax.
 */
class W3cssParagraphsParallaxHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.w3css_paragraphs_parallax':
        $text = file_get_contents(__DIR__ . "/README.md");
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
      'paragraph__w3css_parallax_section' => [
        'base hook' => 'paragraph',
      ],
    ];
  }

}
