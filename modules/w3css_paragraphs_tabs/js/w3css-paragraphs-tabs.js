/**
 * @file
 * W3CSS Paragraphs Tabs.
 *
 * Filename:     w3css-paragraphs-tabs.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */
((Drupal, once) => {
  'use strict';
  const firstChildButtons = document.querySelectorAll('.w3-tabs-button>button:first-child');
  firstChildButtons.forEach(button => button.classList.add('w3-is-active'));

  Drupal.behaviors.w3cssParagraphsTabs = {
    attach: (context, settings) => {
      const hideAllPanes = panes => panes.forEach(pane => pane.style.display = "none");

      const removeActiveClassFromButtons = buttons => buttons.forEach(button => button.classList.remove("w3-is-active"));

      const buttons = document.querySelectorAll('.w3-tabs-button>.w3-tab-button');
      buttons.forEach(button => {
        button.addEventListener('click', () => {
          const { id: btnId } = button;
          const pneId = `${btnId}-pane`;
          const currPne = document.getElementById(pneId);
          const currBtn = document.getElementById(btnId);
          const pId = `#w3-tab-${btnId.split('-')[2]}`;
          const tabPanes = document.querySelectorAll(`${pId} .w3-tab-pane`);
          const tabButtons = document.querySelectorAll(`${pId} .w3-tab-button`);
          hideAllPanes(tabPanes);
          removeActiveClassFromButtons(tabButtons);
          currPne.style.display = "block";
          currBtn.classList.add("w3-is-active");
        });
      });
    }
  }
})(Drupal, once);

