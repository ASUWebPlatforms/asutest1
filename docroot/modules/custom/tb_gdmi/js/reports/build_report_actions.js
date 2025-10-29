(function ($, Drupal) {
  const messages = new Drupal.Message();
  $(document).on('click', '#generate-report', function (e) {
    e.preventDefault();
    if ($(this).hasClass('btn-gold')) {
      $('body').append(Drupal.theme.ajaxProgressIndicatorFullscreen());
      const isAnchor =  $(this).is( "a" );
      const sid = isAnchor ? $(this).attr('data-sid') : $('#selected-gdmi').val();
      $.ajax({
        url: `/dashboard/reports/submission/${sid}/results`,
        type: 'POST',
        success: function (data) {
          const download = isAnchor ? true : $('#download-pdf').is(':checked');
          generateStandardReport(data, download);
        },
        error: function (jqXHR, textStatus) {
          messages.add('AJAX error: ' + textStatus, {type: 'error'});
        }
      });
    } else {
      messages.add('A GDMI must be selected.',  {type: 'error'});
      window.scrollTo({
        top: 0,
        behavior: 'smooth',
      });
    }
  });
  })(jQuery, Drupal);