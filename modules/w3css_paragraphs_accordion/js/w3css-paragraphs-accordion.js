/**
 * @file
 * W3CSS Paragraphs Accordion.
 *
 * Filename:     w3css-paragraphs-accordion.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.w3cssParagraphsAccordion = {
    attach: function (context, settings) {

      // Accordion
      // Global helper
      function getMyId(classId) {
        const myIds = [];
        myIds[0] = document.querySelectorAll("#" + classId + ">.wrapper-accs-btn-pane");
        myIds[1] = document.querySelector("#" + classId + ">.ex-button>button.minus");
        myIds[2] = document.querySelector("#" + classId + ">.ex-button>button.plus");
        return myIds;
      }

      $(context).find('.w3-accs-button>button').once('.w3-accs-button>button').on('click', function () {
        let listClass, classId, sectionId, x;
        classId = $(this).parent().parent().attr('id');
        sectionId = $(this).parent().parent().parent().attr('id');
        x = getMyId(sectionId);
        listClass = document.getElementById(classId).classList;
        // Open
        if (listClass.contains('w3-not-active')) {
          document.getElementById(classId).className = document.getElementById(classId).className.replace("w3-not-active", "w3-is-active");
          $('#' + sectionId).each(function () {
            if (!$(this).children('.w3-not-active').length) {
              x[1].style.display = "block";
              x[2].style.display = "none";
            }
          });
        }
        else {
          // close
          document.getElementById(classId).className = document.getElementById(classId).className.replace("w3-is-active", "w3-not-active");
          $('#' + sectionId).each(function () {
            if (!$(this).children('.w3-is-active').length) {
              x[2].style.display = "block";
              x[1].style.display = "none";
            }
          });
        }
      });
      $(context).find('.ex-button>button.plus').once('.ex-button>button.plus').on('click', function () {
        let classId, s;
        classId = $(this).parent().parent().attr('id');
        s = getMyId(classId);
        for (let j = 0; j < s[0].length; j++) {
          s[0][j].className = s[0][j].className.replace("w3-not-active", "w3-is-active");
        }
        s[2].style.display = "none";
        s[1].style.display = "block";
      });
      $(context).find('.ex-button>button.minus').once('.ex-button>button.minus').on('click', function () {
        let classId, s;
        classId = $(this).parent().parent().attr('id');
        s = getMyId(classId);
        for (let j = 0; j < s[0].length; j++) {
          s[0][j].className = s[0][j].className.replace("w3-is-active", "w3-not-active");
        }
        s[1].style.display = "none";
        s[2].style.display = "block";
      });

    }
  }
})(jQuery, Drupal);
