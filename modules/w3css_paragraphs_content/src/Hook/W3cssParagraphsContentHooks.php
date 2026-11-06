<?php

namespace Drupal\w3css_paragraphs_content\Hook;

use Drupal\Core\Routing\AdminContext;
use Drupal\node\Entity\Node;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for w3css_paragraphs_content.
 */
class W3cssParagraphsContentHooks {

  use StringTranslationTrait;

  public function __construct(
    private readonly AdminContext $adminContext,
    private readonly RouteMatchInterface $routeMatch,
  ) {}

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help(string $route_name, RouteMatchInterface $route_match): ?string {
    if ($route_name !== 'help.page.w3css_paragraphs_content') {
      return NULL;
    }
    $output = '<h2>' . $this->t('About') . '</h2>';
    $output .= '<p>' . $this->t('The W3CSS Paragraphs Content Type module provides the <em>W3CSS Paragraphs Content</em> content type, which can hold every W3CSS paragraph type. For more information, see the <a href=":project">online documentation for the W3CSS Paragraphs module</a>.', [
      ':project' => 'https://www.drupal.org/project/w3css_paragraphs',
    ]) . '</p>';
    $output .= '<h2>' . $this->t('Uses') . '</h2>';
    $output .= '<dl>';
    $output .= '<dt>' . $this->t('Building a page from paragraphs') . '</dt>';
    $output .= '<dd>' . $this->t('Create content of the <em>W3CSS Paragraphs Content</em> type and add the W3CSS paragraph types you have enabled, in any order.') . '</dd>';
    $output .= '</dl>';
    return $output;
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
   *
   * @param array<string, mixed> $variables
   *   The page template variables.
   */
  #[Hook('preprocess_page')]
  public function preprocessPage(array &$variables): void {
    if ($this->adminContext->isAdminRoute()) {
      $variables['#attached']['library'][] = "w3css_paragraphs_content/w3css-paragraphs-content-admin";
    }
    $node_publish = $this->routeMatch->getParameter('node');
    $node_preview = $this->routeMatch->getParameter('node_preview');
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
   *
   * @param array<string, mixed> $existing
   *   An array of existing implementations.
   * @param string $type
   *   The type of the theme.
   * @param string $theme
   *   The name of the theme.
   * @param string $path
   *   The path of the theme.
   *
   * @return array<string, array<string, mixed>>
   *   The theme hook definitions.
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'node__w3css_paragraphs_content' => [
        'base hook' => 'node',
      ],
    ];
  }

}
