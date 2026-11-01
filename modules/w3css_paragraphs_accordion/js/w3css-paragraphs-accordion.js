/**
 * @file
 * W3CSS Paragraphs Accordion.
 *
 * Filename:     w3css-paragraphs-accordion.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */
((Drupal, once) => {
  const ACTIVE = 'w3css-accordion__item--active';

  // Old selector to BEM classes, for templates copied into a theme or a site.
  const LEGACY_HOOKS = {
    '.w3-row:has(> .wrapper-accs-btn-pane)': 'w3css-accordion',
    '.ex-button': 'w3css-accordion__expand-all',
    '.ex-button > .plus': 'w3css-accordion__expand',
    '.ex-button > .minus': 'w3css-accordion__collapse',
    '.wrapper-accs-btn-pane': 'w3css-accordion__item',
    '.wrapper-accs-btn-pane.w3-is-active': ACTIVE,
    '.w3-accs-button': 'w3css-accordion__header',
    '.w3-acc-button': 'w3css-accordion__button',
    '.plus-minus': 'w3css-accordion__indicator',
    '.plus-minus > .minus': 'w3css-accordion__indicator-minus',
    '.plus-minus > .plus': 'w3css-accordion__indicator-plus',
    '.w3-accs-pane': 'w3css-accordion__panel',
  };

  /**
   * Shows the expand-all or the collapse-all button, when the set has them.
   *
   * @param {HTMLElement} accordion
   *   The accordion.
   * @param {boolean} allOpen
   *   TRUE when every section is open, so collapse-all is the one to offer.
   */
  function showExpandButton(accordion, allOpen) {
    const expand = accordion.querySelector(
      ':scope > .w3css-accordion__expand-all > .w3css-accordion__expand',
    );
    const collapse = accordion.querySelector(
      ':scope > .w3css-accordion__expand-all > .w3css-accordion__collapse',
    );
    if (expand && collapse) {
      expand.style.display = allOpen ? 'none' : 'block';
      collapse.style.display = allOpen ? 'block' : 'none';
    }
  }

  /**
   * Opens or closes one section and tells assistive technology.
   *
   * @param {HTMLElement} item
   *   The section.
   * @param {boolean} open
   *   TRUE to open the section.
   */
  function setSection(item, open) {
    Drupal.w3cssParagraphs.setActive(item, ACTIVE, open);
    item.classList.toggle('w3-not-active', !open);
    item
      .querySelector(':scope > .w3css-accordion__header > button')
      .setAttribute('aria-expanded', String(open));
  }

  /**
   * Opens or closes the section of the clicked button.
   *
   * @param {Event} event
   *   The click on a section button.
   */
  function toggleSection(event) {
    const item = event.currentTarget.closest('.w3css-accordion__item');
    const accordion = item.closest('.w3css-accordion');
    if (item.classList.contains(ACTIVE)) {
      setSection(item, false);
      if (!accordion.querySelector(`:scope > .${ACTIVE}`)) {
        showExpandButton(accordion, false);
      }
    } else {
      setSection(item, true);
      if (
        !accordion.querySelector(
          `:scope > .w3css-accordion__item:not(.${ACTIVE})`,
        )
      ) {
        showExpandButton(accordion, true);
      }
    }
  }

  /**
   * Moves every section of an accordion to one state.
   *
   * @param {HTMLElement} button
   *   The expand-all or collapse-all button that was clicked.
   * @param {boolean} open
   *   TRUE to open every section, FALSE to close every section.
   */
  function setAllSections(button, open) {
    const accordion = button.closest('.w3css-accordion');
    accordion
      .querySelectorAll(':scope > .w3css-accordion__item')
      .forEach((item) => setSection(item, open));
    showExpandButton(accordion, open);
    // The clicked button is hidden now: keyboard focus must not fall to the
    // page start, so it moves to the button that took its place.
    accordion
      .querySelector(
        `:scope > .w3css-accordion__expand-all > ${
          open ? '.w3css-accordion__collapse' : '.w3css-accordion__expand'
        }`,
      )
      .focus();
  }

  /**
   * Makes the section buttons and the expand-all buttons work.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.w3cssParagraphsAccordion = {
    attach(context) {
      Drupal.w3cssParagraphs.adoptLegacyHooks(context, LEGACY_HOOKS);
      once(
        'w3css-paragraphs-accordion',
        '.w3css-accordion__button',
        context,
      ).forEach((button) => {
        button.setAttribute(
          'aria-expanded',
          String(
            button
              .closest('.w3css-accordion__item')
              .classList.contains(ACTIVE),
          ),
        );
        button.addEventListener('click', toggleSection);
      });
      once(
        'w3css-paragraphs-accordion',
        '.w3css-accordion__expand',
        context,
      ).forEach((button) => {
        button.addEventListener('click', () => setAllSections(button, true));
      });
      once(
        'w3css-paragraphs-accordion',
        '.w3css-accordion__collapse',
        context,
      ).forEach((button) => {
        button.addEventListener('click', () => setAllSections(button, false));
      });
    },
  };
})(Drupal, once);
