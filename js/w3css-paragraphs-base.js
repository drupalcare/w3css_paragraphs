/**
 * @file
 * W3CSS Paragraphs.
 *
 * Filename:     w3css-paragraphs-base.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */
(function ($, Drupal) {
  'use strict';

  if ($('.node__content>.field--type-entity-reference-revisions').length > 0) {
    $('.node > .node__content').removeClass('w3-container').addClass('w3-row');
  }

  // Drupal.behaviors.w3cssParagraphsBase = {
  //   attach: function (context, settings) {

  //   }
  // };
})(jQuery, Drupal);
