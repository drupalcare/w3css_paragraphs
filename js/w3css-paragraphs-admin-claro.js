/**
 * @file
 * W3CSS Paragraphs.
 *
 * Filename:     w3css-paragraphs-admin-claro.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */
(function ($, Drupal) {
  'use strict';

  function getColor(x, y = 0) {
    for (let i = 0; i < x.length; i++) {
      let currVal = ($(x[i]).val());
      if (y > 0) {
        currVal = 'w3' + currVal.slice(y);
      }
      x[i].parentNode.classList.add(currVal);
      x[i].nextElementSibling.classList.add(currVal);
    }
  }

  function bgContent() {
    if ('.w3css-tab-display .form-type--radio>.form-radio') {
      let x = document.querySelectorAll(".field--name-w3css-paragraphs-node-bg .form-type--radio>.form-radio");
      getColor(x);
    }
  }

  function bgCurrent() {
    if ('.w3css-tab-display .form-type--radio>.form-radio') {
      let x = document.querySelectorAll(".field--name-w3css-display-bg-color .form-type--radio>.form-radio");
      getColor(x);
    }
  }

  function bgHover() {
    if ('.w3css-tab-display .form-type--radio>.form-radio') {
      let x = document.querySelectorAll(".field--name-w3css-display-hover-bg .form-type--radio>.form-radio");
      getColor(x, 8);
    }
  }

  function txtColor() {
    if ('.w3css-tab-display .form-type--radio>.form-radio') {
      let x = document.querySelectorAll(".field--name-w3css-display-text-color .form-type--radio>.form-radio");
      getColor(x, 7);
    }
  }

  function txtHover() {
    if ('.w3css-tab-display .form-type--radio>.form-radio') {
      let x = document.querySelectorAll(".field--name-w3css-display-hover-text .form-type--radio>.form-radio");
      getColor(x, 13);
    }
  }

  function borderColor() {
    if ('.w3css-tab-display .form-type--radio>.form-radio') {
      let x = document.querySelectorAll(".field--name-w3css-display-border-color .form-type--radio>.form-radio");
      getColor(x, 9);
    }
  }

  function borderHover() {
    if ('.w3css-tab-display .form-type--radio>.form-radio') {
      let x = document.querySelectorAll(".field--name-w3css-display-hover-border .form-type--radio>.form-radio");
      getColor(x, 15);
    }
  }

  function bgActive() {
    if ('.w3css-tab-display .form-type--radio>.form-radio') {
      let x = document.querySelectorAll(".field--name-w3css-display-active-item .form-type--radio>.form-radio");
      getColor(x, 12);
    }
  }

  Drupal.behaviors.w3cssParagraphsAdmin = {
    attach: function (context, settings) {
      $(context)
        .find('.field--type-entity-reference-revisions')
        .once('.field--type-entity-reference-revisions')
        .on('click', function () {
          bgContent();
          bgCurrent();
          bgHover();
          txtColor();
          txtHover();
          borderColor();
          borderHover();
          bgActive();
        });
      bgContent();
      bgCurrent();
      bgHover();
      txtColor();
      txtHover();
      borderColor();
      borderHover();
      bgActive();
    }
  };

})(jQuery, Drupal);
