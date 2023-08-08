/**
 * @file
 * W3CSS Paragraphs Modal.
 *
 * Filename:     w3css-paragraphs-modal.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */
((Drupal, once) => {
  'use strict';

  Drupal.behaviors.w3cssParagraphsModal = {
    attach: (context, settings) => {
      let currWin;

      const showModal = function () {
        let modalBtn = this.id;
        let modalWin = `${modalBtn}-win`;
        currWin = document.getElementById(modalWin);
        currWin.style.display = "block";
      };

      const hideModal = function () {
        currWin.style.display = "none";
      };

      let modalButtons = once('.w3-modal-button', '.w3-modal-button>.w3-button', context);
      modalButtons.forEach(button => button.addEventListener('click', showModal));

      let modalHeaders = once('.w3-modal-header', '.w3-modal-header>.w3-display-topright', context);
      modalHeaders.forEach(header => header.addEventListener('click', hideModal));
    }
  }
})(Drupal, once);
