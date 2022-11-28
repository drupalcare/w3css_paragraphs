/**
 * @file
 * W3CSS Paragraphs Modal.
 *
 * Filename:     w3css-paragraphs-modal.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */
(function ($, Drupal, once) {
  'use strict';
  Drupal.behaviors.w3cssParagraphsModal = {
    attach: function (context, settings) {
      // Modal window
      let currWin;
      $(once('.w3-modal-button', '.w3-modal-button>.w3-button', context)).on('click', function () {
        let modalWin, modalBtn;
        modalBtn = this.id;
        modalWin = (modalBtn) + "-win";
        currWin = document.getElementById(modalWin);
        currWin.style.display = "block";
      });
      $(once('.w3-modal-header', '.w3-modal-header>.w3-display-topright', context)).on('click', function () {
        currWin.style.display = "none";
      });
    }
  }
})(jQuery, Drupal, once);
