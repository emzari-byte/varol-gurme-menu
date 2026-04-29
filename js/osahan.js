/*
Template Name: Sakafo - Online Food Ordering & Restaurant Website Mobile Template
Author: Askbootstrap
Author URI: https://themeforest.net/user/askbootstrap
Version: 1.0
*/
(function($) {
  "use strict";

  // Tooltip
  $('[data-toggle="tooltip"]').tooltip();

  // Select Quantity
  $('.plus').on('click', function() {
    var $input = $(this).prev();
    if ($input.val()) {
      $input.val(+($input.val()) + 1);
    }
  });

  $('.minus').on('click', function() {
    var $input = $(this).next();
    if ($input.val() > 1) {
      $input.val(+($input.val()) - 1);
    }
  });

  // Offer Slider
  if ($('.offer-slider').length && !$('.offer-slider').hasClass('slick-initialized')) {
    $('.offer-slider').slick({
      centerMode: true,
      slidesToShow: 2,
      centerPadding: '30px',
      slidesToScroll: 2,
      autoplay: true,
      autoplaySpeed: 2000,
      arrows: false,
      dots: false
    });
  }

  // Category Slider
  // Premium görünüm için cat-slider doğal horizontal scroll kullanıyor.
  // Bu yüzden burada slick initialize edilmiyor.

  // Trending Slider
  if ($('.trending-slider').length) {
    if ($('.trending-slider').hasClass('slick-initialized')) {
      $('.trending-slider').slick('unslick');
    }

    $('.trending-slider').slick({
      dots: false,
      arrows: false,
      infinite: false,
      speed: 300,
      slidesToShow: 1,
      slidesToScroll: 1,
      adaptiveHeight: false,
      centerMode: false,
      variableWidth: false,
      mobileFirst: true,
      responsive: [
        {
          breakpoint: 576,
          settings: {
            slidesToShow: 2,
            slidesToScroll: 1,
            centerMode: false,
            variableWidth: false
          }
        },
        {
          breakpoint: 992,
          settings: {
            slidesToShow: 2,
            slidesToScroll: 1,
            centerMode: false,
            variableWidth: false
          }
        }
      ]
    });
  }

  // Osahan Slider
  if ($('.osahan-slider').length && !$('.osahan-slider').hasClass('slick-initialized')) {
    $('.osahan-slider').slick({
      centerMode: false,
      slidesToShow: 1,
      arrows: false,
      dots: true
    });
  }

  // Osahan Slider Map
  if ($('.osahan-slider-map').length && !$('.osahan-slider-map').hasClass('slick-initialized')) {
    $('.osahan-slider-map').slick({
      centerMode: true,
      centerPadding: '30px',
      slidesToShow: 2,
      arrows: false,
      responsive: [
        {
          breakpoint: 768,
          settings: {
            arrows: false,
            centerMode: true,
            centerPadding: '40px',
            slidesToShow: 3
          }
        },
        {
          breakpoint: 480,
          settings: {
            arrows: false,
            centerMode: true,
            centerPadding: '40px',
            slidesToShow: 3
          }
        }
      ]
    });
  }

  // Nav
  var $main_nav = $('#main-nav');
  var $toggle = $('.toggle');

  if ($main_nav.length && typeof $main_nav.hcOffcanvasNav === 'function') {
    var defaultOptions = {
      disableAt: false,
      customToggle: $toggle,
      levelSpacing: 40,
      navTitle: 'Varol Gurme',
      levelTitles: true,
      levelTitleAsBack: true,
      pushContent: '#container',
      insertClose: 2
    };

    $main_nav.hcOffcanvasNav(defaultOptions);
  }

  // Flag Number
  var input = document.querySelector("#phone");
  /*
  if (input && typeof window.intlTelInput === 'function') {
    window.intlTelInput(input, {
      preferredCountries: ['tr', 'us'],
      utilsScript: "build/js/utils.js"
    });
  }
  */

})(jQuery);