/**
 * @file
 * W3CSS Paragraphs Accordion.
 *
 * Filename:     w3css-paragraphs-accordion.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */
((Drupal, once) => {
  'use strict';
  Drupal.behaviors.w3cssParagraphsAccordion = {
    attach: (settings) => {

      // work around big pipe https://www.drupal.org/node/2794099
      // https://drupal.stackexchange.com/a/314250
      // https://www.drupal.org/project/drupal/issues/3272693
      let loadingBlocks = document.querySelectorAll('[data-big-pipe-placeholder-id]').length;
      if (!loadingBlocks) {


        const getMyId = (classId) => {

          const [wrapperAccsBtnPane, minusButton, plusButton] = [

              document.querySelectorAll(`#${classId} > .wrapper-accs-btn-pane`),
              document.querySelector(`#${classId} > .ex-button > button.minus`),
              document.querySelector(`#${classId} > .ex-button > button.plus`),
            ];

          return [wrapperAccsBtnPane, minusButton, plusButton];

        };

        document.querySelectorAll('.w3-accs-button, .w3-accs-button>button').forEach((button) => {

          button.addEventListener('click', function() {

            let classId = this.parentElement.parentElement.id;
            let sectionId = this.parentElement.parentElement.parentElement.id;
            let x = getMyId(sectionId);
            let element = document.getElementById(classId);
            let section = document.getElementById(sectionId);

            if (element.classList.contains('w3-not-active')) {

              element.classList.replace('w3-not-active', 'w3-is-active');

              Array.from(section.children).forEach((child) => {

                if (!child.classList.contains('w3-not-active')) {
                  x[1].style.display = "block";
                  x[2].style.display = "none";
                }

              });

            } else {
              element.classList.replace('w3-is-active', 'w3-not-active');

              Array.from(section.children).forEach((child) => {

                if (!child.classList.contains('w3-is-active')) {
                  if (x[2]) {
                    x[2].style.display = "block";
                    x[1].style.display = "none";
                  }
                }
              });

            }
          });
        });

        const toggleClassAndDisplay = (buttonSelector, activeClass, inactiveClass, displayIndex1, displayIndex2) => {

          let buttons = document.querySelectorAll(buttonSelector);

          buttons.forEach((button) => {

            button.addEventListener('click', function() {

              let classId = this.parentNode.parentNode.getAttribute('id');
              let s = getMyId(classId);

              for (let j = 0; j < s[0].length; j++) {
                s[0][j].className = s[0][j].className.replace(inactiveClass, activeClass);
              }

              s[displayIndex1].style.display = "none";
              s[displayIndex2].style.display = "block";

            });

          });
        }

        toggleClassAndDisplay('.ex-button>button.plus', 'w3-is-active', 'w3-not-active', 2, 1);
        toggleClassAndDisplay('.ex-button>button.minus', 'w3-not-active', 'w3-is-active', 1, 2);

      }
    }
  }
})(Drupal, once);
