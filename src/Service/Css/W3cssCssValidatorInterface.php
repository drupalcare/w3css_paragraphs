<?php

declare(strict_types=1);

namespace Drupal\w3css_paragraphs\Service\Css;

/**
 * The one place a stored value may become part of generated CSS.
 *
 * Form validation does not cover imports, migrations or API writes, so every
 * value is checked here at output. Every method fails closed: a value that
 * does not match has no valid rendering and gives NULL or FALSE, never a
 * scrubbed remnant.
 */
interface W3cssCssValidatorInterface {

  /**
   * Largest accepted height, min-height or max-width, in pixels.
   */
  public const MAX_PIXELS = 10000;

  /**
   * Validates a stored color such as `w3-244-67-54`.
   *
   * @param mixed $value
   *   The stored value.
   *
   * @return string|null
   *   `R,G,B` rebuilt from three integers, or NULL when invalid.
   */
  public function color(mixed $value): ?string;

  /**
   * Validates a stored two-digit opacity from 01 to 99.
   *
   * @param mixed $value
   *   The stored value. NULL or an empty string means absent.
   *
   * @return string|null
   *   `0.NN`, `1` when absent, or NULL when present and invalid.
   */
  public function opacity(mixed $value): ?string;

  /**
   * Builds an rgba() value from a stored color and opacity.
   *
   * @param mixed $color
   *   The stored color.
   * @param mixed $opacity
   *   The stored opacity.
   *
   * @return string|null
   *   The rgba() value, or NULL when either value is invalid.
   */
  public function rgba(mixed $color, mixed $opacity): ?string;

  /**
   * Validates a pixel size.
   *
   * @param mixed $value
   *   The stored value: an int or a string of plain ASCII digits.
   *
   * @return int|null
   *   An integer from 1 to MAX_PIXELS, or NULL when invalid.
   */
  public function pixels(mixed $value): ?int;

  /**
   * Builds a CSS url() from a root-relative or absolute http or https URL.
   *
   * @param mixed $value
   *   A URL produced by the file URL generator.
   *
   * @return string|null
   *   A quoted url() value, or NULL when the URL is neither of those, for
   *   example another scheme or `//host`, holds a control character or is not
   *   valid UTF-8.
   */
  public function imageUrl(mixed $value): ?string;

  /**
   * Turns a machine name into a slug for selectors and file names.
   *
   * @param mixed $value
   *   A bundle machine name.
   *
   * @return string|null
   *   The kebab-case slug, or NULL when the value is not a machine name.
   */
  public function slug(mixed $value): ?string;

  /**
   * Validates an entity id.
   *
   * @param mixed $value
   *   The entity id.
   *
   * @return int|null
   *   A positive integer, or NULL when invalid or unsaved.
   */
  public function entityId(mixed $value): ?int;

  /**
   * Validates a UUID for the id of an unsaved entity.
   *
   * @param mixed $value
   *   The UUID.
   *
   * @return string|null
   *   The 32 hex digits with no dashes, or NULL when invalid.
   */
  public function uuid(mixed $value): ?string;

  /**
   * Checks the key that names a saved entity's file and library.
   *
   * @param string $key
   *   A key such as `paragraph-w3css-simple-42`.
   *
   * @return bool
   *   TRUE only for `{paragraph|node}-{slug}-{positive integer}`.
   */
  public function isStorageKey(string $key): bool;

  /**
   * Last guard before CSS is written to a file or the page head.
   *
   * Generated CSS can never legitimately contain `<`. If it does, a guarantee
   * upstream has failed.
   *
   * @param string $css
   *   The generated CSS.
   *
   * @return bool
   *   TRUE when the CSS may be emitted.
   */
  public function isSafeCss(string $css): bool;

}
