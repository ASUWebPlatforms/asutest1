(function ($, Drupal, drupalSettings) {
  $(document).ready(function() {
    var hash = window.location.hash;
    if (hash && $(hash).length) {
      $('html, body').animate({
        scrollTop: $(hash).offset().top - 400
      }, 1000); 
    }
  });
})(jQuery, Drupal, drupalSettings);