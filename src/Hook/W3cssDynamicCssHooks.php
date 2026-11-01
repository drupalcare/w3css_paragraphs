<?php

declare(strict_types=1);

namespace Drupal\w3css_paragraphs\Hook;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\w3css_paragraphs\Service\Css\W3cssCssBuilderInterface;
use Drupal\w3css_paragraphs\Service\Css\W3cssCssFileManagerInterface;
use Drupal\w3css_paragraphs\Service\Css\W3cssCssValidatorInterface;
use Drupal\w3css_paragraphs\Service\Css\W3cssDynamicLibraryRegistrarInterface;
use Psr\Log\LoggerInterface;

/**
 * Keeps generated CSS files in step with content, and attaches them.
 */
final class W3cssDynamicCssHooks {

  /**
   * The node bundle with its own template and display values.
   */
  private const NODE_BUNDLE = 'w3css_paragraphs_content';

  public function __construct(
    private readonly W3cssCssFileManagerInterface $files,
    private readonly W3cssCssBuilderInterface $builder,
    private readonly W3cssCssValidatorInterface $validator,
    private readonly W3cssDynamicLibraryRegistrarInterface $registrar,
    private readonly CacheTagsInvalidatorInterface $cacheTagsInvalidator,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Implements hook_paragraph_insert().
   */
  #[Hook('paragraph_insert')]
  public function paragraphInsert(EntityInterface $entity): void {
    $this->writeForEntity($entity);
  }

  /**
   * Implements hook_paragraph_update().
   */
  #[Hook('paragraph_update')]
  public function paragraphUpdate(EntityInterface $entity): void {
    $this->writeForEntity($entity);
  }

  /**
   * Implements hook_paragraph_delete().
   */
  #[Hook('paragraph_delete')]
  public function paragraphDelete(EntityInterface $entity): void {
    $this->deleteForEntity($entity);
  }

  /**
   * Implements hook_node_insert().
   */
  #[Hook('node_insert')]
  public function nodeInsert(EntityInterface $entity): void {
    $this->writeForEntity($entity);
  }

  /**
   * Implements hook_node_update().
   */
  #[Hook('node_update')]
  public function nodeUpdate(EntityInterface $entity): void {
    $this->writeForEntity($entity);
  }

  /**
   * Implements hook_node_delete().
   */
  #[Hook('node_delete')]
  public function nodeDelete(EntityInterface $entity): void {
    $this->deleteForEntity($entity);
  }

  /**
   * Implements hook_entity_update().
   *
   * Publishing a draft can make a host default and leave its paragraphs marked
   * non-default, so no paragraph hook writes their file. The host's references
   * name the revisions that are now public.
   */
  #[Hook('entity_update')]
  public function entityUpdate(EntityInterface $entity): void {
    if ($entity->getEntityTypeId() === 'paragraph' || !$entity instanceof FieldableEntityInterface) {
      return;
    }
    if ($entity instanceof RevisionableInterface && !$entity->isDefaultRevision()) {
      return;
    }
    $this->writeReferencedParagraphs($entity);
  }

  /**
   * Implements hook_library_info_build().
   *
   * @return array<string, array{header: bool, css: array<string, array<string, array<string, mixed>>>}>
   *   Library definitions keyed by library name.
   */
  #[Hook('library_info_build')]
  public function libraryInfoBuild(): array {
    return $this->registrar->buildLibraries();
  }

  /**
   * Implements hook_css_alter().
   *
   * @param array<int|string, array<string, mixed>> $css
   *   The CSS assets.
   */
  #[Hook('css_alter')]
  public function cssAlter(array &$css): void {
    // Matched by our directory and not by a name prefix, which could also
    // catch a static stylesheet.
    $prefix = W3cssCssFileManagerInterface::DIRECTORY . '/';
    foreach ($css as &$asset) {
      if (isset($asset['data']) && is_string($asset['data']) && str_starts_with($asset['data'], $prefix)) {
        $asset['group'] = CSS_AGGREGATE_THEME;
        $asset['weight'] = (float) W3cssDynamicLibraryRegistrarInterface::ASSET_WEIGHT;
      }
    }
  }

  /**
   * Implements hook_module_preuninstall().
   */
  #[Hook('module_preuninstall')]
  public function modulePreuninstall(string $module): void {
    if ($module === 'w3css_paragraphs' && $this->files->deleteAll() > 0) {
      $this->cacheTagsInvalidator->invalidateTags(['library_info']);
    }
  }

  /**
   * Prepares a paragraph's CSS; called from W3cssPreprocessHooks.
   *
   * Any bundle with the fields gets it, so a site's own bundles keep their
   * color. A bundle without them builds no CSS and is left as it is.
   *
   * @param array<string, mixed> $variables
   *   The preprocess variables.
   */
  public function preprocessParagraph(array &$variables): void {
    $paragraph = $variables['paragraph'] ?? NULL;
    if (!$paragraph instanceof FieldableEntityInterface) {
      return;
    }
    $viewMode = $variables['view_mode'] ?? 'default';
    $this->attachDynamicCss($variables, $paragraph, is_string($viewMode) ? $viewMode : 'default');
  }

  /**
   * Prepares a content node's CSS; called from W3cssPreprocessHooks.
   *
   * @param array<string, mixed> $variables
   *   The preprocess variables.
   */
  public function preprocessNode(array &$variables): void {
    $node = $variables['node'] ?? NULL;
    if (!$node instanceof FieldableEntityInterface || $node->bundle() !== self::NODE_BUNDLE) {
      return;
    }
    $viewMode = $variables['view_mode'] ?? 'default';
    $this->attachDynamicCss($variables, $node, is_string($viewMode) ? $viewMode : 'default');
  }

  /**
   * Writes an existing entity's file, whatever hook state it was saved in.
   *
   * For the post-update. A paragraph's file comes from its default revision; a
   * host then also writes the revisions it references, which wins where a
   * published host points at a paragraph revision marked non-default.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The default revision of a paragraph or other host.
   */
  public function rebuildForEntity(FieldableEntityInterface $entity): void {
    $this->writeCss($entity);
    if ($entity->getEntityTypeId() !== 'paragraph') {
      $this->writeReferencedParagraphs($entity);
    }
  }

  /**
   * Prepares an entity's CSS for its template.
   *
   * The library is used only when the file holds exactly the CSS built for
   * what is being rendered. A draft, preview, translation or missing file uses
   * the page head instead. An entity with no CSS gets no id, as in 1.x.
   *
   * @param array<string, mixed> $variables
   *   The preprocess variables. Sets `w3css_element_id` and the `id` attribute
   *   when there is CSS, and may add to `#attached`.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The paragraph or node being rendered.
   * @param string $viewMode
   *   The view mode.
   */
  public function attachDynamicCss(array &$variables, FieldableEntityInterface $entity, string $viewMode = 'default'): void {
    $variables['w3css_element_id'] = NULL;
    $elementId = $this->builder->elementId($entity);
    if ($elementId === NULL) {
      return;
    }
    $css = $this->builder->build($entity, $viewMode);
    if ($css === '') {
      return;
    }
    $key = $this->builder->storageKey($entity);
    if ($key !== NULL && $this->files->matches($key, $css)) {
      $variables['#attached']['library'][] = $this->registrar->libraryId($key);
      $this->setElementId($variables, $entity, $elementId);
      return;
    }
    // Markup::create() marks the string safe, so html_tag prints it as it is:
    // this is the only unescaped sink in the module.
    if (!$this->validator->isSafeCss($css)) {
      $this->logger->error('Refused to print CSS for @id: it contained an unexpected "<".', ['@id' => $elementId]);
      return;
    }
    $this->setElementId($variables, $entity, $elementId);
    $variables['#attached']['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'style',
        '#value' => Markup::create($css),
        '#attributes' => ['data-w3css-dynamic' => $elementId],
      ],
      'w3css-dynamic-' . $elementId,
    ];
  }

