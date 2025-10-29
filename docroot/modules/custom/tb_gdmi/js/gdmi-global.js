(function ($) {
  Drupal.behaviors.gdmiGlobal = {
    attach: function (context, settings) {
      $('.nav-link.login-status').hide();
    },
  };
})(jQuery);