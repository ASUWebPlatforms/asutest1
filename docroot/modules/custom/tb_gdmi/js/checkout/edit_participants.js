(function ($) {
  const btn = $('.group-edit-participants-form #edit-group-wrapper-person-details-submit');
  btn.prop('type', 'button');
  btn.removeClass('btn-primary');
  btn.addClass('btn-gray-8');

  const inputs = $('.group-edit-participants-form input[type="text"], .group-edit-participants-form input[type="email"]');
  inputs.on('input', (e) => {
    if (btn.hasClass('btn-gray-8')) {
      btn.prop('type', 'submit');
      btn.removeClass('btn-gray-8');
      btn.addClass('btn-primary');
    }
  });
})(jQuery);
