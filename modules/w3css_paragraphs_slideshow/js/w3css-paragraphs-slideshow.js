/**
 * @file
 * W3CSS Paragraphs Slideshow.
 *
 * Filename:     w3css-paragraphs-slideshow.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */
(function (Drupal) {
  'use strict';

  const NAV_ACTIVE = 'w3css-slideshow__nav-button--active';

  // Old selector to BEM classes, for templates copied into a theme or a site.
  const LEGACY_HOOKS = {
    '.w3-slideshow-inner': 'w3css-slideshow',
    '.w3-slides': 'w3css-slideshow__slides',
    '.w3-s-wr': 'w3css-slideshow__slide',
    '.w3-s-wr > .w3-s-pane': 'w3css-slideshow__pane',
    '.w3-s-pane > .w3-s-pane': 'w3css-slideshow__panel',
    '.w3-s-title': 'w3css-slideshow__title',
    '.w3-s-nav': 'w3css-slideshow__nav',
    '.w3-s-nav.btns-On': 'w3css-slideshow__nav--on',
    '.w3-s-nav.btns-Off': 'w3css-slideshow__nav--off',
    '.b-nav': 'w3css-slideshow__nav-button',
    '.b-nav.w3-is-active': NAV_ACTIVE,
    '.w3-s-nav > .pp': 'w3css-slideshow__toggle',
    '.play-active-slideshow': 'w3css-slideshow__toggle--playing',
    '.pause-active-slideshow': 'w3css-slideshow__toggle--paused',
    '.w3-s-np': 'w3css-slideshow__arrows',
    '.w3-s-np.btns-On': 'w3css-slideshow__arrows--on',
    '.w3-s-np.btns-Off': 'w3css-slideshow__arrows--off',
    '.w3-s-prev': 'w3css-slideshow__arrow w3css-slideshow__arrow--prev',
    '.w3-s-next': 'w3css-slideshow__arrow w3css-slideshow__arrow--next',
    '.w3-slideshow-inner > .play-stop-value': 'w3css-slideshow__interval',
  };

  /**
   * Sets up one slideshow. All state stays per instance.
   *
   * @param {HTMLElement} root
   *   The element carrying the w3css-slideshow class.
   * @param {AbortSignal} signal
   *   Aborted when the slideshow is detached.
   */
  const initSlideshow = (root, signal) => {
    // Direct children only: a slide may hold another slideshow.
    const slides = root.querySelectorAll(
      ':scope > .w3css-slideshow__slides > .w3css-slideshow__slide',
    );
    const navButtons = root.querySelectorAll(
      ':scope > .w3css-slideshow__nav > .w3css-slideshow__nav-button',
    );
    let n = 1;

    const nextItem = (total) => {
      n += 1;
      if (n === total + 1) {
        n = 1;
      }
      return n;
    };

    const prevItem = (total) => {
      n -= 1;
      if (n === 0) {
        n = total;
      }
      return n;
    };

    // The current slide is whichever one the browser shows: themes may set it.
    const syncCurrent = () => {
      slides.forEach((slide, index) => {
        if (window.getComputedStyle(slide).display === 'block') {
          n = index + 1;
        }
      });
    };

    const show = (slideNumber) => {
      const slide = slides[slideNumber - 1];
      if (!slide) {
        return;
      }
      navButtons.forEach((button, index) => {
        const current = index === slideNumber - 1;
        Drupal.w3cssParagraphs.setActive(button, NAV_ACTIVE, current);
        if (current) {
          button.setAttribute('aria-current', 'true');
        } else {
          button.removeAttribute('aria-current');
        }
      });
      slides.forEach((item) => {
        item.style.display = 'none';
      });
      slide.style.display = 'block';
    };

    const step = (pick) => {
      syncCurrent();
      show(pick(slides.length));
    };

    const interval = parseInt(
      root.querySelector(':scope > .w3css-slideshow__interval').textContent,
      10,
    );

    const slidesWrapper = root.querySelector(
      ':scope > .w3css-slideshow__slides',
    );
    slidesWrapper.setAttribute('aria-live', 'polite');

    if (interval !== 0) {
      const toggle = root.querySelector(
        ':scope > .w3css-slideshow__nav > .w3css-slideshow__toggle',
      );
      toggle.classList.add('no-bottom-nav', 'w3css-slideshow__toggle--visible');
      Drupal.w3cssParagraphs.autoplay({
        interval,
        tick: () => step(nextItem),
        hoverTarget: slidesWrapper,
        toggle,
        pauseLabel: Drupal.t('Pause slideshow'),
        playLabel: Drupal.t('Play slideshow'),
        root,
        liveRegion: slidesWrapper,
        playingClasses: [
          'w3css-slideshow__toggle--playing',
          'play-active-slideshow',
        ],
        pausedClasses: [
          'w3css-slideshow__toggle--paused',
          'pause-active-slideshow',
        ],
        signal,
      });
    }

    root
      .querySelector(
        ':scope > .w3css-slideshow__arrows > .w3css-slideshow__arrow--next',
      )
      .addEventListener('click', () => step(nextItem), { signal });

    root
      .querySelector(
        ':scope > .w3css-slideshow__arrows > .w3css-slideshow__arrow--prev',
      )
      .addEventListener('click', () => step(prevItem), { signal });

    navButtons.forEach((button, index) => {
      button.addEventListener(
        'click',
        () => {
          show(index + 1);
          n = index + 1;
        },
        { signal },
      );
    });
  };

  /**
   * Runs each slideshow, with autoplay, previous, next and numbered buttons.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.w3cssParagraphsSlideshow =
    Drupal.w3cssParagraphs.instanceBehavior(
      'w3css-paragraphs-slideshow',
      '.w3css-slideshow',
      initSlideshow,
      LEGACY_HOOKS,
    );
})(Drupal);
