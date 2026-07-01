(function ($) {
  "use strict";


  const initGalleries = () => {
    $(".gallery-items").tjGallery({
      row_min_height: 80,
      selector: "img",
      margin: 7,
    });

    $(".post-img-list").tjGallery({
      row_min_height: 170,
      selector: "img",
      margin: 10,
    });
  };

  const initOwlCarousels = () => {
    $(".timeline-carousel").owlCarousel({
      loop: false,
      margin: 10,
      dots: false,
      nav: true,
      responsiveClass: true,
      responsive: { 0: { items: 2 }, 600: { items: 3 }, 1000: { items: 5 } },
    });

    $(".story-gallery, .st-child-gallery, .piv-gallery, .group-carousel, .rl-products").each(function () {
      $(this).owlCarousel({
        loop: false,
        margin: 10,
        dots: $(this).hasClass("st-child-gallery"),
        nav: !$(this).hasClass("st-child-gallery"),
        items: 3,
      });
    });
  };

  const initVenobox = () => {
    $(".video-popup").venobox();
  };

  const initClockpicker = () => {
    $("#event-time").clockpicker({
      placement: "bottom",
      align: "left",
      autoclose: true,
      default: "now",
    });
  };

  const initTabNavigation = () => {
    const $modalFooterBtns = $(".modal-footer .btn");

    $modalFooterBtns.on("click", function () {
      const $this = $(this);
      const tab = $this.attr("data-tab");

      $modalFooterBtns.removeClass("current");
      $(".post-inner").removeClass("current");
      $this.addClass("current");
      $("#" + tab).addClass("current");
    });

    $(".post-inner span.close-btn").on("click", function () {
      $(".post-inner").removeClass("current");
    });
  };

  initGalleries();
  initOwlCarousels();
  initVenobox();
  initClockpicker();
  initTabNavigation();

})(jQuery);
