/**
 * @file
 * W3CSS Paragraphs 3D Carousel
 *
 * Filename:     w3css-paragraphs-3d-carousel.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */
(function($, Drupal, once) {
  'use strict';

  Drupal.behaviors.w3cssParagraphs3dCarousel = {
    attach: function(context, settings) {

      const allCarousel = once('w3cssParagraphs3dCarousel', '.w3-3d-carousel', context);
      if (!allCarousel.length) {
        return;
      }

      let currentWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
      let autoHelper = [];
      let carouselId;
      let n = 0;

      let getCarInfo = (carouselId) => {
        let carousel, cells, cellCount, cellWidth, theta, radius, rawNo, carInfo;
        carousel = context.getElementById(carouselId);
        cells = carousel.querySelectorAll('#' + carouselId + '>.w3-carousel-item');
        cellCount = cells.length;
        cellWidth = carousel.offsetWidth;
        theta = 360 / cellCount;
        radius = Math.round(cellWidth / 2 / Math.tan(Math.PI / cellCount));
        rawNo = carouselId.split('-')[3];
        carInfo = [carousel, cells, cellCount, cellWidth, theta, radius, rawNo];
        return carInfo;
      };
      let getThisId = (th) => {
        let pId = th.attr('id');
        pId = pId.slice(5);
        pId = parseInt(pId);
        pId = ('w3-3d-carousel-' + pId);
        return pId;
      };
      let rotateCarousel = (carouselId, n) => {
        let x = getCarInfo(carouselId);
        let carousel = x[0];
        let cells = x[1];
        let cellCount = x[2];
        let theta = x[4];
        let radius = x[5];
        let angle = theta * n * -1;
        carousel.style.transform = 'translateZ(' + -radius + 'px) rotateY(' + angle + 'deg)';
      };
      let buildCarousel = (carouselId) => {
        let y = getCarInfo(carouselId);
        let itemId = carouselId.split('-')[3];
        let cells = y[1];
        let cellCount = y[2];
        let theta = y[4];
        let radius = y[5];
        let rawNo = y[6];
        for (let i = 0; i < cellCount; i++) {
          if (i < cellCount) {
            cells[i].style.opacity = 1;
            let cellAngle = theta * i;
            let indexNo = context.getElementById('index-' + rawNo);
            let prevBtnId = context.getElementById('prev-' + rawNo);
            let nextBtnId = context.getElementById('next-' + rawNo);
            cells[0].classList.add('w3-is-active');
            indexNo.innerHTML = 1;
            nextBtnId.style.display = 'inline';
            prevBtnId.style.display = 'none';
            cells[i].style.transform = 'rotateY(' + cellAngle + 'deg) translateZ(' + radius + 'px)';
          } else {
            cells[i].style.opacity = 0;
            cells[i].style.transform = "none";
          }
        }
        rotateCarousel(carouselId, n);
      };
      let backward = (carouselId) => {
        let cells = context.querySelectorAll('#' + carouselId + '>.w3-carousel-item');
        let cellCount = Object.keys(cells);
        cellCount = cellCount.length;
        for (let i = 0; i < cellCount; i++) {
          if (cells[i].classList.contains('w3-is-active')) {
            let btnId, prevBtnId, nextBtnId, indexNo;
            btnId = carouselId.split('-')[3];
            prevBtnId = context.getElementById('prev-' + btnId);
            nextBtnId = context.getElementById('next-' + btnId);
            indexNo = context.getElementById('index-' + btnId);
            if (i == 0) {
              let prevOn = parseInt(btnId + 0 + 0);
              let prevOff = parseInt(btnId + 0 + 1);
              let nextOn = parseInt(btnId + 1 + 1);
              let nextOff = parseInt(btnId + 1 + 0);
              if (autoHelper.includes(prevOn)) {
                for (let s = 0; s < autoHelper.length; s++) {
                  if (autoHelper[s] == prevOn) {
                    autoHelper[s] = prevOff;
                  }
                  if (autoHelper[s] == nextOff) {
                    autoHelper[s] = nextOn;
                  }
                }
              }
              break;
            }
            nextBtnId.style.display = 'inline';
            cells[i].classList.remove('w3-is-active');
            i = i - 1;;
            if (cells[i]) {
              if (i == 0) {
                prevBtnId.style.display = 'none';
                nextBtnId.style.display = 'inline';
              };
              cells[i].classList.add('w3-is-active');
              n = i;
              indexNo.innerHTML = n + 1;
              return n;
            };
          } // End of for
        }
      };
      let forward = (carouselId) => {
        let cells = context.querySelectorAll('#' + carouselId + '>.w3-carousel-item');
        let cellCount = Object.keys(cells);
        cellCount = cellCount.length;
        for (let i = 0; i < cellCount; i++) {
          if (cells[i].classList.contains('w3-is-active')) {
            let btnId, prevBtnId, nextBtnId, indexNo;
            btnId = carouselId.split('-')[3];
            prevBtnId = context.getElementById('prev-' + btnId);
            nextBtnId = context.getElementById('next-' + btnId);
            indexNo = context.getElementById('index-' + btnId);
            if (i == cellCount - 1) {
              let prevOn = parseInt(btnId + 0 + 0);
              let prevOff = parseInt(btnId + 0 + 1);
              let nextOn = parseInt(btnId + 1 + 1);
              let nextOff = parseInt(btnId + 1 + 0);
              if (autoHelper.includes(nextOn)) {
                for (let s = 0; s < autoHelper.length; s++) {
                  if (autoHelper[s] == nextOn) {
                    autoHelper[s] = nextOff;
                  }
                  if (autoHelper[s] == prevOff) {
                    autoHelper[s] = prevOn;
                  }
                }
              }
              break;
            }
            prevBtnId.style.display = 'inline';
            cells[i].classList.remove('w3-is-active');
            i = i + 1;
            if (cells[i]) {
              if (i == cellCount - 1) {
                nextBtnId.style.display = 'none';
                prevBtnId.style.display = 'inline';
              };
              cells[i].classList.add('w3-is-active');
              n = i;
              indexNo.innerHTML = n + 1;
              return n;
            }
          }
        } // End of for
      };
      let getNumberId = (th) => {
        let idNu = th.split('-')[3];
        parseInt(idNu);
        return idNu;
      };
      let nextBtn = (carouselId) => {
        n = forward(carouselId);
        rotateCarousel(carouselId, n);
      };
      let prevBtn = (carouselId) => {
        n = backward(carouselId);
        rotateCarousel(carouselId, n);
      };
      let autoPlay = (carouselId) => {
        let d = getNumberId(carouselId);
        let prevOn = parseInt(d + 0 + 0);
        let nextOn = parseInt(d + 1 + 1);
        if ((autoHelper.indexOf(nextOn) >= 0)) {
          n = forward(carouselId);
          rotateCarousel(carouselId, n);
        }
        if ((autoHelper.indexOf(prevOn) >= 0)) {
          n = backward(carouselId);
          rotateCarousel(carouselId, n);
        }
      };
      let buildAll = (allCarousel) => {
        autoHelper = [];
        for (let j = 0; j < allCarousel.length; j++) {
          let carouselC = allCarousel[j];
          carouselId = carouselC.id;
          let shortId = carouselId.split('-')[3];
          autoHelper.push(parseInt(shortId + 1 + 1));
          autoHelper.push(parseInt(shortId + 0 + 1));
          buildCarousel(carouselId);
        }
      };
      for (let j = 0; j < allCarousel.length; j++) {
        let carouselC = allCarousel[j];
        let carouselId = carouselC.id;
        let intervalId = carouselId.replaceAll('-', '');
        let cTimeId = carouselId.split('-')[3];
        let cTime = context.querySelector('#co-' + cTimeId + '>.play-stop-value');
        cTime = cTime.textContent;
        cTime = parseInt(cTime);
        if (cTime != 0) {
          intervalId = setInterval(() => {
            autoPlay(carouselId);
          }, cTime);
          let stopOnHover = context.getElementById(carouselId);
          let playPause = context.getElementById('btn-' + cTimeId);
          playPause.style.display = 'inline';
          stopOnHover.addEventListener('mouseover', function() {
            if (playPause.classList.contains('play-active-carousel')) {
              clearInterval(intervalId);
            }
          });
          stopOnHover.addEventListener('mouseout', function() {
            if (playPause.classList.contains('play-active-carousel')) {
              intervalId = setInterval(() => {
                autoPlay(carouselId);
              }, cTime);
            }
          });
          playPause.addEventListener('click', function() {
            if (this.classList.contains('play-active-carousel')) {
              this.classList.remove('play-active-carousel');
              this.classList.add('pause-active-carousel');
              this.innerHTML = '&#9654;';
              clearInterval(intervalId);
            } else {
              this.classList.remove('pause-active-carousel');
              this.classList.add('play-active-carousel');
              this.innerHTML = '&#10073;&nbsp;&#10073;';
              intervalId = setInterval(() => {
                autoPlay(carouselId);
              }, cTime);
            }
          }); // End playPause
        } // End if
      } // End for
      $(once('.carousel-options', '.carousel-options .next-button', context))
        .on('click', function() {
          carouselId = getThisId($(this));
          nextBtn(carouselId);
        });
      $(once('.carousel-options', '.carousel-options .previous-button', context))
        .on('click', function() {
          carouselId = getThisId($(this));
          prevBtn(carouselId);
        });
      window.onresize = () => {
        n = 0;
        let newWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
        if (Math.abs(newWidth - currentWidth) >= 50) {
          buildAll(allCarousel);
          currentWidth = newWidth;
        }
      };
      buildAll(allCarousel);

    }
  };
})(jQuery, Drupal, once);
