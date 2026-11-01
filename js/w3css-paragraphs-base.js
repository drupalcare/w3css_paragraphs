/**
 * @file
 * W3CSS Paragraphs.
 *
 * Filename:     w3css-paragraphs-base.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */
((Drupal, once) => {
  /**
   * Makes node content full width when the page shows a paragraph field.
   *
   * The test and the change both look at the whole page on purpose: one
   * paragraph field changes every node on it. The selectors are core's node
   * markup, which this module does not own.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.w3cssParagraphsBase = {
    attach() {
      if (
        document.querySelector(
          '.node__content>.field--type-entity-reference-revisions',
        )
      ) {
        once('w3css-paragraphs-base', '.node > .node__content').forEach(
          (content) => {
            content.classList.remove('w3-container');
            content.classList.add('w3-row');
          },
        );
      }
    },
  };
})(Drupal, once);
