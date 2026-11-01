<?php

declare(strict_types=1);

namespace Drupal\w3css_paragraphs\Service\Css;

/**
 * Describes each generated CSS file as a Drupal library.
 */
interface W3cssDynamicLibraryRegistrarInterface {

  /**
   * Prefix of every dynamic library name.
   */
  public const LIBRARY_PREFIX = 'dynamic--';

  /**
   * Weight that places the files after theme CSS.
   */
  public const ASSET_WEIGHT = 1000;

  /**
   * Gives the full library id for a key, ready to attach.
   *
   * @param string $key
   *   The storage key.
   *
   * @return string
   *   `w3css_paragraphs/dynamic--{key}`.
   */
  public function libraryId(string $key): string;

  /**
   * Builds every library definition.
   *
   * @return array<string, array{header: bool, css: array<string, array<string, array<string, mixed>>>}>
   *   Definitions keyed by library name.
   */
  public function buildLibraries(): array;

}
