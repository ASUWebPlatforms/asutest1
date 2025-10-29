(function ($, Drupal) {
  const messages = new Drupal.Message();
  $('.add-reminder-btn').click(function (e) {

    if(!$(this).hasClass('btn-primary')) {
      return;
    }

    $('body').append(Drupal.theme.ajaxProgressIndicatorFullscreen());
    $.ajax({
      url: `/gdmi/templates/participant-reminder-form-template`,
      type: 'POST',
      data: {
        index: $('.reminders-container .reminder').length + 1
      },
      success: function (data) {
        $('.reminders-container').append(data);
        $('.ajax-progress-fullscreen').remove();
        $('.add-reminder-btn').attr('style', 'display: none !important;');
      },
      error: function (jqXHR, textStatus) {
        messages.add('AJAX error: ' + textStatus, {type: 'error'});
      }
    });
    
  });

  $(document).on('click', '.participant-reminder-action.action-remove.enabled',function (e) {

    const currentReminder = $(this).closest('.reminder');
    let groupId = $('#groupId').val();
    const pid = currentReminder.attr('pid');

    if (pid !== undefined) {
      $('body').append(Drupal.theme.ajaxProgressIndicatorFullscreen());
      $.ajax({
        url: `/dashboard/groups/${groupId}/remove-reminder`,
        type: 'POST',
        data: {
          pid,
        },
        success: function (data) {
          if (data.status === 200) {
            currentReminder.remove();
            $('.add-reminder-btn').show();
            updatePerticipantReminderIndex();
          }
          $('.ajax-progress-fullscreen').remove();
        },
        error: function (jqXHR, textStatus) {
          messages.add('AJAX error: ' + textStatus, {type: 'error'});
        }
      });
    } else {
      currentReminder.remove();
      $('.add-reminder-btn').show();
      updatePerticipantReminderIndex();
    }
  });

  $(document).on('click', '.participant-reminder-action.action-edit.enabled',function (e) {
    const currentReminder = $(this).closest('.reminder');
    currentReminder.toggleClass('collapsed');
    const isClose = currentReminder.hasClass('collapsed');
    let icon = isClose ? '<i class="fas fa-sort-down ml-2"></i>' : '<i class="fas fa-sort-up ml-2"></i>';
    let text = isClose ? 'EDIT DETAILS' : 'CLOSE';
    $(this).find('span').text(text);
    $(this).find('svg').remove();
    $(this).find('span').after(icon);
  });

  $(document).on('click', '.save-reminder-btn',function (e) {

    const currentReminder = $(this).closest('.reminder');
    let groupId = $('#groupId').val();
    const pid = currentReminder.attr('pid');
    const isNew = pid === undefined;
    const schedule_type = currentReminder.find('input[name^="reminder_shedule_type_"]:checked').val();

    $('body').append(Drupal.theme.ajaxProgressIndicatorFullscreen());
    $.ajax({
      url: `/dashboard/groups/${groupId}/add-reminder`,
      type: 'POST',
      data: {
        isNew,
        pid,
        schedule_type,
        days: currentReminder.find(`select[name="reminder_shedule_days_${schedule_type}"]`).val(),
        message_type: currentReminder.find('input[name^="reminder_message_type_"]:checked').val(),
        message_title: currentReminder.find('input[name="reminder_custom_message_title"]').val(),
        message_body: currentReminder.find('textarea[name="reminder_custom_message_body"]').val(),
        day: currentReminder.find('select[name="participant_reminder_date_day"]').val(),
        month: currentReminder.find('select[name="participant_reminder_date_month"]').val(),
        year: currentReminder.find('select[name="participant_reminder_date_year"]').val(),
        time: currentReminder.find('input[name="participant_reminder_date_time"]').val()
      },
      success: function (data) {
        if (data.status === 200) {
          currentReminder.attr('pid', data.pid);
          currentReminder.find('.reminder-title').text(data.label);
          currentReminder.find('.participant-reminder-action.action-edit').addClass('enabled');
          currentReminder.find('.participant-reminder-action.action-edit span').text('EDIT DETAILS');
          currentReminder.find('.participant-reminder-action.action-edit svg').remove();
          currentReminder.find('.participant-reminder-action.action-edit span').after('<i class="fas fa-sort-down ml-2"></i>');
          currentReminder.addClass('collapsed');
          $('.add-reminder-btn').show();
        }

        if (data.status === 400) {
          messages.add(data.message, {type: 'error'});
          window.scrollTo({
            top: 0,
            behavior: 'smooth',
          });
        }
        
        $('.ajax-progress-fullscreen').remove();
      },
      error: function (jqXHR, textStatus) {
        messages.add('AJAX error: ' + textStatus, {type: 'error'});
      }
    });

  });

  function updatePerticipantReminderIndex() {
    $('.reminders-container .reminder').each((index, element) => {
      let item = $(element);
      const newIndex = (index + 1);
      item.attr('data-index', newIndex);
      item.find('.reminder-title').attr('index', newIndex);

      item.find('input[name^="reminder_shedule_type_"]').each((index, element) => {
        const el = $(element);
        el.attr('id', el.attr('id').replace(/\d+$/, newIndex));
        el.attr('name', 'reminder_shedule_type_' + newIndex);
      });

      item.find('label[for^="reminder_shedule_type_"]').each((index, element) => {
        const el = $(element);
        el.attr('for', el.attr('for').replace(/\d+$/, newIndex));
      });

      item.find('input[name^="reminder_message_type_"]').each((index, element) => {
        const el = $(element);
        el.attr('id', el.attr('id').replace(/\d+$/, newIndex));
        el.attr('name', 'reminder_message_type_' + newIndex);
      });

      item.find('label[for^="reminder_message_type_"]').each((index, element) => {
        const el = $(element);
        el.attr('for', el.attr('for').replace(/\d+$/, newIndex));
      });

    });
  }

  // On change update enabled state.
  $(document).on('change', 'input[name*="reminder_shedule_type_"]', function(e) {
    const currentReminder = $(this).closest('.reminder');
    const selected = $(this).val();
    switch (selected) {
      case 'after':
          currentReminder.find('select[name="reminder_shedule_days_after"]').prop('disabled', false);
          currentReminder.find('select[name="reminder_shedule_days_before"]').prop('disabled', true);
          currentReminder.find('#participant_reminder_date select').prop('disabled', true);
        break;
      case 'before':
          currentReminder.find('select[name="reminder_shedule_days_before"]').prop('disabled', false);
          currentReminder.find('select[name="reminder_shedule_days_after"]').prop('disabled', true);
          currentReminder.find('#participant_reminder_date select').prop('disabled', true);
        break;
      case 'datetime':
          currentReminder.find('#participant_reminder_date select').prop('disabled', false);
          currentReminder.find('select[name="reminder_shedule_days_before"]').prop('disabled', true);
          currentReminder.find('select[name="reminder_shedule_days_after"]').prop('disabled', true);
        break;
    }
  });

   // On change update enabled state.
   $(document).on('change', 'input[name*="reminder_message_type_"]', function(e) {
    const currentReminder = $(this).closest('.reminder');
    const messageDefault = currentReminder.find('.participant-reminder-message .message-default');
    const messageCustom =  currentReminder.find('.participant-reminder-message .message-custom');
    const selected = $(this).val();
    switch (selected) {
      case 'default':
          messageDefault.find('.message-heading, .message-body, .message-title').removeClass('text-muted');
          messageCustom.find('.form-item input, .form-item textarea').prop('disabled', true);
          messageCustom.find('.form-item label, .gdmi-check-form-group small').addClass('text-muted');
        break;
      case 'custom':
          messageDefault.find('.message-heading, .message-body, .message-title').addClass('text-muted');
          messageCustom.find('.form-item input, .form-item textarea').prop('disabled', false);
          messageCustom.find('.form-item label, .gdmi-check-form-group small').removeClass('text-muted');
        break;
    }
  });

  // On init set the initial state.
  $('input[name="participants_reminders_type"]').change(function(e) {
    const type = $(this).val();
    switchParticipantReminderType(type);
  });

  function switchParticipantReminderType(type) {
    if (type === 'default') {
      $('.reminders-container').hide();
      $('.add-reminder-btn').removeClass('btn-primary');
      $('.add-reminder-btn').addClass('btn-gray');
    } else {
      $('.reminders-container').show();
      $('.add-reminder-btn').removeClass('btn-gray');
      $('.add-reminder-btn').addClass('btn-primary');
    }
  }

  switchParticipantReminderType($('input[name="participants_reminders_type"]:checked').val())
})(jQuery, Drupal);