<?php

declare(strict_types=1);

namespace Drupal\w3css_paragraphs\Service\Css;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Turns an entity's stored display values into scoped CSS.
 */
interface W3cssCssBuilderInterface {

  /**
   * Builds the CSS for one paragraph or node.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The paragraph or node, in the translation and revision being rendered.
   * @param string $viewMode
   *   The view mode. A paragraph field hidden in its view display gives no
   *   CSS, the same as the rendered field the 1.x templates read.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   Whose media access decides whether an image URL is included: the current
   *   user when NULL. A public file is built for the anonymous user.
   *
   * @return string
   *   The CSS, or an empty string when there is nothing valid to emit.
   */
  public function build(FieldableEntityInterface $entity, string $viewMode = 'default', ?AccountInterface $account = NULL): string;

  /**
   * Gives the one DOM id an entity's selectors and its template share.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The paragraph or node. An unsaved entity gets a UUID-based id.
   *
   * @return string|null
   *   The DOM id, or NULL for an unsupported entity or invalid identifier.
   */
  public function elementId(FieldableEntityInterface $entity): ?string;

  /**
   * Gives the key that names a saved entity's CSS file and library.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The paragraph or node.
   *
   * @return string|null
   *   The key, or NULL for an unsaved or unsupported entity.
   */
  public function storageKey(FieldableEntityInterface $entity): ?string;

  /**
   * Tells whether a paragraph bundle's color sits only on an inner element.
   *
   * @param string $bundle
   *   The bundle.
   *
   * @return bool
   *   TRUE when the outer element carries no rule, so it needs no generated id.
   */
  public function colorsInnerElementOnly(string $bundle): bool;

}
