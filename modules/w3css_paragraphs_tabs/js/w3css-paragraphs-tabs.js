/**
 * @file
 * W3CSS Paragraphs Tabs.
 *
 * Filename:     w3css-paragraphs-tabs.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */
(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.w3cssParagraphsTabs = {
    attach: function (context, settings) {
      // Ensuring the first child button has the 'w3-is-active' class only once
      $('.w3-tabs-button > button:first-child', context).once('w3tabs-active').addClass('w3-is-active');

      // Handling tab click events
      $('.w3-tabs-button > .w3-tab-button', context).once('w3tabs-click').on('click', function () {
        var btnId = this.id;
        var pneId = btnId + "-pane";
        var pId = "#w3-tab-" + btnId.split('-')[2];
        var x = $(pId + " .w3-tab-pane");
        var btnClass = $(pId + " .w3-tab-button");

        // Hide all tab panes
        x.hide();

        // Remove active class from all buttons
        btnClass.removeClass("w3-is-active");

        // Show the current pane and add active class to the button
        $('#' + pneId).show();
        $(this).addClass("w3-is-active");
      });
    }
  }
})(jQuery, Drupal);

