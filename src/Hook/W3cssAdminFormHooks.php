<?php

declare(strict_types=1);

namespace Drupal\w3css_paragraphs\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Shows color radios as swatches, on the server and in any theme.
 *
 * No config or stored value changes: the radios stay real radios, so keyboard
 * use and saving are core's. A site using another widget is left alone.
 */
final class W3cssAdminFormHooks {

  /**
   * Color fields and the value prefix that "w3" replaces to name the color.
   *
   * An empty prefix means the stored value is already the class. Text and
   * border fields show their base color.
   */
  public const SWATCH_PREFIXES = [
    'w3css_paragraphs_node_bg' => '',
    'w3css_display_bg_color' => '',
    'w3css_display_hover_bg' => 'w3-hover',
    'w3css_display_text_color' => 'w3-text',
    'w3css_display_hover_text' => 'w3-hover-text',
    'w3css_display_border_color' => 'w3-border',
    'w3css_display_hover_border' => 'w3-hover-border',
    'w3css_display_active_item' => 'w3-is-active',
  ];

  /**
   * Fields whose radios sit in one row, without a color.
   */
  private const INLINE_FIELDS = [
    'w3css_display_opacity',
    'w3css_display_width',
    'w3css_display_card',
  ];

  /**
   * Implements hook_field_widget_single_element_WIDGET_TYPE_form_alter().
   *
   * Attaching here covers every route and theme, and Ajax-added paragraphs:
   * the response of the Ajax request carries the libraries.
   *
   * @param array<string, mixed> $element
   *   The widget element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array<string, mixed> $context
   *   The widget context; `items` is the field item list.
   */
  #[Hook('field_widget_single_element_options_buttons_form_alter')]
  public function alterOptionsButtons(array &$element, FormStateInterface $form_state, array $context): void {
    $field = $context['items']->getName();
    $isSwatch = array_key_exists($field, self::SWATCH_PREFIXES);
    if ((!$isSwatch && !in_array($field, self::INLINE_FIELDS, TRUE))
      || ($element['#type'] ?? NULL) !== 'radios'
      || empty($element['#options'])) {
      return;
    }
    $element['#attached']['library'][] = 'w3css_paragraphs/w3css-paragraphs-admin';
    if ($isSwatch) {
      $element['#attached']['library'][] = 'w3css_paragraphs/w3css-paragraphs-swatches';
    }
    // Radios::processRadios() keeps what an option's sub-element already has.
    foreach (array_keys($element['#options']) as $key) {
      $classes = ['w3css-choice'];
      if ($isSwatch) {
        $classes[] = 'w3css-swatch';
        $classes[] = $this->colorClass((string) $key, self::SWATCH_PREFIXES[$field]) ?? 'w3css-swatch--none';
      }
      $element[$key]['#wrapper_attributes']['class'] = $classes;
    }
  }

  /**
   * Gives the class that shows an option's color.
   *
   * @param string $value
   *   The option's stored value.
   * @param string $prefix
   *   The prefix that "w3" replaces; empty when the value is the class.
   *
   * @return string|null
   *   The class, or NULL for N/A and for a value that is not one of ours, such
   *   as an option a site added.
   */
  private function colorClass(string $value, string $prefix): ?string {
    if ($prefix !== '') {
      if (!str_starts_with($value, $prefix . '-')) {
        return NULL;
      }
      $value = 'w3' . substr($value, strlen($prefix));
    }
    return preg_match('/^w3-[a-z0-9-]+$/D', $value) === 1 ? $value : NULL;
  }

}
