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
   * Shows the pane of the clicked tab and hides the others of its own set.
   *
   * A set nested inside a pane keeps its own selection and its own panes.
   *
   * @param {Event} event
   *   The click on a tab button.
   */
  function selectTab(event) {
    const button = event.currentTarget;
    Drupal.w3cssParagraphs
      .tabsOfList(button, '.w3css-tabs__button')
      .forEach((tabButton) => {
        const pane = document.getElementById(`${tabButton.id}-pane`);
        if (pane) {
          pane.style.display = 'none';
        }
        Drupal.w3cssParagraphs.setActive(tabButton, ACTIVE, false);
        Drupal.w3cssParagraphs.setTabSelected(tabButton, false);
      });
    document.getElementById(`${button.id}-pane`).style.display = 'block';
    Drupal.w3cssParagraphs.setActive(button, ACTIVE, true);
    Drupal.w3cssParagraphs.setTabSelected(button, true);
  }

  /**
   * Tells whether a tab's list is laid out as a column right now.
   *
   * A vertical set is a column on wide screens only, so the arrow keys and
   * aria-orientation follow the layout on screen, not the setting alone.
   *
   * @param {HTMLElement} button
   *   A tab button.
   *
   * @return {boolean}
   *   TRUE when the list is a column.
   */
  function isVertical(button) {
    const list = button.parentElement;
    const vertical = getComputedStyle(list).flexDirection === 'column';
    list.setAttribute('aria-orientation', vertical ? 'vertical' : 'horizontal');
    return vertical;
  }

  /**
   * Moves to and selects a tab from the keyboard; selection follows focus.
   *
   * @param {KeyboardEvent} event
   *   The keydown on a tab button.
   */
  function moveWithKeys(event) {
    const button = event.currentTarget;
    const tabs = Drupal.w3cssParagraphs.tabsOfList(
      button,
      '.w3css-tabs__button',
    );
    const vertical = isVertical(button);
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
    isVertical(button);
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
