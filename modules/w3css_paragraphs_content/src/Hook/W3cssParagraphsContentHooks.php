<?php

namespace Drupal\w3css_paragraphs_content\Hook;

use Drupal\Core\Routing\AdminContext;
use Drupal\node\Entity\Node;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Hook implementations for w3css_paragraphs_content.
 */
class W3cssParagraphsContentHooks {

  public function __construct(
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly PluginManagerInterface $filterManager,
    private readonly AdminContext $adminContext,
    private readonly RouteMatchInterface $routeMatch,
  ) {}

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help(string $route_name, RouteMatchInterface $route_match): string|\Stringable|null {
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
        if (!$this->moduleHandler->moduleExists('markdown')) {
          return '<pre>' . $text . '</pre>';
        }
        // Use the Markdown filter to render the README.
        $settings = $this->configFactory->get('markdown.settings')->getRawData();
        $config = [
          'settings' => $settings,
        ];
        $filter = $this->filterManager->createInstance('markdown', $config);
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
