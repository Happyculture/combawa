(function (Drupal, debounce, $) {

  function handleResize() {
    $('.off-canvas-menu__button:not(.off-canvas-menu__button--close)').each(function () {
      var $target = $('#' + $(this).attr('aria-controls'));
      // If the control button is hidden, show the target.
      if (!$(this).is(':visible')) {
        $target.removeAttr('aria-hidden');
        $(this).parent().find('.off-canvas-menu__button')
          .attr('aria-expanded', 'true');
      }
      // If the button is visible and the canvas has not been opened by a
      // previous click, hide it the canvas.
      else if (!$(this).data('off-canvas-open-by-click')) {
        $target.attr('aria-hidden', 'true');
        $(this).parent().find('.off-canvas-menu__button')
          .attr('aria-expanded', 'false');
      }
    });
  }

  Drupal.behaviors.OffCanvasMenu = {
    attach: function () {
      $('.off-canvas-menu__button').once('OffCanvasMenu').on('click', function () {
        var $target = $('#' + $(this).attr('aria-controls'));
        if ($(this).attr('aria-expanded') === 'true') {
          $target.attr('aria-hidden', 'true');
          $(this).parent().find('.off-canvas-menu__button')
            .attr('aria-expanded', 'false')
            .removeData('off-canvas-open-by-click');
          $(this).parents("header").removeClass('with-menu');
          $("body").removeClass("menu-displayed");
        }
        else {
          $target.removeAttr('aria-hidden');
          $(this).parent().find('.off-canvas-menu__button')
            .attr('aria-expanded', 'true')
            .data('off-canvas-open-by-click', true);
          $(this).parents("header").addClass("with-menu");
          $("body").addClass("menu-displayed");
        }
      });

      $(window).once('OffCanvasMenu').on('resize', debounce(handleResize, 50));
      handleResize();
    }
  };

})(Drupal, Drupal.debounce, jQuery);
