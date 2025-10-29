/**
 * @file
 * JS behaviors for the decorative image widget.
 */
(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.decorativeImageWidget = {
    attach: function (context) {
      // When the decorative image checkbox is checked, disable the alt
      // text field.
      $(context).find('.image-widget .decorative-checkbox').once('decorative-image-widget').each(function () {
        let $altTextField = $(this).parent().parent().find('.alt-textfield');

        $(this).change(function () {
          enableOrDisableAltTextField($altTextField, !$(this).is(':checked'));
        });
        enableOrDisableAltTextField($altTextField, !$(this).is(':checked'));
      });

      function enableOrDisableAltTextField($altTextField, enable) {
        if (!enable) {
          $altTextField
            .prop('disabled', true)
            .parent().addClass('form-disabled');
        }
        else {
          $altTextField
            .prop('disabled', false)
            .parent().removeClass('form-disabled');
        }
      }
    },
  };

})(jQuery, Drupal);