  /**
   * Gives the outer element the id its CSS is scoped to.
   *
   * @param array<string, mixed> $variables
   *   The preprocess variables.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity being rendered.
   * @param string $elementId
   *   The element id, built from validated parts.
   */
  private function setElementId(array &$variables, FieldableEntityInterface $entity, string $elementId): void {
    $variables['w3css_element_id'] = $elementId;
    // These templates give the outer element an id of their own, and print
    // this one with a suffix on the inner element that takes the color. The
    // node template prints it on its wrapper, not on the article.
    if ($entity->getEntityTypeId() === 'node' || $this->builder->colorsInnerElementOnly($entity->bundle())) {
      return;
    }
    // Works on the array core passes and on an Attribute object, if another
    // preprocess already made one, because Attribute is array-accessible.
    $variables['attributes']['id'] = $elementId;
  }

  /**
   * Writes the file for a saved entity, or removes it when there is no CSS.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The saved entity.
   */
  private function writeForEntity(EntityInterface $entity): void {
    if (!$entity instanceof FieldableEntityInterface) {
      return;
    }
    // A draft must not replace the published look.
    if ($entity instanceof RevisionableInterface && !$entity->isDefaultRevision()) {
      return;
    }
    $this->writeCss($entity);
  }

  /**
   * Writes the files of the paragraphs a host references, nested ones included.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $host
   *   The host, in the revision that is public.
   */
  private function writeReferencedParagraphs(FieldableEntityInterface $host): void {
    foreach ($host->getFieldDefinitions() as $name => $definition) {
      if ($definition->getType() !== 'entity_reference_revisions') {
        continue;
      }
      foreach ($host->get($name) as $item) {
        $paragraph = $item->entity ?? NULL;
        if ($paragraph instanceof FieldableEntityInterface && $paragraph->getEntityTypeId() === 'paragraph') {
          $this->writeCss($paragraph);
          $this->writeReferencedParagraphs($paragraph);
        }
      }
    }
  }

