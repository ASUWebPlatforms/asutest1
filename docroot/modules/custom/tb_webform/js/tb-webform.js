(function ($, Drupal) {

    'use strict';

    Drupal.behaviors.tbWebform = {
      attach: function (context) {
          $("select[data-drupal-selector='edit-field-degree-type']").change(function() {
              // If degree_type is not master
              // disable hybrid option in delivery method
              if ($(this).val().toLowerCase() != "master") {
                  $("select[data-drupal-selector='edit-field-degree-delivery-method']").find("option").each(function() {
                      if ($(this).val().toLowerCase() == "hybrid") {
                          $(this).attr("disabled", "disabled").siblings().removeAttr("disabled");
                      }
                  });
              } else {
                  $("select[data-drupal-selector='edit-field-degree-delivery-method']").find("option").each(function() {
                      $(this).attr("disabled", "disabled").removeAttr("disabled");
                  });
              }
          });

          $("select[data-drupal-selector='edit-field-degree-delivery-method']").change(function() {
              // If degree_type is master
              // delivery_method is in_person
              // remove worldwide option in location
              if ($(this).val().toLowerCase() == "in_person") {
                  $("select[data-drupal-selector='edit-field-degree-location']").find("option").each(function() {
                      if ($(this).val().toLowerCase() == "phx") {
                          $(this).removeAttr("disabled");
                      }

                      if ($(this).val().toLowerCase() == "la") {
                          $(this).removeAttr("disabled");
                      }

                      if ($(this).val().toLowerCase() == "dc") {
                          $(this).removeAttr("disabled");
                      }

                      if ($(this).val().toLowerCase() == "worldwide") {
                          $(this).attr("disabled", "disabled")
                      }
                  });
              }

              // If degree_type is master
              // delivery_method is hybrid
              // remove phx and dc option in location
              if ($(this).val().toLowerCase() == "hybrid") {
                  $("select[data-drupal-selector='edit-field-degree-location']").find("option").each(function() {
                      if ($(this).val().toLowerCase() == "phx") {
                          $(this).attr("disabled", "disabled")
                      }

                      if ($(this).val().toLowerCase() == "la") {
                          $(this).removeAttr("disabled");
                      }

                      if ($(this).val().toLowerCase() == "dc") {
                          $(this).attr("disabled", "disabled")
                      }

                      if ($(this).val().toLowerCase() == "worldwide") {
                          $(this).removeAttr("disabled");
                      }
                  });
              }
          });
      }
    };

})(jQuery, Drupal);
