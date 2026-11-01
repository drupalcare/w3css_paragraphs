<?php

declare(strict_types=1);

namespace Drupal\w3css_paragraphs\Service\Css;

/**
 * Reads, writes and deletes the generated per-entity CSS files.
 *
 * A file is `public://w3css_paragraphs/{key}.{hash}.css`, where the hash is a
 * short digest of the bytes. A new value gives a new URL, so no cache flush is
 * needed for the browser to see it.
 */
interface W3cssCssFileManagerInterface {

  /**
   * The directory that holds every generated file.
   */
  public const DIRECTORY = 'public://w3css_paragraphs';

  /**
   * Gives the URI of the file for a key.
   *
   * @param string $key
   *   The storage key.
   *
   * @return string
   *   The URI, or an empty string when there is no file.
   */
  public function fileUri(string $key): string;

  /**
   * Checks that exactly this CSS is what the key's file holds.
   *
   * This is the rule that keeps a draft, preview, translation or stale file
   * from styling the wrong content: only one file may exist, and its name
   * must carry the hash of this CSS.
   *
   * @param string $key
   *   The storage key.
   * @param string $css
   *   The CSS built for what is being rendered.
   *
   * @return bool
   *   TRUE when the file can be attached in place of the CSS.
   */
  public function matches(string $key, string $css): bool;

  /**
   * Writes the file for a key, then removes older variants.
   *
   * @param string $key
   *   The storage key.
   * @param string $css
   *   The CSS.
   *
   * @return bool
   *   TRUE on success. FALSE for an invalid key or a failed write, logged.
   */
  public function write(string $key, string $css): bool;

  /**
   * Deletes every variant for a key. Missing files count as deleted.
   *
   * @param string $key
   *   The storage key.
   *
   * @return bool
   *   TRUE when no file remains.
   */
  public function delete(string $key): bool;

  /**
   * Deletes every generated file.
   *
   * @return int
   *   The number of files deleted.
   */
  public function deleteAll(): int;

  /**
   * Lists the key of every generated file.
   *
   * @return list<string>
   *   The storage keys.
   */
  public function listKeys(): array;

}
