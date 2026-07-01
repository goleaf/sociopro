!(function (e) {
  "use strict";
  ClassicEditor.create(document.querySelector("#editor")).catch((e) => {
    console.error(e);
  }),
    e(".gallery-items").tjGallery({
      row_min_height: 80,
      selector: "img",
      margin: 7,
    }),
    e(".post-img-list").tjGallery({
      row_min_height: 170,
      selector: "img",
      margin: 10,
    }),
    e(".timeline-carousel").owlCarousel({
      loop: !1,
      margin: 10,
      dots: !1,
      nav: !0,
      responsiveClass: !0,
      responsive: { 0: { items: 2 }, 600: { items: 3 }, 767: { items: 4 }, 1e3: { items: 5 } },
    }),
    e(".story-gallery").owlCarousel({
      loop: !1,
      margin: 10,
      dots: !1,
      nav: !0,
      items: 1,
    }),
    e(".st-child-gallery").owlCarousel({
      loop: !1,
      margin: 10,
      dots: !0,
      nav: !1,
      items: 1,
    }),
    e(".piv-gallery").owlCarousel({
      loop: !1,
      margin: 10,
      dots: !1,
      nav: !0,
      items: 1,
    }),
    e(".group-carousel").owlCarousel({ dots: !1, nav: !0, items: 1 }),
    e(".rl-products").owlCarousel({
      loop: !1,
      margin: 20,
      dots: !1,
      nav: !0,
      responsiveClass: !0,
      responsive: { 0: { items: 1 }, 600: { items: 2 }, 1e3: { items: 3 } },
    }),
    e(".video-popup").venobox(),
    e("select").niceSelect();
  e("#event-time").clockpicker({
    placement: "bottom",
    align: "left",
    autoclose: !0,
    default: "now",
  });
  var o = e(".modal-footer .btn");
  o.click(function () {
    var s = e(this).attr("data-tab");
    o.removeClass("current"),
      e(".post-inner").removeClass("current"),
      e(this).addClass("current"),
      e("#" + s).addClass("current");
  }),
    e(".post-inner span.close-btn").on("click", function () {
      e(".post-inner").removeClass("current");
    });
})(jQuery);
//# sourceMappingURL=custom.js.map
