<?php

declare(strict_types=1);

namespace Drupal\w3css_paragraphs\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Sets the heading tags every paragraph template prints.
 *
 * Called from W3cssPreprocessHooks: core allows a module one implementation of
 * a preprocess hook.
 */
final class W3cssHeadingHooks {

  /**
   * The setting's config object.
   */
  public const CONFIG = 'w3css_paragraphs.settings';

  /**
   * The setting's key.
   */
  public const KEY = 'content_title_tag';

  /**
   * The tag used when the setting is missing or holds anything else.
   */
  public const DEFAULT_TAG = 'h3';

  /**
   * The tags a site may choose. The config schema's list is tested against it.
   */
  public const ALLOWED_TAGS = ['h2', 'h3', 'h4', 'h5', 'h6'];

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Sets the title tag, and the item tag one level below it.
   *
   * Sets `content_title_tag` and `content_item_tag`. Item headings stop at h6,
   * the lowest HTML has.
   *
   * @param array<string, mixed> $variables
   *   The preprocess variables.
   */
  public function preprocessParagraph(array &$variables): void {
    $config = $this->configFactory->get(self::CONFIG);
    // Twig prints this as a tag name, and config can arrive by import without
    // passing the form, so only a listed value gets through.
    $stored = $config->get(self::KEY);
    $tag = in_array($stored, self::ALLOWED_TAGS, TRUE) ? $stored : self::DEFAULT_TAG;
    $variables['content_title_tag'] = $tag;
    $variables['content_item_tag'] = 'h' . min(6, (int) substr($tag, 1) + 1);
    // Changing the setting must reach pages already in the render cache.
    foreach ($config->getCacheTags() as $cacheTag) {
      $variables['#cache']['tags'][] = $cacheTag;
    }
  }

}
