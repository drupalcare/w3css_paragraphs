<?php

declare(strict_types=1);

namespace Drupal\w3css_paragraphs\Hook;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Hook implementations for w3css_paragraphs.
 */
final class W3cssParagraphsHooks {

  public function __construct(
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly PluginManagerInterface $filterManager,
  ) {}

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
