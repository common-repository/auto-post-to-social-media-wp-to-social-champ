jQuery(document).ready(function($) {
    "use strict";

  //===== Header Search =====//
  $('.srch-btn').on('click', function () {
    $('.header-search').addClass('active');
    return false;
  });
  $('.srch-cls-btn').on('click', function () {
    $('.header-search').removeClass('active');
    return false;
  });


  //===== Select =====//
  if ($('select').length > 0) {
    $('select').selectpicker();
  }


  //=== Testimonials Carousel ===//
  $('.testi-car').owlCarousel({
    autoplay: true,
    smartSpeed: 2000,
    loop: true,
    items: 1,
    dots: false,
    slideSpeed: 15000,
    autoplayHoverPause: true,
    nav: false,
    margin: 0,
    animateIn: 'fadeIn',
    animateOut: 'fadeOut',
    navText: [
    "<i class='fa fa-angle-up'></i>",
    "<i class='fa fa-angle-down'></i>"
    ]
  });
    

});


//===== Sticky Header =====//
$(window).on('scroll',function () {
  'use strict';

  var header_height = $('header').innerHeight();

  var scroll = $(window).scrollTop();
  if (scroll >= header_height) {
    $('header').addClass('sticky-active');
  } else {
    $('header').removeClass('sticky-active');
  }
});


//Window Load jQuery Code
$(window).on('load',function(){
  'use strict';
  $('.pageloader-wrap').fadeOut('slow');

});
