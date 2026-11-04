<?php

declare(strict_types=1);

namespace Drupal\w3css_paragraphs_contact_form\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders the referenced contact form as a form a visitor can submit.
 *
 * Replaces the contact_formatter module, which has no release for the Drupal
 * versions this module supports.
 *
 * @extends \Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase<\Drupal\Core\Entity\EntityInterface>
 */
#[FieldFormatter(
  id: 'w3css_contact_form',
  label: new TranslatableMarkup('Contact form'),
  field_types: ['entity_reference'],
)]
final class W3cssContactFormFormatter extends EntityReferenceFormatterBase {

  /**
   * Constructs the formatter.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array<string, mixed> $settings
   *   The formatter settings.
   * @param string $label
   *   The label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array<string, mixed> $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entityFormBuilder
   *   The entity form builder.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFormBuilderInterface $entityFormBuilder,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param array<string, mixed> $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    return $field_definition->getFieldStorageDefinition()->getSetting('target_type') === 'contact_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemList<\Drupal\Core\Entity\EntityInterface> $items
   *   The field items.
   * @param string $langcode
   *   The language code.
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    // getEntitiesToView() checks 'view' access on each contact form, which
    // the contact module grants with 'access site-wide contact form'.
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $contact_form) {
      $message = $this->entityTypeManager->getStorage('contact_message')->create([
        'contact_form' => $contact_form->id(),
      ]);
      $form = $this->entityFormBuilder->getForm($message);
      // Same cacheability as the contact module's own page: the form carries
      // a token and per-user defaults, so it is not cached.
      $form['#cache']['contexts'][] = 'user.permissions';
      $form['#cache']['max-age'] = 0;
      $elements[$delta] = $form;
    }
    return $elements;
  }

}
