/**
 * @file
 * timezone Select2 integration.
 */
(function ($, drupalSettings) {
    'use strict';
  
    Drupal.behaviors.timezoneSelect2 = {
      attach: function (context) {
        $('.select2', context).each(function () {
            $(this).select2();
        })
      }
    };
  
  })(jQuery, drupalSettings);