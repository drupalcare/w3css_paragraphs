<?php

declare(strict_types=1);

namespace Drupal\w3css_paragraphs\Hook;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Shows color radios as swatches, on the server and in any theme.
 *
 * No config or stored value changes: the radios stay real radios, so keyboard
 * use and saving are core's. A site using another widget is left alone.
 */
final class W3cssAdminFormHooks {

  use StringTranslationTrait;

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
   * Implements hook_field_widget_complete_form_alter().
   *
   * Styles the Paragraphs "Add" dialog. It must be the complete-form hook: a
   * paragraphs field with no items yet has no deltas, so the single-element
   * hooks never run on an empty form and the first dialog opened unstyled.
   *
   * @param array<string, mixed> $field_widget_complete_form
   *   The complete widget form for one field.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array<string, mixed> $context
   *   The widget context; `items` is the field item list.
   */
  #[Hook('field_widget_complete_form_alter')]
  public function attachAddDialog(array &$field_widget_complete_form, FormStateInterface $form_state, array $context): void {
    $items = $context['items'] ?? NULL;
    if (!$items instanceof FieldItemListInterface) {
      return;
    }
    $definition = $items->getFieldDefinition();
    if ($definition->getType() !== 'entity_reference_revisions'
      || $definition->getSetting('target_type') !== 'paragraph') {
      return;
    }
    $field_widget_complete_form['#attached']['library'][] = 'w3css_paragraphs/w3css-paragraphs-add-dialog';
  }

  /**
   * Implements hook_field_widget_single_element_WIDGET_TYPE_form_alter().
   *
   * Attaching here covers every route and theme, and Ajax-added paragraphs:
   * the response of the Ajax request carries the libraries.
   *
   * @param array<int|string, mixed> $element
   *   The widget element; each option key also holds a child element.
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
    // The option list is a grid; every rule is scoped under these classes.
    $element['#attributes']['class'][] = 'w3css-options';
    $element['#attributes']['class'][] = $isSwatch ? 'w3css-options--swatch' : 'w3css-options--inline';
    if ($field === 'w3css_display_card') {
      $element['#attributes']['class'][] = 'w3css-options--inline-wide';
    }
    if ($isSwatch) {
      $element['#attached']['library'][] = 'w3css_paragraphs/w3css-paragraphs-swatches';
    }
    // These radios draw their own look. Selectify, when installed, restyles
    // every radio unless it carries this opt-out; processRadios() copies the
    // parent #attributes to each option input.
    $element['#attributes']['data-selectify-skip'] = '';
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
   * Implements hook_field_widget_single_element_WIDGET_TYPE_form_alter().
   *
   * Shows a short label for each W3.CSS border, radius, margin and padding
   * option. Display text only: the stored value and the field's own allowed
   * values are not changed, and a value this list does not know keeps the
   * label the site gave it.
   *
   * @param array<string, mixed> $element
   *   The widget element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array<string, mixed> $context
   *   The widget context; `items` is the field item list.
   */
  #[Hook('field_widget_single_element_options_select_form_alter')]
  public function alterOptionsSelect(array &$element, FormStateInterface $form_state, array $context): void {
    $field = $context['items']->getName();
    $labels = $this->shortLabels()[$field] ?? NULL;
    if ($labels === NULL || empty($element['#options']) || !is_array($element['#options'])) {
      return;
    }
    foreach ($element['#options'] as $value => $label) {
      if (isset($labels[$value])) {
        $element['#options'][$value] = $labels[$value];
      }
    }
    $element['#attached']['library'][] = 'w3css_paragraphs/w3css-paragraphs-admin';
  }

  /**
   * Gives the short select labels, keyed by field and stored value.
   *
   * @return array<string, array<string, \Drupal\Core\StringTranslation\TranslatableMarkup>>
   *   The labels.
   */
  private function shortLabels(): array {
    $context = ['context' => 'W3CSS display option'];
    return [
      'w3css_display_border' => [
        'w3-border' => $this->t('1px, all sides', [], $context),
        'w3-border-3px' => $this->t('3px, all sides', [], $context),
        'w3-border-6px' => $this->t('6px, all sides', [], $context),
        'w3-border-top' => $this->t('1px, top', [], $context),
        'w3-border-right' => $this->t('1px, right', [], $context),
        'w3-border-bottom' => $this->t('1px, bottom', [], $context),
        'w3-border-left' => $this->t('1px, left', [], $context),
        'w3-topbar' => $this->t('6px bar, top', [], $context),
        'w3-rightbar' => $this->t('6px bar, right', [], $context),
        'w3-bottombar' => $this->t('6px bar, bottom', [], $context),
        'w3-leftbar' => $this->t('6px bar, left', [], $context),
      ],
      'w3css_display_round' => [
        'w3-round-small' => $this->t('2px', [], $context),
        'w3-round-medium' => $this->t('4px', [], $context),
        'w3-round-large' => $this->t('8px', [], $context),
        'w3-round-xlarge' => $this->t('16px', [], $context),
        'w3-round-xxlarge' => $this->t('32px', [], $context),
        'w3-circle' => $this->t('Circle', [], $context),
      ],
      'w3css_display_margin' => [
        'w3-margin' => $this->t('16px, all sides', [], $context),
        'w3-margin-top' => $this->t('16px, top', [], $context),
        'w3-margin-right' => $this->t('16px, right', [], $context),
        'w3-margin-bottom' => $this->t('16px, bottom', [], $context),
        'w3-margin-left' => $this->t('16px, left', [], $context),
        'w3-section' => $this->t('16px, top and bottom', [], $context),
      ],
      'w3css_display_padding' => [
        'w3-padding-small' => $this->t('4px by 8px', [], $context),
        'w3-padding' => $this->t('8px by 16px', [], $context),
        'w3-padding-large' => $this->t('12px by 24px', [], $context),
        'w3-padding-16' => $this->t('16px, top and bottom', [], $context),
        'w3-padding-24' => $this->t('24px, top and bottom', [], $context),
        'w3-padding-32' => $this->t('32px, top and bottom', [], $context),
        'w3-padding-48' => $this->t('48px, top and bottom', [], $context),
        'w3-padding-64' => $this->t('64px, top and bottom', [], $context),
      ],
    ];
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
