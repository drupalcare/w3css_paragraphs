/**
 * @file
 * W3CSS Paragraphs 3D Carousel
 *
 * Filename:     w3css-paragraphs-3d-carousel.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */
(function (Drupal) {
  'use strict';

  const FORWARD = 'forward';
  const BACKWARD = 'backward';
  const ITEM_ACTIVE = 'w3css-3d-carousel__item--active';

  // Old selector to BEM classes, for templates copied into a theme or a site.
  const LEGACY_HOOKS = {
    '.p-container-inner:has(> .w3-3d-carousel)': 'w3css-3d-carousel',
    '.w3-3d-carousel': 'w3css-3d-carousel__ring',
    '.w3-carousel-item': 'w3css-3d-carousel__item',
    '.w3-carousel-item.w3-is-active': ITEM_ACTIVE,
    '.w3-carousel-item.title-over-image': 'w3css-3d-carousel__item--title-over',
    '.carousel-options': 'w3css-3d-carousel__options',
    '.carousel-options > .w3-row': 'w3css-3d-carousel__controls',
    '.previous-button': 'w3css-3d-carousel__arrow w3css-3d-carousel__arrow--prev',
    '.next-button': 'w3css-3d-carousel__arrow w3css-3d-carousel__arrow--next',
    '.slide-number': 'w3css-3d-carousel__index',
    '.carousel-options .pp': 'w3css-3d-carousel__toggle',
    '.play-active-carousel': 'w3css-3d-carousel__toggle--playing',
    '.pause-active-carousel': 'w3css-3d-carousel__toggle--paused',
    '.carousel-options > .play-stop-value': 'w3css-3d-carousel__interval',
  };

  const getWindowWidth = () =>
    window.innerWidth ||
    document.documentElement.clientWidth ||
    document.body.clientWidth;

  // The carousel stops at its first and last slide. The arrow at an end
  // stays in place but is disabled, so the controls never shift.
  // aria-disabled (not the disabled attribute) keeps a focused arrow
  // focused when the last click brings it to the end.
  const setArrowDisabled = (button, disabled) => {
    button.classList.toggle('w3-is-disabled', disabled);
    button.classList.toggle('w3css-3d-carousel__arrow--disabled', disabled);
    if (disabled) {
      button.setAttribute('aria-disabled', 'true');
    } else {
      button.removeAttribute('aria-disabled');
    }
  };

  /**
   * Sets up one carousel. All state stays per instance.
   *
   * @param {HTMLElement} root
   *   The element carrying the w3css-3d-carousel class.
   * @param {AbortSignal} signal
   *   Aborted when the carousel is detached.
   */
  const initCarousel = (root, signal) => {
    const ring = root.querySelector(':scope > .w3css-3d-carousel__ring');
    // Direct children only: a slide may hold another carousel.
    const cells = ring.querySelectorAll(':scope > .w3css-3d-carousel__item');
    const options = root.querySelector(':scope > .w3css-3d-carousel__options');
    const prevButton = options.querySelector('.w3css-3d-carousel__arrow--prev');
    const nextButton = options.querySelector('.w3css-3d-carousel__arrow--next');
    const indexNumber = options.querySelector('.w3css-3d-carousel__index');
    const cellCount = cells.length;
    let currentWidth = getWindowWidth();
    let direction = FORWARD;

    const getRadius = () =>
      // One slide has no ring: tan(PI) is ~0 and the radius would be ~1e18px,
      // which pushes the slide out of view.
      cellCount > 1
        ? Math.round(ring.offsetWidth / 2 / Math.tan(Math.PI / cellCount))
        : 0;

    const rotate = (slideIndex) => {
      const angle = (360 / cellCount) * slideIndex * -1;
      ring.style.transform = `translateZ(${-getRadius()}px) rotateY(${angle}deg)`;
    };

    const build = () => {
      if (!cellCount) {
        return;
      }
      const theta = 360 / cellCount;
      const radius = getRadius();
      Drupal.w3cssParagraphs.setActive(cells[0], ITEM_ACTIVE, true);
      indexNumber.textContent = '1';
      // With one slide there is nowhere to go either way.
      setArrowDisabled(nextButton, cellCount <= 1);
      setArrowDisabled(prevButton, true);
      cells.forEach((cell, index) => {
        cell.style.opacity = 1;
        cell.style.transform = `rotateY(${theta * index}deg) translateZ(${radius}px)`;
      });
      rotate(0);
    };

    const firstActiveIndex = () =>
      Array.prototype.findIndex.call(cells, (cell) =>
        cell.classList.contains(ITEM_ACTIVE),
      );

    const moveTo = (from, to) => {
      Drupal.w3cssParagraphs.setActive(cells[from], ITEM_ACTIVE, false);
      Drupal.w3cssParagraphs.setActive(cells[to], ITEM_ACTIVE, true);
      indexNumber.textContent = String(to + 1);
    };

    // Returns the new slide index, or undefined at an end, where autoplay
    // turns round instead.
    const forward = () => {
      const active = firstActiveIndex();
      if (active === -1) {
        return undefined;
      }
      if (active === cellCount - 1) {
        direction = BACKWARD;
        return undefined;
      }
      const target = active + 1;
      setArrowDisabled(prevButton, false);
      if (target === cellCount - 1) {
        setArrowDisabled(nextButton, true);
      }
      moveTo(active, target);
      return target;
    };

    const backward = () => {
      const active = firstActiveIndex();
      if (active === -1) {
        return undefined;
      }
      if (active === 0) {
        direction = FORWARD;
        return undefined;
      }
      const target = active - 1;
      setArrowDisabled(nextButton, false);
      if (target === 0) {
        setArrowDisabled(prevButton, true);
      }
      moveTo(active, target);
      return target;
    };

    const rotateIfMoved = (target) => {
      if (target !== undefined) {
        rotate(target);
      }
    };

    // Two separate checks on purpose. Reaching the last slide turns the
    // carousel round and steps back in the same tick; reaching the first
    // slide only turns it round, so the first slide holds for one extra tick.
    const autoPlay = () => {
      if (direction === FORWARD) {
        rotateIfMoved(forward());
      }
      if (direction === BACKWARD) {
        rotateIfMoved(backward());
      }
    };

    const interval = parseInt(
      options.querySelector(':scope > .w3css-3d-carousel__interval')
        .textContent,
      10,
    );

    // Every slide stays in the page, so the number is what announces a move.
    indexNumber.setAttribute('aria-live', 'polite');
    indexNumber.setAttribute('aria-atomic', 'true');

    if (interval !== 0) {
      const toggle = options.querySelector('.w3css-3d-carousel__toggle');
      toggle.style.display = 'inline';
      Drupal.w3cssParagraphs.autoplay({
        interval,
        tick: autoPlay,
        hoverTarget: ring,
        toggle,
        pauseLabel: Drupal.t('Pause carousel'),
        playLabel: Drupal.t('Play carousel'),
        root,
        liveRegion: indexNumber,
        playingClasses: [
          'w3css-3d-carousel__toggle--playing',
          'play-active-carousel',
        ],
        pausedClasses: [
          'w3css-3d-carousel__toggle--paused',
          'pause-active-carousel',
        ],
        signal,
      });
    }

    nextButton.addEventListener(
      'click',
      () => {
        if (nextButton.getAttribute('aria-disabled') !== 'true') {
          rotateIfMoved(forward());
        }
      },
      { signal },
    );

    prevButton.addEventListener(
      'click',
      () => {
        if (prevButton.getAttribute('aria-disabled') !== 'true') {
          rotateIfMoved(backward());
        }
      },
      { signal },
    );

    window.addEventListener(
      'resize',
      () => {
        const newWidth = getWindowWidth();
        if (Math.abs(newWidth - currentWidth) >= 50) {
          direction = FORWARD;
          build();
          currentWidth = newWidth;
        }
      },
      { signal },
    );

    build();
  };

  /**
   * Runs each 3D carousel, with autoplay and previous and next buttons.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.w3cssParagraphs3dCarousel =
    Drupal.w3cssParagraphs.instanceBehavior(
      'w3cssParagraphs3dCarousel',
      '.w3css-3d-carousel',
      initCarousel,
      LEGACY_HOOKS,
    );
})(Drupal);
