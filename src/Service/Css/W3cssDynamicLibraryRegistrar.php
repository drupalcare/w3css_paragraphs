<?php

declare(strict_types=1);

namespace Drupal\w3css_paragraphs\Service\Css;

/**
 * Default implementation of the dynamic library registrar.
 */
final class W3cssDynamicLibraryRegistrar implements W3cssDynamicLibraryRegistrarInterface {

  public function __construct(
    private readonly W3cssCssFileManagerInterface $files,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function libraryId(string $key): string {
    return 'w3css_paragraphs/' . self::LIBRARY_PREFIX . $key;
  }

  /**
   * {@inheritdoc}
   */
  public function buildLibraries(): array {
    $libraries = [];
    foreach ($this->files->listKeys() as $key) {
      $uri = $this->files->fileUri($key);
      if ($uri === '') {
        continue;
      }
      // The public:// URI and not a URL: the library parser treats a leading
      // slash as docroot-relative, which breaks sites in a subdirectory.
      $libraries[self::LIBRARY_PREFIX . $key] = [
        'header' => FALSE,
        'css' => [
          'theme' => [
            $uri => [
              'weight' => self::ASSET_WEIGHT,
              'minified' => TRUE,
            ],
          ],
        ],
      ];
    }
    return $libraries;
  }

}
