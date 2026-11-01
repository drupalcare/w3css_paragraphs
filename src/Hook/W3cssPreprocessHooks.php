<?php

declare(strict_types=1);

namespace Drupal\w3css_paragraphs\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Holds the module's one implementation of the paragraph preprocess hook.
 */
final class W3cssPreprocessHooks {

  public function __construct(
    private readonly W3cssDynamicCssHooks $dynamicCss,
    private readonly W3cssHeadingHooks $headings,
  ) {}

  /**
   * Implements hook_preprocess_paragraph().
   *
   * @param array<string, mixed> $variables
   *   The preprocess variables.
   */
  #[Hook('preprocess_paragraph')]
  public function preprocessParagraph(array &$variables): void {
    $this->headings->preprocessParagraph($variables);
    $this->dynamicCss->preprocessParagraph($variables);
  }

  /**
   * Implements hook_preprocess_node().
   *
   * @param array<string, mixed> $variables
   *   The preprocess variables.
   */
  #[Hook('preprocess_node')]
  public function preprocessNode(array &$variables): void {
    $this->dynamicCss->preprocessNode($variables);
  }

}
