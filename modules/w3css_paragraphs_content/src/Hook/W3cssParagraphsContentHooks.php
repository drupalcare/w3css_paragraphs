<?php

namespace Drupal\w3css_paragraphs_content\Hook;

use Drupal\node\Entity\Node;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for w3css_paragraphs_content.
 */
class W3cssParagraphsContentHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.w3css_paragraphs_content':
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
  // Function w3css_paragraphs_content_theme($existing, $type, $theme, $path) {
  // return [
  // 'page__w3css_paragraphs_content' => [
  // 'base hook' => 'page',
  // ],
  // ];
  // }.

  /**
   * Implements hook_preprocess_page() for page templates.
   */
  #[Hook('preprocess_page')]
  public function preprocessPage(&$variables) {
    /** @var \Drupal\Core\Routing\AdminContext $admin_context */
    $admin_context = \Drupal::service('router.admin_context');
    if ($admin_context->isAdminRoute()) {
      $variables['#attached']['library'][] = "w3css_paragraphs_content/w3css-paragraphs-content-admin";
    }
    $route_match = \Drupal::routeMatch();
    $node_publish = $route_match->getParameter('node');
    $node_preview = $route_match->getParameter('node_preview');
    $node = $node_publish ?? $node_preview;
    if ($node instanceof Node && $node->bundle() == 'w3css_paragraphs_content') {
      $variables['#attached']['library'][] = "w3css_paragraphs_content/w3css-paragraphs-content-base";
      // Unset left and right.
      unset($variables['page']['sidebar_first'], $variables['page']['sidebar_second']);
      $selected_regions = array_column($node->w3css_paragraphs_disable_region->getValue(), 'value');
      foreach ($selected_regions as $x => $selected_region) {
        switch ($selected_region) {
          case 'header':
            unset($variables['page']['header']);
            break;

          case 'navigation':
            unset($variables['page']['primary_menu'], $variables['page']['primary_menu_vertical']);
            break;

          case 'welcome_text':
            unset($variables['page']['welcome_text']);
            break;

          case 'highlighted':
            unset($variables['page']['highlighted']);
            break;

          case 'top_regions':
            unset($variables['page']['top_first'], $variables['page']['top_second'], $variables['page']['top_third']);
            break;

          case 'page_title':
            unset($variables['page']['page_title']);
            break;

          case 'breadcrumb':
            unset($variables['page']['breadcrumb']);
            break;

          case 'bottom_regions':
            unset($variables['page']['bottom_first'], $variables['page']['bottom_second'], $variables['page']['bottom_third'], $variables['page']['bottom_forth']);
            break;

          case 'footer_regions':
            unset($variables['page']['footer_first'], $variables['page']['footer_second'], $variables['page']['footer_third']);
            break;

          case 'footer_menu':
            unset($variables['page']['footer_menu']);
            $variables['#attached']['library'][] = "w3css_paragraphs_content/hide-footer-menu";
            break;

          case 'copyright':
            $variables['#attached']['library'][] = "w3css_paragraphs_content/hide-copyright";
            break;
        }
      }
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) {
    return [
      'node__w3css_paragraphs_content' => [
        'base hook' => 'node',
      ],
    ];
  }

}
