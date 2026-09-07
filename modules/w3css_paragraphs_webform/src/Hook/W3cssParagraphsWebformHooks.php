<?php

namespace Drupal\w3css_paragraphs_webform\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for w3css_paragraphs_webform.
 */
class W3cssParagraphsWebformHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.w3css_paragraphs_webform':
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

}
