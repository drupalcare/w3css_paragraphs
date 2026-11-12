<?php

declare(strict_types=1);

namespace Drupal\w3css_paragraphs\Theme;

use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Answers one question: is this request rendered by a W3CSS theme?
 *
 * "W3CSS theme" means drupal8_w3css_theme itself or any theme with it among
 * its base themes, at any depth. The module ships its own copy of the W3.CSS
 * library only for the other themes, so that a W3CSS site has one copy.
 *
 * Nothing here throws: an unknown or uninstalled theme name is simply not a
 * W3CSS theme, and a base theme loop ends in "no".
 */
final class W3cssThemeDetection {

  /**
   * The W3CSS theme's machine name.
   */
  public const THEME = 'drupal8_w3css_theme';

  /**
   * Cache contexts every answer of isActive() varies by.
   */
  public const CACHE_CONTEXTS = ['theme'];

  /**
   * Remembered isInHierarchy() answers, by theme name.
   *
   * @var array<string, bool>
   */
  private array $hierarchyCache = [];

  public function __construct(
    private readonly ThemeManagerInterface $themeManager,
    private readonly ThemeHandlerInterface $themeHandler,
  ) {}

  /**
   * Whether the theme rendering this request is a W3CSS theme.
   *
   * Core switches the active theme mid-request, so the active name is read on
   * every call. Callers must add CACHE_CONTEXTS to what they render.
   */
  public function isActive(): bool {
    return $this->isInHierarchy($this->themeManager->getActiveTheme()->getName());
  }

  /**
   * Whether a theme is the W3CSS theme or has it as a base theme.
   *
   * @param string $theme_name
   *   A theme machine name.
   */
  public function isInHierarchy(string $theme_name): bool {
    if ($theme_name === '') {
      return FALSE;
    }
    if (isset($this->hierarchyCache[$theme_name])) {
      return $this->hierarchyCache[$theme_name];
    }

    // A visited list, not recursion: two themes naming each other as base
    // theme must end in "no", never in a stack overflow.
    $visited = [];
    $pending = [$theme_name];
    while ($pending !== []) {
      $name = array_pop($pending);
      if (!is_string($name) || isset($visited[$name])) {
        continue;
      }
      $visited[$name] = TRUE;
      if ($name === self::THEME) {
        return $this->hierarchyCache[$theme_name] = TRUE;
      }
      // getTheme() throws for a theme it does not know, and a base theme in an
      // .info.yml need not be installed.
      if ($this->themeHandler->themeExists($name)) {
        array_push($pending, ...array_values((array) ($this->themeHandler->getTheme($name)->info['base theme'] ?? [])));
      }
    }

    return $this->hierarchyCache[$theme_name] = FALSE;
  }

}
