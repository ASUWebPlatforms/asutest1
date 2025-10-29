(function ($, Drupal) {
  const messages = new Drupal.Message();

  $('#compare-results, #email-report').click(function(params) {
    const el = $(this);
    el.prop('checked', !el.hasClass('checked'));
    el.toggleClass('checked');
  });

  $('#email-report').click(function(params) {
    let isChecked = $(this).prop('checked');
    const container = $('.build-report__email-options');
    container.find('.email-options-label').toggleClass('text-muted', !isChecked);
    container.find('label').toggleClass('text-muted', !isChecked);
    container.find('input[type="checkbox"]').prop('disabled', !isChecked);
  });

  $('#admin-report, #other-report').click(function(params) {
    let isChecked = $(this).prop('checked');
    $(this).siblings('select, input').prop('disabled', !isChecked);
  });

  function switchSubmitBtn(enabled) {
    const btn = $('#generate-report');
    if (enabled) {
      btn.removeClass('btn-gray');
      btn.addClass('btn-gold');
    } else {
      btn.removeClass('btn-gold');
      btn.addClass('btn-gray');
    }
  }

  $('#selected-gdmi').change(function (e) {
    let value = $(this).val();
    if (value !== '0') {
      $('body').append(Drupal.theme.ajaxProgressIndicatorFullscreen());
      $.ajax({
        url: `/dashboard/reports/submission/${value}/admins`,
        type: 'POST',
        success: function (data) {
          let options = `<option value="0">Select GDMI for the report</option>`;
          for (const [key, value] of Object.entries(data.options)) {
            options += `<option value="${key}">${value}</option>`;
          }
          $('#admin-email').html(options);
          $('.ajax-progress-fullscreen').remove();
        },
        error: function (jqXHR, textStatus) {
          messages.add('AJAX error: ' + textStatus, {type: 'error'});
        }
      });
      switchSubmitBtn(true);
    } else {
      switchSubmitBtn(false);
      $('#admin-email').html(`<option value="0">Select GDMI for the report</option>`);
    }
  });

  $('.options-dropdown').click(function(e){
    const element = $(this);
    const content = element.find('.dropdown-content');
    if (!content.hasClass('show')) {
      $('.dropdown-content').removeClass('show');
      
      let relative = $('#reports-table').get(0).getBoundingClientRect();
      let options = element.get(0).getBoundingClientRect();

      const left = (options.left - relative.left - 200) + (options.width / 2);
      const top = (options.top - relative.top) + options.height;

      content.css({'top': `${top}px`, 'left': `${left}px`});
      content.addClass('show');

    } else {
      content.removeClass('show');
    }
  });

})(jQuery, Drupal);