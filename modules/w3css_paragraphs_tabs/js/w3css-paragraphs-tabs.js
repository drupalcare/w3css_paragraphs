/**
 * @file
 * W3CSS Paragraphs Tabs.
 *
 * Filename:     w3css-paragraphs-tabs.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */
((Drupal, once) => {
  const ACTIVE = 'w3css-tabs__button--active';

  // Old selector to BEM classes, for templates copied into a theme or a site.
  const LEGACY_HOOKS = {
    '.w3-tabs-wrapper': 'w3css-tabs',
    '.w3-tabs-button': 'w3css-tabs__bar',
    '.w3-tab-button': 'w3css-tabs__button',
    '.w3-tab-button.w3-is-active': ACTIVE,
    '.w3-tabs-pane': 'w3css-tabs__panes',
    '.w3-tab-pane': 'w3css-tabs__pane',
  };

  /**
   * Shows the pane of the clicked tab and hides the others.
   *
   * @param {Event} event
   *   The click on a tab button.
   */
  function selectTab(event) {
    const button = event.currentTarget;
    const tabs = button.closest('.w3css-tabs');
    tabs.querySelectorAll('.w3css-tabs__pane').forEach((pane) => {
      pane.style.display = 'none';
    });
    tabs.querySelectorAll('.w3css-tabs__button').forEach((tabButton) => {
      Drupal.w3cssParagraphs.setActive(tabButton, ACTIVE, false);
      Drupal.w3cssParagraphs.setTabSelected(tabButton, false);
    });
    document.getElementById(`${button.id}-pane`).style.display = 'block';
    Drupal.w3cssParagraphs.setActive(button, ACTIVE, true);
    Drupal.w3cssParagraphs.setTabSelected(button, true);
  }

  /**
   * Moves to and selects a tab from the keyboard; selection follows focus.
   *
   * @param {KeyboardEvent} event
   *   The keydown on a tab button.
   */
  function moveWithKeys(event) {
    const button = event.currentTarget;
    const tabs = [...button.parentElement.children].filter((element) =>
      element.matches('.w3css-tabs__button'),
    );
    const vertical = button.closest('.w3css-tabs').matches('.v-tabs');
    const next = Drupal.w3cssParagraphs.tabFromKey(event, tabs, vertical);
    if (next) {
      event.preventDefault();
      next.focus();
      next.click();
    }
  }

  /**
   * Gives a tab and its pane the semantics old copied templates do not print.
   *
   * @param {HTMLElement} button
   *   The tab button.
   */
  function setSemantics(button) {
    button.type = 'button';
    button.setAttribute('role', 'tab');
    button.parentElement.setAttribute('role', 'tablist');
    if (button.closest('.w3css-tabs').matches('.v-tabs')) {
      button.parentElement.setAttribute('aria-orientation', 'vertical');
    }
    const pane = document.getElementById(`${button.id}-pane`);
    if (pane) {
      button.setAttribute('aria-controls', pane.id);
      pane.setAttribute('role', 'tabpanel');
      pane.setAttribute('aria-labelledby', button.id);
      pane.tabIndex = 0;
    }
  }

  /**
   * Marks the first tab of each set active and makes the tabs switch panes.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.w3cssParagraphsTabs = {
    attach(context) {
      Drupal.w3cssParagraphs.adoptLegacyHooks(context, LEGACY_HOOKS);
      once(
        'w3css-paragraphs-tabs',
        '.w3css-tabs__bar > .w3css-tabs__button',
        context,
      ).forEach((button) => {
        const first = button.matches(':first-child');
        setSemantics(button);
        if (first) {
          Drupal.w3cssParagraphs.setActive(button, ACTIVE, true);
        }
        Drupal.w3cssParagraphs.setTabSelected(button, first);
        button.addEventListener('click', selectTab);
        button.addEventListener('keydown', moveWithKeys);
      });
    },
  };
})(Drupal, once);
