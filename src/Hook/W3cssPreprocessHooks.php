<?php

declare(strict_types=1);

namespace Drupal\w3css_paragraphs\Hook;

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Holds the module's one implementation of the paragraph preprocess hook.
 */
final class W3cssPreprocessHooks {

  /**
   * Themes that always load W3.CSS themselves.
   */
  private const THEMES_WITH_W3CSS = ['drupal8_w3css_theme'];

  /**
   * Themes that load W3.CSS when their typography.load_w3css setting is on.
   */
  private const THEMES_WITH_W3CSS_SETTING = ['solo'];

  /**
   * Prefix shared by the display option fields.
   */
  private const DISPLAY_PREFIX = 'w3css_display_';

  /**
   * Named groups of display classes, in the order templates print them.
   *
   * Keys are field names without the w3css_display_ prefix. This is the one
   * home for which shared option lands in which group and in what order.
   */
  private const CLASS_GROUPS = [
    // The wrapper of most bundles: colours, frame and the free classes field.
    'box' => [
      'hover_bg',
      'text_color',
      'hover_text',
      'border',
      'round',
      'border_color',
      'card',
      'hover_border',
      'classes',
    ],
    // The inner spacing wrapper.
    'spacing' => ['margin', 'padding'],
    // Text colours, used apart from the frame by image and hero bundles.
    'text' => ['hover_bg', 'text_color', 'hover_text'],
    // Border, rounding and card shadow around an image.
    'frame' => ['border', 'round', 'border_color', 'hover_border', 'card'],
    // The box without the free classes field, as the 3D carousel prints it.
    'box_base' => [
      'hover_bg',
      'text_color',
      'hover_text',
      'border',
      'round',
      'border_color',
      'card',
      'hover_border',
    ],
    // The hero text panel: colours, then spacing, then the hero option.
    'hero_text' => [
      'hover_bg',
      'text_color',
      'hover_text',
      'margin',
      'padding',
      'hero',
    ],
    // The same panel on the full-width hero, which has its own option field.
    'hero_full_width_text' => [
      'hover_bg',
      'text_color',
      'hover_text',
      'margin',
      'padding',
      'hero_full_width',
    ],
    // The image overlay's caption colours and its frame, rounding first.
    'overlay_text' => ['text_color', 'hover_text', 'round'],
    'overlay_frame' => ['round', 'border', 'border_color', 'hover_border', 'card'],
    // The parallax section's text panel: colours, frame, then spacing.
    'parallax_text' => [
      'hover_bg',
      'text_color',
      'hover_text',
      'border',
      'round',
      'border_color',
      'hover_border',
      'margin',
      'padding',
    ],
  ];

  /**
   * Older template variable names, kept for templates copied into themes.
   *
   * Each maps to the display field (without prefix) that fills it.
   */
  private const LEGACY_NAMES = [
    'container_w_input' => 'width',
    'w3css_hover_bg' => 'hover_bg',
    'w3css_txt_color' => 'text_color',
    'w3css_hover_txt' => 'hover_text',
    'w3css_border' => 'border',
    'w3css_round' => 'round',
    'w3css_border_color' => 'border_color',
    'w3css_card' => 'card',
    'w3css_hover_border' => 'hover_border',
    'w3css_classes' => 'classes',
    'w3css_margin' => 'margin',
    'w3css_padding' => 'padding',
  ];

