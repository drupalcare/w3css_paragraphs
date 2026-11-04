<?php

namespace Drupal\w3css_paragraphs_webform\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\filter\Plugin\FilterInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Hook implementations for w3css_paragraphs_webform.
 */
class W3cssParagraphsWebformHooks {

  /**
   * Constructs the hook implementations.
   */
  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
    protected ConfigFactoryInterface $configFactory,
    #[Autowire(service: 'plugin.manager.filter')]
    protected DefaultPluginManager $filterManager,
  ) {}

  /**
   * Implements hook_help().
   *
   * @return string|\Stringable|null
   *   The help text, or NULL when there is none.
   */
  #[Hook('help')]
  public function help(string $route_name, RouteMatchInterface $route_match): string|\Stringable|null {
    switch ($route_name) {
      case 'help.page.w3css_paragraphs_webform':
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
        if (!$filter instanceof FilterInterface) {
          return NULL;
        }
        return $filter->process($text, 'en');
    }
    return NULL;
  }

}
