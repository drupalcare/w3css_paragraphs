<?php

declare(strict_types=1);

namespace Drupal\w3css_paragraphs\Service\Css;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileInterface;
use Drupal\image\ImageStyleInterface;
use Psr\Log\LoggerInterface;

/**
 * Default implementation of the CSS builder.
 *
 * A bundle with no entry below is still handled: the decision is "has the
 * field", so a site's own bundles keep their color. Secondary elements get
 * their own id suffix rather than a descendant selector, because a flip box
 * can hold paragraphs that contain another flip box.
 */
final class W3cssCssBuilder implements W3cssCssBuilderInterface {

  /**
   * Paragraph background color field.
   */
  private const COLOR_FIELD = 'w3css_display_bg_color';

  /**
   * Paragraph background opacity field.
   */
  private const OPACITY_FIELD = 'w3css_display_opacity';

  /**
   * Bundles whose color is not on the outer element: id suffixes.
   */
  private const COLOR_TARGETS = [
    'w3css_hero' => ['--inner'],
    'w3css_hero_full_width' => ['--inner'],
    'w3css_image' => ['--inner'],
    'w3css_image_overlay' => ['--overlay'],
    'w3css_parallax_section' => ['--text'],
    'w3css_responsive_image_narrow' => ['--inner'],
    'w3css_responsive_image_wide' => ['--inner'],
  ];

  /**
   * Bundles with a pixel value: field, id suffix and CSS property.
   */
  private const PIXEL_RULES = [
    'w3css_3d_flip_box' => [
      ['field' => 'w3css_display_3d_height', 'suffix' => '--box', 'property' => 'height'],
    ],
  ];

  /**
   * The bundle with a background image.
   */
  private const PARALLAX_BUNDLE = 'w3css_parallax_section';

  /**
   * The paragraph field holding the parallax media.
   */
  private const PARALLAX_IMAGE_FIELD = 'w3css_content_image';

  /**
   * The image field on the module's media type.
   */
  private const MEDIA_IMAGE_FIELD = 'w3css_media_image';

  /**
   * The only node bundle with display values.
   */
  private const NODE_BUNDLE = 'w3css_paragraphs_content';

  /**
   * Per-request view displays, keyed by display id.
   *
   * @var array<string, \Drupal\Core\Entity\Display\EntityViewDisplayInterface|null>
   */
  private array $displays = [];

