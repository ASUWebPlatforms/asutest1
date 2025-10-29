
(function ($) {
  $(document).on('click', 'button.btn.close[data-bs-dismiss="alert"]', function (e) {
    $(this).closest('.alert').remove();  
  });
})(jQuery);