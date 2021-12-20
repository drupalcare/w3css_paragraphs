/**
 * @file
 * W3CSS Paragraphs 3D Carousel
 *
 * Filename:     w3css-paragraphs-3d-carousel.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */

(function ($, Drupal) {
  let carouselId;
  'use strict';

  let selectedIndex = 0;
  let oCarouselNo;

   // refresh the page to rebuild the carousel.
  let currentWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
  window.onresize = function () {
    let newWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
    if (Math.abs(newWidth - currentWidth) >= 50) {
      location.reload();
      currentWidth = newWidth;
    }
  }

  function getCarInfo(carouselId) {
    let carInfo, carousel, cells, cellCount, cellWidth, theta, radius;
    carousel = document.getElementById(carouselId);
    cells = carousel.querySelectorAll("#" + carouselId + ">.w3-carousel-item");
    cellCount = cells.length;
    cellWidth = carousel.offsetWidth;
    theta = 360 / cellCount;
    radius = Math.round(cellWidth / 2 / Math.tan(Math.PI / cellCount));

    return carInfo = [carousel, cells, cellCount, cellWidth, theta, radius];
  }

  const rotateCarousel = function (carouselId) {
    let x = getCarInfo(carouselId);
    let carousel = x[0];
    let theta = x[4];
    let radius = x[5];
    let angle = theta * selectedIndex * -1;
    carousel.style.transform = "translateZ(" + -radius + 'px) rotateY(' + angle + 'deg)';

  };

  const buildCarousel = function (carouselId) {
    let y = getCarInfo(carouselId);
    let cells = y[1];
    let cellCount = y[2];
    let theta = y[4];
    let radius = y[5];

    for (let i = 0; i < cellCount; i++) {
      if (i < cellCount) {
        // visible cell
        cells[i].style.opacity = 1;
        let cellAngle = theta * i;
        cells[i].style.transform = "rotateY(" + cellAngle + "deg) translateZ(" + radius + "px)";
      }
      else {
        // hidden cell
        cells[i].style.opacity = 0;
        cells[i].style.transform = "none";
      }
    }
    rotateCarousel(carouselId);
  };

// If there is more than one on the page.
  let allCarousel = document.querySelectorAll(".w3-3d-carousel");
  for (let j = 0; j < allCarousel.length; j++) {
    let carouselC = allCarousel[j];
    carouselId = carouselC.id;
    buildCarousel(carouselId);
  }

  Drupal.behaviors.w3cssParagraphs3dCarousel = {
    attach: function (context, settings) {
      $(context).find('.paragraph--type--w3css-3d-carousel .previous-button').once('.paragraph--type--w3css-3d-carousel .previous-button').on('click', function () {
        let carouselNo, pId;
        pId = $(this).parent().parent().attr('id');
        carouselNo = pId.slice(3);
        carouselNo = parseInt(carouselNo);
        carouselId = ('w3-3d-carousel-' + carouselNo);
        if (oCarouselNo != carouselNo) {
          selectedIndex = 0;
        }
        selectedIndex--;
        rotateCarousel(carouselId);
        oCarouselNo = carouselNo;

      });

      $(context).find('.paragraph--type--w3css-3d-carousel .next-button').once('.paragraph--type--w3css-3d-carousel .next-button').on('click', function () {
        let carouselNo, pId;
        pId = $(this).parent().parent().attr('id');
        carouselNo = pId.slice(3);
        carouselNo = parseInt(carouselNo);
        carouselId = ('w3-3d-carousel-' + carouselNo);
        if (oCarouselNo != carouselNo) {
          selectedIndex = 0;
        }
        selectedIndex++;
        rotateCarousel(carouselId);
        oCarouselNo = carouselNo;

      });

    }
  }
})(jQuery, Drupal);
