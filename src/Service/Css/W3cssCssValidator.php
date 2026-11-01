<?php

declare(strict_types=1);

namespace Drupal\w3css_paragraphs\Service\Css;

/**
 * Default implementation of the CSS value validator.
 *
 * Every pattern carries the D modifier. Without it `$` also matches before a
 * final newline, so `w3-1-2-3\n` would pass.
 */
final class W3cssCssValidator implements W3cssCssValidatorInterface {

  /**
   * Longest accepted image URL.
   */
  private const MAX_URL_LENGTH = 2048;

  /**
   * An http or https URL: a plain host, an optional port, then a path.
   *
   * The host accepts no `@`, so `https://good.example@evil.example/` fails.
   */
  private const ABSOLUTE_URL = '/^https?:\/\/(?:[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?|\[[0-9a-f:]+\])(?::\d{1,5})?\//i';

  /**
   * {@inheritdoc}
   */
  public function color(mixed $value): ?string {
    if (!is_string($value) || preg_match('/^w3-(\d{1,3})-(\d{1,3})-(\d{1,3})$/D', $value, $matches) !== 1) {
      return NULL;
    }
    $channels = [(int) $matches[1], (int) $matches[2], (int) $matches[3]];
    if (max($channels) > 255) {
      return NULL;
    }
    return implode(',', $channels);
  }

  /**
   * {@inheritdoc}
   */
  public function opacity(mixed $value): ?string {
    if ($value === NULL || $value === '') {
      return '1';
    }
    if (!is_string($value) || preg_match('/^\d{2}$/D', $value) !== 1 || $value === '00') {
      return NULL;
    }
    return sprintf('0.%02d', (int) $value);
  }

  /**
   * {@inheritdoc}
   */
  public function rgba(mixed $color, mixed $opacity): ?string {
    $channels = $this->color($color);
    $alpha = $this->opacity($opacity);
    if ($channels === NULL || $alpha === NULL) {
      return NULL;
    }
    return 'rgba(' . $channels . ',' . $alpha . ')';
  }

  /**
   * {@inheritdoc}
   */
  public function pixels(mixed $value): ?int {
    if (is_string($value) && preg_match('/^[1-9]\d{0,8}$/D', $value) === 1) {
      $value = (int) $value;
    }
    if (!is_int($value) || $value < 1 || $value > self::MAX_PIXELS) {
      return NULL;
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function imageUrl(mixed $value): ?string {
    if (!is_string($value)
      || strlen($value) > self::MAX_URL_LENGTH
      || !mb_check_encoding($value, 'UTF-8')
      || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
      return NULL;
    }
    // Backslash first, or the escapes escape each other: a URL ending in a
    // backslash would otherwise escape the closing quote.
    $escaped = str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
    $stripped = str_replace(['{', '}', ';', '<', '>'], '', $escaped);
    // Checked after the strip, because removing a character can create the
    // prefix: `/;/evil.example` becomes the protocol-relative
    // `//evil.example`. An absolute URL must pass on both sides of the strip,
    // so `http;://evil.example/` cannot become one; the file URL generator
    // gives these for remote storage such as S3, and 1.x printed them.
    $isRootRelative = preg_match('/^\/[^\/\\\\]/', $stripped) === 1;
    $isAbsolute = preg_match(self::ABSOLUTE_URL, $value) === 1 && preg_match(self::ABSOLUTE_URL, $stripped) === 1;
    if (!$isRootRelative && !$isAbsolute) {
      return NULL;
    }
    return "url('" . $stripped . "')";
  }

  /**
   * {@inheritdoc}
   */
  public function slug(mixed $value): ?string {
    if (!is_string($value) || preg_match('/^[a-z0-9_]{1,128}$/D', $value) !== 1) {
      return NULL;
    }
    $slug = trim(str_replace('_', '-', $value), '-');
    return $slug === '' ? NULL : $slug;
  }

  /**
   * {@inheritdoc}
   */
  public function entityId(mixed $value): ?int {
    if (is_string($value) && preg_match('/^[1-9]\d{0,17}$/D', $value) === 1) {
      $value = (int) $value;
    }
    return is_int($value) && $value > 0 ? $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function uuid(mixed $value): ?string {
    if (!is_string($value) || preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/D', $value) !== 1) {
      return NULL;
    }
    return str_replace('-', '', $value);
  }

  /**
   * {@inheritdoc}
   */
  public function isStorageKey(string $key): bool {
    return strlen($key) <= 160
      && preg_match('/^(?:paragraph|node)-[a-z0-9](?:[a-z0-9-]*[a-z0-9])?-[1-9]\d*$/D', $key) === 1;
  }

  /**
   * {@inheritdoc}
   */
  public function isSafeCss(string $css): bool {
    return !str_contains($css, '<');
  }

}
