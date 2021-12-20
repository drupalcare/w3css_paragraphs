/**
 * @file
 * W3CSS Paragraphs Modal.
 *
 * Filename:     w3css-paragraphs-modal.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */
(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.w3cssParagraphsModal = {
    attach: function (context, settings) {
      // Modal window
      let currWin;
      $(context).find('.w3-modal-button>.w3-button').once('.w3-modal-button>.w3-button').on('click', function () {
        let modalWin, modalBtn;
        modalBtn = this.id;
        modalWin = (modalBtn) + "-win";
        currWin = document.getElementById(modalWin);
        currWin.style.display = "block";
      });
      $(context).find('.w3-modal-header>.w3-display-topright').once('.w3-modal-header>.w3-display-topright').on('click', function () {
        currWin.style.display = "none";
      });

    }
  }
})(jQuery, Drupal);