  public function __construct(
    private readonly W3cssCssValidatorInterface $validator,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerInterface $logger,
    private readonly FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function build(FieldableEntityInterface $entity, string $viewMode = 'default', ?AccountInterface $account = NULL): string {
    $elementId = $this->elementId($entity);
    if ($elementId === NULL) {
      return '';
    }
    $rules = match ($entity->getEntityTypeId()) {
      'paragraph' => $this->paragraphRules($entity, $viewMode, $account),
      'node' => $this->nodeRules($entity),
      default => [],
    };
    $css = '';
    foreach ($rules as $suffix => $declaration) {
      $css .= '#' . $elementId . $suffix . '{' . $declaration . ";}\n";
    }
    return $css;
  }

  /**
   * {@inheritdoc}
   */
  public function elementId(FieldableEntityInterface $entity): ?string {
    $key = $this->storageKey($entity);
    if ($key !== NULL) {
      return 'w3css-' . $key;
    }
    $prefix = $this->keyPrefix($entity);
    $uuid = $this->validator->uuid($entity->uuid());
    if ($prefix === NULL || $uuid === NULL || $entity->id() !== NULL) {
      return NULL;
    }
    return 'w3css-' . $prefix . '-u' . $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function storageKey(FieldableEntityInterface $entity): ?string {
    $prefix = $this->keyPrefix($entity);
    $id = $this->validator->entityId($entity->id());
    if ($prefix === NULL || $id === NULL) {
      return NULL;
    }
    return $prefix . '-' . $id;
  }

  /**
   * {@inheritdoc}
   */
  public function colorsInnerElementOnly(string $bundle): bool {
    return isset(self::COLOR_TARGETS[$bundle]);
  }

  /**
   * Gives the part of the key shared by saved and unsaved entities.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return string|null
   *   `{entity type}-{slug}`, or NULL when not supported.
   */
  private function keyPrefix(FieldableEntityInterface $entity): ?string {
    $type = $entity->getEntityTypeId();
    if ($type !== 'paragraph' && $type !== 'node') {
      return NULL;
    }
    $slug = $this->validator->slug($entity->bundle());
    return $slug === NULL ? NULL : $type . '-' . $slug;
  }

  /**
   * Builds a paragraph's declarations.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The paragraph.
   * @param string $viewMode
   *   The view mode.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   Whose media access applies; the current user when NULL.
   *
   * @return array<string, string>
   *   Declarations keyed by id suffix.
   */
  private function paragraphRules(FieldableEntityInterface $entity, string $viewMode, ?AccountInterface $account): array {
    $bundle = $entity->bundle();
    $rules = [];
    $color = $this->displayedValue($entity, self::COLOR_FIELD, $viewMode);
    if ($color !== NULL) {
      $opacity = $this->displayedValue($entity, self::OPACITY_FIELD, $viewMode);
      $rgba = $this->validated($entity, self::COLOR_FIELD, $this->validator->rgba($color, $opacity));
      if ($rgba !== NULL) {
        foreach (self::COLOR_TARGETS[$bundle] ?? [''] as $suffix) {
          $rules[$suffix] = 'background-color:' . $rgba;
        }
        // Only the title layout also colors the title. The stored key is
        // `title`; 1.x prefixes it to `w3-image-overlay-title` to compare.
        if ($bundle === 'w3css_image_overlay'
          && $this->displayedValue($entity, 'w3css_display_image_overlay', $viewMode) === 'title') {
          $rules['--title'] = 'background-color:' . $rgba;
        }
      }
    }
    foreach (self::PIXEL_RULES[$bundle] ?? [] as $rule) {
      $value = $this->displayedValue($entity, $rule['field'], $viewMode);
      if ($value !== NULL) {
        $pixels = $this->validated($entity, $rule['field'], $this->validator->pixels($value));
        if ($pixels !== NULL) {
          $rules[$rule['suffix']] = $rule['property'] . ':' . $pixels . 'px';
        }
      }
    }
    if ($bundle === self::PARALLAX_BUNDLE) {
      $background = $this->parallaxBackground($entity, $viewMode, $account);
      if ($background !== NULL) {
        $rules['--bg'] = $background;
      }
    }
    return $rules;
  }

  /**
   * Builds the parallax image declaration; min-height comes only with an image.
   *
   * Follows the rendered path of 1.x: the paragraph display names the media
   * view mode, and that display's formatter names the image style.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The paragraph.
   * @param string $viewMode
   *   The view mode.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   Whose media access applies; the current user when NULL.
   *
   * @return string|null
   *   The declaration, or NULL when no image may be shown.
   */
  private function parallaxBackground(FieldableEntityInterface $entity, string $viewMode, ?AccountInterface $account): ?string {
    $component = $this->viewDisplay('paragraph', $entity->bundle(), $viewMode)?->getComponent(self::PARALLAX_IMAGE_FIELD);
    if ($component === NULL || !$entity->hasField(self::PARALLAX_IMAGE_FIELD)) {
      return NULL;
    }
    $media = $entity->get(self::PARALLAX_IMAGE_FIELD)->entity;
    if (!$media instanceof FieldableEntityInterface || !$media->access('view', $account) || !$media->hasField(self::MEDIA_IMAGE_FIELD)) {
      return NULL;
    }
    $mediaMode = $component['settings']['view_mode'] ?? 'default';
    $formatter = $this->viewDisplay('media', $media->bundle(), is_string($mediaMode) ? $mediaMode : 'default')?->getComponent(self::MEDIA_IMAGE_FIELD);
    $file = $media->get(self::MEDIA_IMAGE_FIELD)->entity;
    if ($formatter === NULL || !$file instanceof FileInterface) {
      return NULL;
    }
    $url = $this->validated($entity, self::PARALLAX_IMAGE_FIELD, $this->validator->imageUrl($this->imageUrl($file, $formatter['settings']['image_style'] ?? '')));
    if ($url === NULL) {
      return NULL;
    }
    $declaration = 'background-image:' . $url;
    $height = $this->displayedValue($entity, 'w3css_display_parallax_height', $viewMode);
    if ($height !== NULL) {
      $pixels = $this->validated($entity, 'w3css_display_parallax_height', $this->validator->pixels($height));
      if ($pixels !== NULL) {
        $declaration .= ';min-height:' . $pixels . 'px';
      }
    }
    return $declaration;
  }

  /**
   * Gives the image URL as the image_url formatter does.
   *
   * Root-relative for local files, absolute for remote storage such as S3.
   *
   * @param \Drupal\file\FileInterface $file
   *   The image file.
   * @param mixed $styleId
   *   The image style id from the formatter settings; empty for the original.
   *
   * @return string
   *   The URL.
   */
  private function imageUrl(FileInterface $file, mixed $styleId): string {
    $uri = (string) $file->getFileUri();
    $style = is_string($styleId) && $styleId !== '' ? $this->entityTypeManager->getStorage('image_style')->load($styleId) : NULL;
    if ($style instanceof ImageStyleInterface) {
      return $this->fileUrlGenerator->transformRelative($style->buildUrl($uri));
    }
    return $this->fileUrlGenerator->generateString($uri);
  }

  /**
   * Builds a node's declarations. 1.x reads these from the entity directly.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The node.
   *
   * @return array<string, string>
   *   Declarations keyed by id suffix.
   */
  private function nodeRules(FieldableEntityInterface $entity): array {
    if ($entity->bundle() !== self::NODE_BUNDLE) {
      return [];
    }
    $rules = [];
    $color = $this->storedValue($entity, 'w3css_paragraphs_node_bg');
    if ($color !== NULL) {
      $opacity = $this->storedValue($entity, 'w3css_paragraphs_node_bgopacity');
      $rgba = $this->validated($entity, 'w3css_paragraphs_node_bg', $this->validator->rgba($color, $opacity));
      if ($rgba !== NULL) {
        $rules[''] = 'background-color:' . $rgba;
      }
    }
    $width = $this->storedValue($entity, 'w3css_paragraphs_max_width');
    if ($width !== NULL) {
      $pixels = $this->validated($entity, 'w3css_paragraphs_max_width', $this->validator->pixels($width));
      if ($pixels !== NULL) {
        // The margin is here and not a class: 1.x printed it on every view
        // mode, and the content module's stylesheet loads on node pages only.
        $rules['--article'] = 'max-width:' . $pixels . 'px;margin:0 auto';
      }
    }
    return $rules;
  }

  /**
   * Logs a rejected value by field name, never by its content.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   * @param string $field
   *   The field name.
   * @param string|int|null $result
   *   The validator's result.
   *
   * @return string|int|null
   *   The same result.
   */
  private function validated(FieldableEntityInterface $entity, string $field, string|int|null $result): string|int|null {
    if ($result === NULL) {
      $this->logger->warning('Rejected the value of @field on @type @id; no CSS was emitted for it.', [
        '@field' => $field,
        '@type' => $entity->getEntityTypeId(),
        '@id' => (string) $this->validator->entityId($entity->id()),
      ]);
    }
    return $result;
  }

  /**
   * Reads a field's value if its view display shows it.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   * @param string $field
   *   The field name.
   * @param string $viewMode
   *   The view mode.
   *
   * @return mixed
   *   The stored value, or NULL when absent or hidden.
   */
  private function displayedValue(FieldableEntityInterface $entity, string $field, string $viewMode): mixed {
    $value = $this->storedValue($entity, $field);
    if ($value === NULL) {
      return NULL;
    }
    $display = $this->viewDisplay($entity->getEntityTypeId(), $entity->bundle(), $viewMode);
    return $display !== NULL && $display->getComponent($field) !== NULL ? $value : NULL;
  }

  /**
   * Reads a field's raw first value.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   * @param string $field
   *   The field name.
   *
   * @return mixed
   *   The stored value, or NULL when absent.
   */
  private function storedValue(FieldableEntityInterface $entity, string $field): mixed {
    if (!$entity->hasField($field) || $entity->get($field)->isEmpty()) {
      return NULL;
    }
    $values = $entity->get($field)->first()?->getValue();
    return is_array($values) ? ($values['value'] ?? NULL) : NULL;
  }

  /**
   * Loads the display core would render: the view mode's, else the default.
   *
   * @param string $type
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param string $viewMode
   *   The view mode.
   *
   * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface|null
   *   The display, or NULL when there is none, which shows no field.
   */
  private function viewDisplay(string $type, string $bundle, string $viewMode): ?object {
    $id = $type . '.' . $bundle . '.' . $viewMode;
    if (!array_key_exists($id, $this->displays)) {
      $storage = $this->entityTypeManager->getStorage('entity_view_display');
      $display = preg_match('/^[a-z0-9_]+$/D', $viewMode) === 1 ? $storage->load($id) : NULL;
      if ($display === NULL || !$display->status()) {
        $display = $storage->load($type . '.' . $bundle . '.default');
      }
      /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface|null $display */
      $this->displays[$id] = $display;
    }
    return $this->displays[$id];
  }

}
