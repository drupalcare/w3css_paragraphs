/**
 * @file
 * W3CSS Paragraphs Tabs.
 *
 * Filename:     w3css-paragraphs-tabs.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */

(function ($, Drupal, once) {
  'use strict';

  $('.w3-tabs-button>button:first-child').addClass('w3-is-active');

  Drupal.behaviors.w3cssParagraphsTabs = {
    attach: function (context, settings) {

      // Tabs
      $(once('.w3-tabs-button', '.w3-tabs-button>.w3-tab-button', context)).on('click', function () {
        let i, x, btnClass, btnId, pneId, currPne, currBtn, pId;
        btnId = this.id;
        pneId = btnId + "-pane";
        currPne = document.getElementById(pneId);
        currBtn = document.getElementById(btnId);
        // get parent ID
        pId = "#w3-tab-" + (btnId.split('-')[2]);
        x = document.querySelectorAll(pId + " .w3-tab-pane");
        for (i = 0; i < x.length; i++) {
          x[i].style.display = "none";
        }
        btnClass = document.querySelectorAll(pId + " .w3-tab-button");
        for (i = 0; i < x.length; i++) {
          btnClass[i].className = btnClass[i].className.replace("w3-is-active", "");
        }
        currPne.style.display = "block";
        currBtn.classList.add("w3-is-active");
      });
    }
  }
})(jQuery, Drupal, once);
