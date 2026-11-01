/**
 * @file
 * W3CSS Paragraphs Modal.
 *
 * Filename:     w3css-paragraphs-modal.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */
((Drupal, once) => {
  // Old selector to BEM classes, for templates copied into a theme or a site.
  const LEGACY_HOOKS = {
    '.w3-modal-button': 'w3css-modal-trigger',
    '.w3-modal-button > .w3-padding-large': 'w3css-modal-trigger__button',
    '.w3-modal[id$="-win"]': 'w3css-modal',
    '.w3-modal-header': 'w3css-modal__header',
    '.w3-modal-header > .w3-display-topright': 'w3css-modal__close',
  };

  const FOCUSABLE =
    'a[href], area[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), iframe, summary, [tabindex]:not([tabindex="-1"])';

  // The button that opened each dialog, which gets focus back on close.
  const openers = new WeakMap();

  /**
   * Lists what Tab can reach inside a dialog right now.
   *
   * @param {HTMLElement} dialog
   *   The dialog.
   *
   * @return {HTMLElement[]}
   *   The focusable elements on show, in document order.
   */
  function tabStops(dialog) {
    return [...dialog.querySelectorAll(FOCUSABLE)].filter(
      (element) =>
        element.getClientRects().length > 0 &&
        window.getComputedStyle(element).visibility !== 'hidden',
    );
  }

  /**
   * Opens a dialog and moves focus into it.
   *
   * @param {HTMLElement} dialog
   *   The dialog.
   * @param {HTMLElement} opener
   *   The button that opened it.
   */
  function openDialog(dialog, opener) {
    openers.set(dialog, opener);
    dialog.style.display = 'block';
    dialog.removeAttribute('aria-hidden');
    const [first] = tabStops(dialog);
    if (first) {
      first.focus();
    } else {
      dialog.setAttribute('tabindex', '-1');
      dialog.focus();
    }
  }

  /**
   * Closes a dialog and returns focus to the button that opened it.
   *
   * @param {HTMLElement} dialog
   *   The dialog.
   */
  function closeDialog(dialog) {
    dialog.style.display = 'none';
    openers.get(dialog)?.focus();
  }

  /**
   * Closes on Escape and keeps Tab inside the dialog, as the APG dialog does.
   *
   * @param {KeyboardEvent} event
   *   The key press. A nested dialog answers first and stops it here.
   */
  function onDialogKeydown(event) {
    const dialog = event.currentTarget;
    if (event.key === 'Escape') {
      event.stopPropagation();
      closeDialog(dialog);
    } else if (event.key === 'Tab') {
      event.stopPropagation();
      const stops = tabStops(dialog);
      const edge = event.shiftKey ? stops[0] : stops[stops.length - 1];
      if (!stops.length || document.activeElement === edge) {
        event.preventDefault();
        stops[event.shiftKey ? stops.length - 1 : 0]?.focus();
      }
    }
  }

  /**
   * Makes the modal buttons open their window and the close buttons shut it.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.w3cssParagraphsModal = {
    attach(context) {
      Drupal.w3cssParagraphs.adoptLegacyHooks(context, LEGACY_HOOKS);
      once(
        'w3css-paragraphs-modal',
        '.w3css-modal-trigger__button',
        context,
      ).forEach((button) => {
        button.addEventListener('click', () => {
          openDialog(document.getElementById(`${button.id}-win`), button);
        });
      });
      once('w3css-paragraphs-modal', '.w3css-modal__close', context).forEach(
        (button) => {
          button.addEventListener('click', () => {
            closeDialog(button.closest('.w3css-modal'));
          });
        },
      );
      once('w3css-paragraphs-modal', '.w3css-modal', context).forEach(
        (dialog) => {
          dialog.addEventListener('keydown', onDialogKeydown);
        },
      );
    },
  };
})(Drupal, once);
