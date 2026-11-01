/**
 * @file
 * Functions used by more than one W3CSS Paragraphs script.
 *
 * Filename:     w3css-paragraphs-shared.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */
((Drupal) => {
  /**
   * Shared functions of the W3CSS Paragraphs scripts.
   *
   * @namespace
   */
  Drupal.w3cssParagraphs = {};

  /**
   * Adds BEM classes to markup that only has the old names.
   *
   * The scripts select only BEM classes. A template copied into a theme or a
   * site before those classes existed prints only the old names, and keeps
   * working through this.
   *
   * @param {Document|Element} context
   *   Where to look.
   * @param {Object<string, string>} hooks
   *   Old selector to the BEM classes it gets, separated by spaces.
   */
  Drupal.w3cssParagraphs.adoptLegacyHooks = (context, hooks) => {
    Object.entries(hooks).forEach(([selector, classes]) => {
      context.querySelectorAll(selector).forEach((element) => {
        element.classList.add(...classes.split(' '));
      });
    });
  };

  /**
   * Sets the active state, which is always the two classes together.
   *
   * @param {HTMLElement} element
   *   The element.
   * @param {string} modifier
   *   The element's own BEM modifier, written beside w3-is-active.
   * @param {boolean} active
   *   TRUE to mark the element active.
   */
  Drupal.w3cssParagraphs.setActive = (element, modifier, active) => {
    element.classList.toggle('w3-is-active', active);
    element.classList.toggle(modifier, active);
  };

  /**
   * Sets a tab's selected state and its place in the roving tabindex.
   *
   * @param {HTMLElement} tab
   *   The element with role tab.
   * @param {boolean} selected
   *   TRUE when this is the selected tab of its list.
   */
  Drupal.w3cssParagraphs.setTabSelected = (tab, selected) => {
    tab.setAttribute('aria-selected', selected ? 'true' : 'false');
    tab.tabIndex = selected ? 0 : -1;
  };

  /**
   * Finds the tab a key moves to, per the WAI-ARIA tabs pattern.
   *
   * Arrow keys wrap around. Left and Right swap in a right-to-left list, and a
   * vertical list uses Up and Down instead.
   *
   * @param {KeyboardEvent} event
   *   The keydown on a tab.
   * @param {HTMLElement[]} tabs
   *   The tabs of the list, in order.
   * @param {boolean} vertical
   *   TRUE for a vertical list.
   *
   * @return {HTMLElement|null}
   *   The tab to move to, or NULL when the key does not move.
   */
  Drupal.w3cssParagraphs.tabFromKey = (event, tabs, vertical) => {
    const rtl = getComputedStyle(event.currentTarget).direction === 'rtl';
    let forward = 'ArrowRight';
    let backward = 'ArrowLeft';
    if (vertical) {
      forward = 'ArrowDown';
      backward = 'ArrowUp';
    } else if (rtl) {
      forward = 'ArrowLeft';
      backward = 'ArrowRight';
    }
    const index = tabs.indexOf(event.currentTarget);
    switch (event.key) {
      case forward:
        return tabs[(index + 1) % tabs.length];
      case backward:
        return tabs[(index - 1 + tabs.length) % tabs.length];
      case 'Home':
        return tabs[0];
      case 'End':
        return tabs[tabs.length - 1];
      default:
        return null;
    }
  };

  /**
   * Builds a behavior that sets up each instance of a component once.
   *
   * @param {string} onceId
   *   The once() id, also the marker removed on detach.
   * @param {string} selector
   *   Selects the root element of each instance.
   * @param {function(HTMLElement, AbortSignal): void} init
   *   Sets up one instance. The signal aborts when the instance is unloaded.
   * @param {Object<string, string>} legacyHooks
   *   Passed to adoptLegacyHooks() on every attach.
   *
   * @return {Drupal~behavior}
   *   The behavior.
   */
  Drupal.w3cssParagraphs.instanceBehavior = (
    onceId,
    selector,
    init,
    legacyHooks,
  ) => {
    const controllers = new WeakMap();
    return {
      attach(context) {
        Drupal.w3cssParagraphs.adoptLegacyHooks(context, legacyHooks);
        once(onceId, selector, context).forEach((root) => {
          const controller = new AbortController();
          controllers.set(root, controller);
          init(root, controller.signal);
        });
      },
      detach(context, settings, trigger) {
        if (trigger !== 'unload') {
          return;
        }
        once.remove(onceId, selector, context).forEach((root) => {
          const controller = controllers.get(root);
          if (controller) {
            controller.abort();
            controllers.delete(root);
          }
        });
      },
    };
  };

  /**
   * Runs a repeating timer with pause on hover and a play and pause button.
   *
   * @param {object} options
   *   The options.
   * @param {number} options.interval
   *   Milliseconds between ticks.
   * @param {function(): void} options.tick
   *   Runs on every tick.
   * @param {HTMLElement} options.hoverTarget
   *   Hovering this element holds the timer.
   * @param {HTMLElement} options.toggle
   *   The play and pause button.
   * @param {string[]} options.playingClasses
   *   Classes on the button while playing. The first one is read as the state.
   * @param {string[]} options.pausedClasses
   *   Classes on the button while paused.
   * @param {string} options.pauseLabel
   *   The button's accessible name while playing.
   * @param {string} options.playLabel
   *   The button's accessible name while paused.
   * @param {HTMLElement} options.root
   *   Keyboard focus entering this element pauses, until the button resumes.
   * @param {HTMLElement} options.liveRegion
   *   Announces changes only while paused.
   * @param {AbortSignal} options.signal
   *   Stops the timer and removes the listeners when aborted.
   */
  Drupal.w3cssParagraphs.autoplay = ({
    interval,
    tick,
    hoverTarget,
    toggle,
    playingClasses,
    pausedClasses,
    pauseLabel,
    playLabel,
    root,
    liveRegion,
    signal,
  }) => {
    let intervalId = null;
    const isPlaying = () => toggle.classList.contains(playingClasses[0]);
    const play = () => {
      clearInterval(intervalId);
      intervalId = setInterval(tick, interval);
    };
    const stop = () => clearInterval(intervalId);
    const pause = () => {
      toggle.classList.remove(...playingClasses);
      toggle.classList.add(...pausedClasses);
      toggle.textContent = '▶';
      toggle.setAttribute('aria-label', playLabel);
      liveRegion.setAttribute('aria-live', 'polite');
      stop();
    };
    const resume = () => {
      toggle.classList.remove(...pausedClasses);
      toggle.classList.add(...playingClasses);
      toggle.textContent = '❙ ❙';
      toggle.setAttribute('aria-label', pauseLabel);
      liveRegion.setAttribute('aria-live', 'off');
      play();
    };

    toggle.setAttribute('aria-label', pauseLabel);
    liveRegion.setAttribute('aria-live', 'off');
    play();
    signal.addEventListener('abort', stop);
    // A click focuses too, and must not pause just before it toggles: only
    // keyboard focus, which is what :focus-visible matches on a button, counts.
    root.addEventListener(
      'focusin',
      (event) => {
        if (isPlaying() && event.target.matches(':focus-visible')) {
          pause();
        }
      },
      { signal },
    );
    hoverTarget.addEventListener(
      'mouseover',
      () => {
        if (isPlaying()) {
          stop();
        }
      },
      { signal },
    );
    hoverTarget.addEventListener(
      'mouseout',
      () => {
        if (isPlaying()) {
          play();
        }
      },
      { signal },
    );
    toggle.addEventListener(
      'click',
      () => {
        if (isPlaying()) {
          pause();
        } else {
          resume();
        }
      },
      { signal },
    );
  };
})(Drupal);
