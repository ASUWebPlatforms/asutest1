(function ($, Drupal) {
  /* Stop views autosubmit on text inputs. */
  Drupal.behaviors.facultySearch = {
    attach: function (context, settings) {
      // Clear timeout autosubmit.
      if (Drupal.behaviors.ViewsAutoSubmit.alterTextInputTimeout) {
        Drupal.behaviors.ViewsAutoSubmit.alterTextInputTimeout = function(timeoutID) {
          return clearTimeout(timeoutID);
        }
      }
    }
  };
})(jQuery, Drupal);