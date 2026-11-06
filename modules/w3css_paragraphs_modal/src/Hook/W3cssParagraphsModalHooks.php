<?php

namespace Drupal\w3css_paragraphs_modal\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for w3css_paragraphs_modal.
 */
class W3cssParagraphsModalHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   *
   * @return string|\Stringable|null
   *   The help text, or NULL when there is none.
   */
  #[Hook('help')]
  public function help(string $route_name, RouteMatchInterface $route_match): ?string {
    if ($route_name !== 'help.page.w3css_paragraphs_modal') {
      return NULL;
    }
    $output = '<h2>' . $this->t('About') . '</h2>';
    $output .= '<p>' . $this->t('The W3CSS Paragraphs Modal module provides the <em>Modal</em> paragraph type, built with the W3.CSS framework. For more information, see the <a href=":project">online documentation for the W3CSS Paragraphs module</a>.', [
      ':project' => 'https://www.drupal.org/project/w3css_paragraphs',
    ]) . '</p>';
    $output .= '<h2>' . $this->t('Uses') . '</h2>';
    $output .= '<dl>';
    $output .= '<dt>' . $this->t('Adding the Modal paragraph type') . '</dt>';
    $output .= '<dd>' . $this->t('On a content type, add a Paragraphs field (<em>Structure</em> > <em>Content types</em> > <em>Manage fields</em>) and allow the <em>Modal</em> paragraph type in it. Then add it when you edit content.') . '</dd>';
    $output .= '<dt>' . $this->t('Styling it') . '</dt>';
    $output .= '<dd>' . $this->t('The paragraph form has display settings for background, text and border colors (with hover colors), background opacity, width, borders, rounded corners, margin, padding, a card shadow and extra W3.CSS classes. Some paragraph types have more or fewer of these settings.') . '</dd>';
    $output .= '</dl>';
    return $output;
  }

  /**
   * Implements hook_theme().
   *
   * @param array<string, mixed> $existing
   *   The existing theme hooks.
   * @param string $type
   *   The type of the extension.
   * @param string $theme
   *   The name of the extension.
   * @param string $path
   *   The path of the extension.
   *
   * @return array<string, array<string, mixed>>
   *   The theme hook definitions.
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'paragraph__w3css_modal' => [
        'base hook' => 'paragraph',
      ],
    ];
  }

}
