(function ($) {

  /**
   * Copyright 2012, Digital Fusion
   * Licensed under the MIT license.
   * http://teamdf.com/jquery-plugins/license/
   *
   * @author Sam Sehnert
   * @desc A small plugin that checks whether elements are within
   *     the user visible viewport of a web browser.
   *     only accounts for vertical position, not horizontal.
   */

  $.fn.visible = function (partial) {

    var $t = $(this),
      $w = $(window),
      viewTop = $w.scrollTop(),
      viewBottom = viewTop + $w.height(),
      _top = $t.offset().top,
      _bottom = _top + $t.height(),
      compareTop = partial === true ? _bottom : _top,
      compareBottom = partial === true ? _top : _bottom;

    return ((compareBottom <= viewBottom) && (compareTop >= viewTop));

  };

})(jQuery);




$(window).on('scroll', function () {

  $(".service-style1__top-title .sec-title, .service-style1__top-title .text-box, .single-blog-style1, .certificates-area .top-title, .slogan-area .button, .video-gallery-style1__video-holder h2, .features-style1__bg .icon").each(function (i, el) {
    var el = $(el);
    if (el.visible(true)) {
      el.addClass("now-in-view");
    } else {
      el.removeClass("now-in-view");
    }
  });

});