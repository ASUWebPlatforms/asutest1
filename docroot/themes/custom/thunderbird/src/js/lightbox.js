(function($, Drupal) {
    // Initialize popup behavior
    Drupal.behaviors.lightBox = {
        attach: function (context) {
            $(document).on('click', '[data-lightbox]', lity);
        }
    };
})(jQuery, Drupal);