(function ($, Drupal) {
  $('input[name="participants_schedule_type"]').change(function(e) {
    switchSchedule($(this).val())
  });

  $('input[name="participants_message_type"]').change(function(e) {
    switchMessage($(this).val())
  });

  function switchSchedule(scheduleType) {
    $('#participant_inivitation select').prop('disabled', scheduleType === 'immediately');
  }

  function switchMessage(messageType) {
    const messageDefault = $('.participant-invitation-message .message-default');
    const messageCustom = $('.participant-invitation-message .message-custom');
   
    if (messageType === 'custom') {
      messageDefault.find('.message-heading, .message-body, .message-title').addClass('text-muted');
      messageCustom.find('.form-item input, .form-item textarea').prop('disabled', false);
      messageCustom.find('.form-item label, .gdmi-check-form-group small').removeClass('text-muted');
    } else {
      messageDefault.find('.message-heading, .message-body, .message-title').removeClass('text-muted');
      messageCustom.find('.form-item input, .form-item textarea').prop('disabled', true);
      messageCustom.find('.form-item label, .gdmi-check-form-group small').addClass('text-muted');
    }
  }

  switchSchedule($('input[name="participants_schedule_type"]:checked').val());
  switchMessage($('input[name="participants_message_type"]:checked').val());
})(jQuery, Drupal);