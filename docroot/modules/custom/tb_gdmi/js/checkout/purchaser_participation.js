(function ($, Drupal) {
  Drupal.behaviors.customNoButtonBehavior = {
    attach: function (context) {
      // Listen for clicks on the "NO" button.
      $('[data-no-button="true"]', context).once('custom-no-button').on('click', function (e) {
        e.preventDefault();
        var $form = $(this).closest('form');
        
        $form.find('[name="purchaser_participation_pane[no_button_clicked]"]').val('1');
        $form.submit();
      });

      var formActions = $(once('formActions','.form-actions', context));
      if (formActions.length) {
        $('.form-actions', context).each(function (key, element) {
          $(element).addClass('flex-row');
          $(element).find('[data-drupal-selector="edit-actions-no-button"]').addClass('btn-golden');
          $(element).css({'width': '242px'});
        });
      }
    },
  };
})(jQuery, Drupal);