  public function __construct(
    private readonly W3cssDynamicCssHooks $dynamicCss,
    private readonly W3cssHeadingHooks $headings,
    private readonly ThemeManagerInterface $themeManager,
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Implements hook_preprocess_paragraph().
   *
   * @param array<string, mixed> $variables
   *   The preprocess variables.
   */
  #[Hook('preprocess_paragraph')]
  public function preprocessParagraph(array &$variables): void {
    $this->headings->preprocessParagraph($variables);
    $this->dynamicCss->preprocessParagraph($variables);
    $this->attachFramework($variables);
    $this->addDisplayVariables($variables);
  }

  /**
   * Implements hook_preprocess_node().
   *
   * @param array<string, mixed> $variables
   *   The preprocess variables.
   */
  #[Hook('preprocess_node')]
  public function preprocessNode(array &$variables): void {
    $this->dynamicCss->preprocessNode($variables);
  }

  /**
   * Attaches W3.CSS to this module's paragraphs when the theme lacks it.
   *
   * @param array<string, mixed> $variables
   *   The preprocess variables.
   */
  private function attachFramework(array &$variables): void {
    $paragraph = $variables['paragraph'] ?? NULL;
    if (!is_object($paragraph) || !method_exists($paragraph, 'bundle') || !str_starts_with((string) $paragraph->bundle(), 'w3css_')) {
      return;
    }
    // The render cache already varies by theme. The setting's config tag
    // refreshes the page when a site turns Solo's W3.CSS option on or off.
    $theme = $this->themeManager->getActiveTheme();
    $names = array_merge([$theme->getName()], array_keys($theme->getBaseThemeExtensions()));
    if (array_intersect($names, self::THEMES_WITH_W3CSS)) {
      return;
    }
    if (array_intersect($names, self::THEMES_WITH_W3CSS_SETTING)) {
      $config = $this->configFactory->get($theme->getName() . '.settings');
      $variables['#cache']['tags'] = array_merge($variables['#cache']['tags'] ?? [], $config->getCacheTags());
      if ($config->get('typography.load_w3css')) {
        return;
      }
    }
    $variables['#attached']['library'][] = 'w3css_paragraphs/w3css-framework';
  }

  /**
   * Builds the shared wrapper classes once, from the display option fields.
   *
   * Adds w3css_display (field => class), w3css_wrapper_classes (base classes
   * plus width), w3css_class_groups (see CLASS_GROUPS) and the older
   * per-field variable names. A field counts only when the view display
   * shows it, as the templates' former content.FIELD|render test did.
   *
   * @param array<string, mixed> $variables
   *   The preprocess variables.
   */
  private function addDisplayVariables(array &$variables): void {
    $paragraph = $variables['paragraph'] ?? NULL;
    // The module's paragraph--default template serves every bundle without
    // its own template, so the shared variables are built for all of them.
    // The older per-field names stay limited to this module's bundles.
    if (!$paragraph instanceof FieldableEntityInterface) {
      return;
    }
    $content = is_array($variables['content'] ?? NULL) ? $variables['content'] : [];

    $display = [];
    foreach ($paragraph->getFieldDefinitions() as $name => $definition) {
      if (!str_starts_with($name, self::DISPLAY_PREFIX) || !in_array($definition->getType(), ['list_string', 'string'], TRUE)) {
        continue;
      }
      if (!$this->isShown($content[$name] ?? NULL)) {
        continue;
      }
      $value = trim((string) $paragraph->get($name)->value);
      if ($value !== '') {
        $display[substr($name, strlen(self::DISPLAY_PREFIX))] = $value;
      }
    }

    $wrapper = [
      'w3-row',
      'p-container',
      'paragraph',
      'paragraph--type--' . Html::getClass($paragraph->bundle()),
    ];
    $viewMode = $variables['view_mode'] ?? '';
    if (is_string($viewMode) && $viewMode !== '') {
      $wrapper[] = 'paragraph--view-mode--' . Html::getClass($viewMode);
    }
    if ($paragraph instanceof EntityPublishedInterface && !$paragraph->isPublished()) {
      $wrapper[] = 'paragraph--unpublished';
    }
    $wrapper[] = 'paragraph--id--' . $paragraph->id();
    $widthClass = isset($display['width']) ? 'p-container-width-' . $display['width'] : '';
    if ($widthClass !== '') {
      $wrapper[] = $widthClass;
    }

    $groups = [];
    foreach (self::CLASS_GROUPS as $group => $keys) {
      $groups[$group] = array_values(array_filter(array_map(static fn (string $key): string => $display[$key] ?? '', $keys)));
    }

    $variables['w3css_display'] = $display;
    $variables['w3css_wrapper_classes'] = $wrapper;
    $variables['w3css_class_groups'] = $groups;
    if (!str_starts_with($paragraph->bundle(), 'w3css_')) {
      return;
    }
    foreach (self::LEGACY_NAMES as $legacy => $key) {
      $variables[$legacy] = $display[$key] ?? '';
    }
    $variables['container_width'] = $widthClass;
  }

  /**
   * Whether a content element will print, short of rendering it.
   *
   * @param mixed $element
   *   The element from $variables['content'], or NULL when absent.
   */
  private function isShown(mixed $element): bool {
    if (!is_array($element)) {
      return FALSE;
    }
    $access = $element['#access'] ?? TRUE;
    if ($access instanceof AccessResultInterface) {
      return $access->isAllowed();
    }
    return (bool) $access;
  }

}
