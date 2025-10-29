(function ($) {
  const inputs = $('.group-gdmi-edit-form input[type="text"]');
  inputs.prop('disabled', true);
  const btn = $('.group-gdmi-edit-form #edit-submit');
  btn.prop('type', 'button');

  btn.click(function(e){
    const element = $(this);
    let val = element.val();
    if (val === 'Edit Names') {
      element.val('Save Changes');
      inputs.prop('disabled', false);
      btn.removeClass('btn-primary');
      btn.addClass('btn-gray-8');
    } else if (btn.hasClass('btn-primary')) {
      $('.group-gdmi-edit-form').submit();
    }
  });

  inputs.on('input', function() {
    if (btn.hasClass('btn-gray-8')) {
      btn.removeClass('btn-gray-8');
      btn.addClass('btn-primary');
    }
  });
})(jQuery);