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
    attach: function(context, settings) {

      const allSlides = once('w3cssParagraphsSlideshow', '.w3-slides', context);
      if (!allSlides.length) {
        return;
      }

      let n = 1;

      let nextItem = (a) => {
        n = n + 1;
        if ((n) == (a + 1)) n = 1;
        return n;
      };

      let prevItem = (a) => {
        n = n - 1;
        if (n === 0) n = a;
        return n;
      };

      let getParentId = (th) => {
        let pId = th.parent()
          .attr('id');
        pId = pId.split('-')[3];
        pId = parseInt(pId);
        return pId;
      };

      let getThisId = (th) => {
        let sId = th.attr('id');
        sId = sId.split('-')[2];
        sId = parseInt(sId);
        return sId;
      };

      // When clicked, get total slides and
      // which slide number has display block
      let getN = (k) => {
        for (let j = 0; j < k.length; j++) {
          let xj = context.getElementById(k[j].id);
          if (window.getComputedStyle(xj)
            .display === 'block') n = j + 1;
        }
      };

      let getNextSlide = (pId, sId) => {
        let currId, x, y, z, m;
        let sIds = [];
        currId = '#w3-slides-' + pId + '>#w3-s-wr-' + pId + '-' + sId;
        // Total of all slides
        x = context.querySelectorAll('#w3-slides-' + pId + '>.w3-s-wr');
        // Selected pane
        y = context.querySelector(currId);
        // Add Active button class
        z = context.querySelector('#w3-slideshow-' + pId + '>.w3-s-nav>#b-nav-' + sId);
        // Remove all active classes.
        m = context.querySelectorAll('#w3-slideshow-' + pId + '>.w3-s-nav>.b-nav');

        for (let j = 0; j < m.length; j++) {
          m[j].className = m[j].className.replace('w3-is-active', '');
        }

        for (let i = 0; i < x.length; i++) {
          x[i].style.display = 'none';
        }

        sIds[0] = y;
        sIds[1] = z;
        return sIds;
      };

      let prevNextBtn = (th_is, fucn) => {
        let pId, sId, x, tSlides, as;
        pId = getParentId(th_is);
        x = context.querySelectorAll('#w3-slides-' + pId + '>.w3-s-wr');
        getN(x);
        tSlides = x.length;
        sId = fucn(tSlides);
        as = getNextSlide(pId, sId);
        as[0].style.display = 'block';
        as[1].classList.add('w3-is-active');
      };

      let bottomNav = (th_is) => {
        let pId, sId;
        let as = [];
        pId = getParentId(th_is);
        sId = getThisId(th_is);
        as = getNextSlide(pId, sId);
        as[0].style.display = 'block';
        as[1].classList.add('w3-is-active');
        n = sId;
      };

      let autoSlide = (pId, fucn) => {
        let sId, x, tSlides, as;
        x = context.querySelectorAll('#w3-slides-' + pId + '>.w3-s-wr');
        getN(x);
        tSlides = x.length;
        sId = fucn(tSlides);
        as = getNextSlide(pId, sId);
        as[0].style.display = 'block';
        as[1].classList.add('w3-is-active');
      };

      for (let i = 0; i < allSlides.length; i++) {
        let slideId = allSlides[i].id;
        let pId = allSlides[i].id;
        let intervalId = allSlides[i].id;
        intervalId = intervalId.replaceAll('-', '');
        pId = pId.split('-')[2];
        pId = parseInt(pId);
        let sTime = context.querySelector('#w3-slideshow-' + pId + '>.play-stop-value');
        sTime = sTime.textContent;
        sTime = parseInt(sTime);

        if (sTime != 0) {

          let stopOnHover = context.getElementById(slideId);
          let playPause = context.getElementById('btn-' + pId);
          let btnClasses = playPause.classList;
          btnClasses.add('no-bottom-nav');

          intervalId = setInterval(() => {
            autoSlide(pId, nextItem);
          }, sTime);

          stopOnHover.addEventListener('mouseover', function() {
            if (playPause.classList.contains('play-active-slideshow')) {
              clearInterval(intervalId);
            }
          });

          stopOnHover.addEventListener('mouseout', function() {
            if (playPause.classList.contains('play-active-slideshow')) {
              intervalId = setInterval(() => {
                autoSlide(pId, nextItem);
              }, sTime);
            }
          });

          playPause.addEventListener('click', function() {
            if (this.classList.contains('play-active-slideshow')) {
              this.classList.remove('play-active-slideshow');
              this.classList.add('pause-active-slideshow');
              this.innerHTML = '&#9654;';
              clearInterval(intervalId);
            } else {
              this.classList.remove('pause-active-slideshow');
              this.classList.add('play-active-slideshow');
              this.innerHTML = '&#10073;&nbsp;&#10073;';
              intervalId = setInterval(() => {
                autoSlide(pId, nextItem);
              }, sTime);
            }
          }); // End playPause

        } // End if
      } // End for

      // Buttons

      $(once('.w3-slideshow-inner', '.w3-slideshow-inner .w3-s-next', context))
        .on('click', function() {
          prevNextBtn($(this), nextItem);
        });

      $(once('.w3-slideshow-inner', '.w3-slideshow-inner .w3-s-prev', context))
        .on('click', function() {
          prevNextBtn($(this), prevItem);
        });

      $(once('.w3-slideshow-inner', '.w3-slideshow-inner .b-nav', context))
        .on('click', function() {
          bottomNav($(this));
        });

    }
  };
})(jQuery, Drupal, once);
