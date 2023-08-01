/**
 * @file
 * W3CSS Paragraphs Slideshow.
 *
 * Filename:     w3css-paragraphs-slideshow.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */
(function($, Drupal, once) {
  'use strict';
  Drupal.behaviors.w3cssParagraphsSlideshow = {
    attach: function(settings) {
      var loadingBlocks = document.querySelectorAll('[data-big-pipe-placeholder-id]').length;

      if (!loadingBlocks) {
        let slides = document.querySelectorAll('.w3-slides');
        let slideIndex = 1;
        const getNextItemIndex = (totalSlides) => {
          slideIndex = slideIndex % totalSlides + 1;
          return slideIndex;
        };
        let getPreviousItemIndex = (totalSlides) => {
          slideIndex = slideIndex === 1 ? totalSlides : slideIndex - 1;
          return slideIndex;
        };
        let getParentId = (element) => {
          return parseInt(element.parent().attr('id').split('-')[3]);
        };
        let getElementId = element => parseInt(element.attr('id').split('-')[2]);
        const getSlideIndex = (slides) => {
          for (let i = 0; i < slides.length; i++) {
            const slide = document.getElementById(slides[i].id);
            if (window.getComputedStyle(slide).display === 'block') {
              slideIndex = i + 1;
              break;
            }
          }
          return slideIndex;
        };
        let getNextSlide = (parentId, slideId) => {
          const slidesSelector = `#w3-slides-${parentId}>.w3-s-wr`;
          const currentIdSelector = `#w3-slides-${parentId}>#w3-s-wr-${parentId}-${slideId}`;
          const activeButtonSelector = `#w3-slideshow-${parentId}>.w3-s-nav>#b-nav-${slideId}`;
          const allActiveClassesSelector = `#w3-slideshow-${parentId}>.w3-s-nav>.b-nav`;
          let slides = document.querySelectorAll(slidesSelector);
          let selectedPane = document.querySelector(currentIdSelector);
          let activeButton = document.querySelector(activeButtonSelector);
          let allActiveClasses = document.querySelectorAll(allActiveClassesSelector);
          allActiveClasses.forEach(element => {
            element.classList.remove('w3-is-active');
          });
          slides.forEach(element => {
            element.style.display = 'none';
          });
          return [selectedPane, activeButton];
        };
        const handlePrevNextBtn = (element, itemFunction) => {
          const parentId = getParentId(element);
          const slides = document.querySelectorAll(`#w3-slides-${parentId}>.w3-s-wr`);
          const totalSlides = slides.length;
          const slideId = itemFunction(totalSlides);
          const [nextSlide, isActive] = getNextSlide(parentId, slideId);
          nextSlide.style.display = 'block';
          isActive.classList.add('w3-is-active');
        };
        let handleBottomNav = (element) => {
          const parentId = getParentId(element);
          const slideId = getElementId(element);
          const slideAndActiveState = getNextSlide(parentId, slideId);
          if (slideAndActiveState && slideAndActiveState.length >= 2) {
            slideAndActiveState[0].style.display = 'block';
            slideAndActiveState[1].classList.add('w3-is-active');
            slideIndex = slideId;
          } else {
            console.error('getNextSlide did not return an array with at least two elements');
          }
        };
        const autoSlide = (parentId, itemFunction) => {
          const slides = document.querySelectorAll(`#w3-slides-${parentId}>.w3-s-wr`);
          getSlideIndex(slides);
          const totalSlides = slides.length;
          const slideId = itemFunction(totalSlides);
          const [nextSlide, activeSlide] = getNextSlide(parentId, slideId);
          nextSlide.style.display = 'block';
          activeSlide.classList.add('w3-is-active');
        };
        slides.forEach(slide => {
          let slideId = slide.id;
          let parentId = parseInt(slide.id.split('-')[2]);
          let intervalId = slide.id.replaceAll('-', '');
          let slideTime = parseInt(document.querySelector(`#w3-slideshow-${parentId}>.play-stop-value`).textContent);
          if (slideTime != 0) {
            let stopOnHover = document.getElementById(slideId);
            let playPause = document.getElementById(`btn-${parentId}`);
            let btnClasses = playPause.classList;
            btnClasses.add('no-bottom-nav');
            const autoSlideInterval = () => setInterval(() => autoSlide(parentId, getNextItemIndex), slideTime);
            intervalId = autoSlideInterval();
            const clearAndSetInterval = () => {
              if (playPause.classList.contains('play-active-slideshow')) {
                clearInterval(intervalId);
                intervalId = autoSlideInterval();
              }
            };
            stopOnHover.addEventListener('mouseover', () => {
              if (playPause.classList.contains('play-active-slideshow')) {
                clearInterval(intervalId);
              }
            });
            stopOnHover.addEventListener('mouseout', clearAndSetInterval);
            playPause.addEventListener('click', function() {
              if (this.classList.contains('play-active-slideshow')) {
                this.classList.replace('play-active-slideshow', 'pause-active-slideshow');
                this.innerHTML = '&#9654;';
                clearInterval(intervalId);
              } else {
                this.classList.replace('pause-active-slideshow', 'play-active-slideshow');
                this.innerHTML = '&#10073;&nbsp;&#10073;';
                intervalId = autoSlideInterval();
              }
            });
          }
        });
        const handleClick = (selector, itemFunction) => {
          $(once('.w3-slideshow-inner', selector)).on('click', function() {
            handlePrevNextBtn($(this), itemFunction);
          });
        };
        handleClick('.w3-slideshow-inner .w3-s-next', getNextItemIndex);
        handleClick('.w3-slideshow-inner .w3-s-prev', getPreviousItemIndex);
        $(once('.w3-slideshow-inner', '.w3-slideshow-inner .b-nav')).on('click', event => handleBottomNav($(event.currentTarget)));
      }
    }
  };
})(jQuery, Drupal, once);
