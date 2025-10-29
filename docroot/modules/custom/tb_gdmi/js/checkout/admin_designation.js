(function ($, Drupal) {
  $('.save-admin-btn').click(function() {
    $('.message-container').addClass('collapse');
    $('.form-radios.admin-participation').css('margin-top', 50);
    $('.admin-invite-action').addClass('enabled');
    $('.admin-email').addClass('disabled');
    $('.admin-invite-action.action-edit span').text('EDIT DETAILS');
    $('.admin-invite-action.action-edit svg').replaceWith('<i class="fas fa-sort-down ml-2"></i>');
    $(this).removeClass('d-block');
    $(this).addClass('d-none');
    $('.admin-participation label, .admin-participation input').addClass(['disabled','text-muted']);
  });

  $(document).on('click','.action-edit.enabled, .action-remove.enabled', function() {
    const el = $(this);
    $('.message-container').removeClass('collapse');
    $('.form-radios.admin-participation').css('margin-top', 0);
    $('.admin-invite-action').removeClass('enabled');
    $('.admin-email').removeClass('disabled');
    $('.admin-invite-action.action-edit span').text('CLOSE');
    $('.admin-invite-action.action-edit svg').replaceWith('<i class="fas fa-sort-up ml-2"></i>');
    $('.save-admin-btn').addClass('d-block');
    $('.save-admin-btn').removeClass('d-none');
    $('.admin-participation label, .admin-participation input').removeClass(['disabled','text-muted']);
    if (el.hasClass('action-remove')) {
      $('.admin-email input').val('');
    }
  });
  $(document).on('click','.admin-participation input', function(e) {
    const el = $(this);
    if (el.hasClass('disabled')) {
      e.preventDefault();
      return;
    }
  });
})(jQuery, Drupal);