  /**
   * Writes an entity's public file, or removes it when there is no CSS.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity, in the revision that is public.
   */
  private function writeCss(FieldableEntityInterface $entity): void {
    // The file belongs to the default translation, whichever one was saved.
    // Another language with different values uses the inline path.
    if ($entity instanceof TranslatableInterface) {
      $entity = $entity->getUntranslated();
    }
    $key = $this->builder->storageKey($entity);
    if ($key === NULL) {
      return;
    }
    $before = $this->files->fileUri($key);
    // Built as anonymous: the file is public, so a restricted media's URL stays
    // out of it. A user who may see that image gets a different hash at render
    // and so the page head, which is not a public file.
    $css = $this->builder->build($entity, 'default', new AnonymousUserSession());
    if ($css !== '' && $this->files->matches($key, $css)) {
      return;
    }
    if ($css === '') {
      if ($before !== '') {
        $this->files->delete($key);
        $this->cacheTagsInvalidator->invalidateTags(['library_info']);
      }
      return;
    }
    if (!$this->validator->isSafeCss($css)) {
      $this->logger->error('Refused to write CSS for @key: it contained an unexpected "<".', ['@key' => $key]);
      return;
    }
    $this->files->write($key, $css);
    if ($this->files->fileUri($key) !== $before) {
      $this->cacheTagsInvalidator->invalidateTags(['library_info']);
    }
  }

  /**
   * Removes the file of a deleted entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The deleted entity.
   */
  private function deleteForEntity(EntityInterface $entity): void {
    if (!$entity instanceof FieldableEntityInterface) {
      return;
    }
    $key = $this->builder->storageKey($entity);
    if ($key === NULL || $this->files->fileUri($key) === '') {
      return;
    }
    $this->files->delete($key);
    $this->cacheTagsInvalidator->invalidateTags(['library_info']);
  }

}
