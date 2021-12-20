/**
 * @file
 * W3CSS Paragraphs Slideshow.
 *
 * Filename:     w3css-paragraphs-slideshow.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */
(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.w3cssParagraphsSlideshow = {
    attach: function (context, settings) {
      // Slideshow
      let n = 1;

      function nextItem(pId) {
        n = n + 1;
        if ((n) == (pId + 1)) {
          n = 1
        }
        return n;
      }

      function prevItem(pId) {
        n = n - 1;
        if (n === 0) {
          n = pId;
        }
        return n;
      }

      function getNextSlide(pId, sId) {
        let currId, x, y, z, m;
        let sIds = [];
        currId = "#w3-slideshow-" + pId + ">#w3-s-wr-" + pId + "-" + sId;
        // Total of all slides
        x = document.querySelectorAll("#w3-slideshow-" + pId + ">.w3-s-wr");
        // Selected pane
        y = document.querySelector(currId);
        // Add Active button class
        z = document.querySelector("#w3-slideshow-" + pId + ">.w3-s-nav>#b-nav-" + sId);
        // Remove all active classes.
        m = document.querySelectorAll("#w3-slideshow-" + pId + ">.w3-s-nav>.b-nav");
        for (let j = 0; j < m.length; j++) {
          m[j].className = m[j].className.replace("w3-is-active", "");
        }

        for (let i = 0; i < x.length; i++) {
          x[i].style.display = "none";
        }
        sIds[0] = y;
        sIds[1] = z;
        return sIds;
      }

      function getParentId(th) {
        let pId;
        pId = th.parent().parent().attr('id');
        pId = pId.split('-')[2];
        pId = parseInt(pId);
        return pId;
      }

      function getThisId(th) {
        let sId;
        sId = th.split('-')[2];
        sId = parseInt(sId);
        return sId;
      }

      $(context).find('.w3-slideshow-inner>.w3-s-nav>button').once('.w3-slideshow-inner>.w3-s-nav>button').on('click', function () {
        let pId, sId, th_is;
        let as = [];
        th_is = $(this);
        pId = getParentId(th_is);
        sId = getThisId(this.id)
        as = getNextSlide(pId, sId);
        as[0].style.display = "block";
        as[1].classList.add("w3-is-active");
        n = sId;
      });
      $(context).find('.w3-slideshow-inner>.w3-s-np>.w3-s-next').once('.w3-slideshow-inner>.w3-s-np>.w3-s-next').on('click', function () {
        let pId, sId, x, tSlides, th_is, as;
        th_is = $(this);
        pId = getParentId(th_is);
        // Get how many item in the slides
        x = document.querySelectorAll("#w3-slideshow-" + pId + ">.w3-s-wr");
        tSlides = x.length;
        sId = nextItem(tSlides);
        as = getNextSlide(pId, sId);
        as[0].style.display = "block";
        as[1].classList.add("w3-is-active");
      });
      $(context).find('.w3-slideshow-inner>.w3-s-np>.w3-s-prev').once('.w3-slideshow-inner>.w3-s-np>.w3-s-prev').on('click', function () {
        let pId, sId, x, tSlides, th_is, as;
        th_is = $(this);
        pId = getParentId(th_is);
        // Get how many item in the slides
        x = document.querySelectorAll("#w3-slideshow-" + pId + ">.w3-s-wr");
        tSlides = x.length;
        sId = prevItem(tSlides);
        as = getNextSlide(pId, sId);
        as[0].style.display = "block";
        as[1].classList.add("w3-is-active");
      });

    }
  }
})(jQuery, Drupal);
