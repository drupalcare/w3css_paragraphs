<?php

declare(strict_types=1);

namespace Drupal\w3css_paragraphs\Service\Css;

use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Psr\Log\LoggerInterface;

/**
 * Default implementation of the CSS file manager.
 *
 * Only files named by this class are ever listed or removed. A site that keeps
 * public files where realpath() cannot reach them finds no file here, so its
 * pages use the inline path rather than a broken link.
 */
final class W3cssCssFileManager implements W3cssCssFileManagerInterface {

  /**
   * Hex length of the hash in a file name.
   */
  private const HASH_LENGTH = 10;

  /**
   * Per-request index of the directory: key to list of file names.
   *
   * One scan answers every paragraph on a page. NULL means not scanned yet.
   *
   * @var array<string, list<string>>|null
   */
  private ?array $index = NULL;

  public function __construct(
    private readonly FileSystemInterface $fileSystem,
    private readonly W3cssCssValidatorInterface $validator,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function fileUri(string $key): string {
    $name = $this->index()[$key][0] ?? NULL;
    return $name === NULL ? '' : self::DIRECTORY . '/' . $name;
  }

  /**
   * {@inheritdoc}
   */
  public function matches(string $key, string $css): bool {
    return ($this->index()[$key] ?? []) === [$this->fileName($key, $css)];
  }

  /**
   * {@inheritdoc}
   */
  public function write(string $key, string $css): bool {
    if (!$this->validator->isStorageKey($key)) {
      $this->logger->error('Refused to write a CSS file: invalid storage key.');
      return FALSE;
    }
    $directory = self::DIRECTORY;
    if (!$this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      $this->logger->error('Could not prepare the CSS directory @dir.', ['@dir' => $directory]);
      return FALSE;
    }
    $name = $this->fileName($key, $css);
    $uri = $directory . '/' . $name;
    try {
      $this->fileSystem->saveData($css, $uri, FileExists::Replace);
    }
    catch (FileException $e) {
      $this->logger->error('Could not write @uri: @message', ['@uri' => $uri, '@message' => $e->getMessage()]);
      return FALSE;
    }
    // Written before the old file goes: a failure above leaves the old file,
    // whose hash no longer matches, so the page falls back to the inline path.
    $remaining = [$name];
    foreach ($this->index()[$key] ?? [] as $old) {
      if ($old !== $name && !$this->deleteFile($directory . '/' . $old)) {
        $remaining[] = $old;
      }
    }
    $this->index[$key] = $remaining;
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(string $key): bool {
    $remaining = [];
    foreach ($this->index()[$key] ?? [] as $name) {
      if (!$this->deleteFile(self::DIRECTORY . '/' . $name)) {
        $remaining[] = $name;
      }
    }
    if ($remaining === []) {
      unset($this->index[$key]);
      return TRUE;
    }
    $this->index[$key] = $remaining;
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll(): int {
    $count = 0;
    foreach ($this->index() as $names) {
      foreach ($names as $name) {
        if ($this->deleteFile(self::DIRECTORY . '/' . $name)) {
          $count++;
        }
      }
    }
    $this->index = NULL;
    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function listKeys(): array {
    return array_map('strval', array_keys($this->index()));
  }

  /**
   * Gives the content-addressed file name.
   *
   * @param string $key
   *   The storage key.
   * @param string $css
   *   The CSS.
   *
   * @return string
   *   The file name.
   */
  private function fileName(string $key, string $css): string {
    return $key . '.' . substr(hash('xxh128', $css), 0, self::HASH_LENGTH) . '.css';
  }

  /**
   * Deletes one file, logging a failure.
   *
   * @param string $uri
   *   The file URI.
   *
   * @return bool
   *   TRUE when the file is gone.
   */
  private function deleteFile(string $uri): bool {
    try {
      $this->fileSystem->delete($uri);
      return TRUE;
    }
    catch (FileException $e) {
      $this->logger->warning('Could not delete @uri: @message', ['@uri' => $uri, '@message' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Scans the directory once per request.
   *
   * @return array<string, list<string>>
   *   File names keyed by storage key. Anything else in the directory is
   *   ignored.
   */
  private function index(): array {
    if ($this->index !== NULL) {
      return $this->index;
    }
    $this->index = [];
    $directory = $this->fileSystem->realpath(self::DIRECTORY);
    if ($directory === FALSE || !is_dir($directory)) {
      return $this->index;
    }
    $names = scandir($directory);
    foreach ($names === FALSE ? [] : $names as $name) {
      if (preg_match('/^(.+)\.[0-9a-f]{' . self::HASH_LENGTH . '}\.css$/D', $name, $matches) === 1
        && $this->validator->isStorageKey($matches[1])
        && is_file($directory . '/' . $name)) {
        $this->index[$matches[1]][] = $name;
      }
    }
    return $this->index;
  }

}
