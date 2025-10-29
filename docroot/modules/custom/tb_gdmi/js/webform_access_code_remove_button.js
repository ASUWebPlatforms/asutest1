(function ($) {
    Drupal.behaviors.webformAccessCodeRemoveButton = {
      attach: function (context, settings) {
        $('.webform_access_code_remove_button', context).click(function(e){
            e.preventDefault();
            const element = $(this);
            const tr = element.closest('tr');
            tr.fadeOut('slow');
            tr.find('input').val('');
        })
      },
    };
  })(jQuery);