<?php

declare(strict_types=1);

namespace Drupal\w3css_paragraphs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\w3css_paragraphs\Hook\W3cssHeadingHooks;

/**
 * Configures W3CSS Paragraphs.
 */
final class W3cssParagraphsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'w3css_paragraphs_settings';
  }

  /**
   * {@inheritdoc}
   *
   * @return list<string>
   *   The config names this form edits.
   */
  protected function getEditableConfigNames(): array {
    return [W3cssHeadingHooks::CONFIG];
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, mixed> $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array<string, mixed>
   *   The form.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $tags = W3cssHeadingHooks::ALLOWED_TAGS;
    $form[W3cssHeadingHooks::KEY] = [
      '#type' => 'select',
      '#title' => $this->t('Heading level of paragraph titles'),
      '#description' => $this->t('Choose the level that fits the page outline around your paragraphs. Accordion item headings use the next level down.'),
      '#options' => array_combine($tags, $tags),
      '#config_target' => W3cssHeadingHooks::CONFIG . ':' . W3cssHeadingHooks::KEY,
    ];
    return parent::buildForm($form, $form_state);
  }

}
