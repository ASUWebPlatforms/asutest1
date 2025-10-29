/**
 * @file
 * Make parent elements clickable.
 */
(function($, Drupal) {
  /**
   * Clickable parents.
   */
  Drupal.behaviors.clickable = {
    attach: function attach(context) {
      // Find elements to be made clickable, using specified child element
      // link.
      var clickableElement = $('[data-js-clickable]', context);
      if (clickableElement.length !== 0) {
        clickableElement.each(function() {
          var link = $(this).attr('data-js-clickable');
          if (link) {
            $(this).jqueryClickable({
              selectLink: link,
              callbackAfter: function(){
                $('.email-icon').each(function(){
                  $(this).on('click', function(e){
                    e.stopPropagation();
                    window.location.href = $(this).attr('href');
                  });
                });
              }
            });
          }
          else {
            $(this).jqueryClickable();
          }
        });
      }
    },
  };

})(jQuery, Drupal);
