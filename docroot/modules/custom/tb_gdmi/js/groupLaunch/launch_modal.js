(function ($, Drupal) {
  $.ajax({
    url: '/gdmi-modal',
    method: 'POST',
    data: {
      id: 'launch-group',
      title: 'Launch Group',
      description: 'The assessment will launch automatically.<span class="mb-2 d-block"></span> The system will automatically send invites and reminders, according to your inputs.',
      close_btn_text: 'Return to Launch Settings',
      confirm_btn_text: 'Launch Group',
    },
    success: function (data) {
      const modal =  document.createElement('div');
      modal.innerHTML = data.html;
      document.body.appendChild(modal);
    },
  });

  $('.btn-launch').click(() => {
    $('#modal-overlay-launch-group').addClass('show');
    $('#modal-launch-group').addClass('show');
  });

  $(document).on('click', '#modal-overlay-launch-group, #close-modal-launch-group', function () {
    $('#modal-launch-group').removeClass('show');
    $('#modal-overlay-launch-group').removeClass('show');
  });

  $(document).on('click', '#confirm-modal-launch-group', function () {
    $('.btn-launch').parent().submit();
  });
})(jQuery, Drupal);