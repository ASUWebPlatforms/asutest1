(function($, Drupal) {
  // Initialize tabs
  Drupal.behaviors.tabsVertical = {
    attach: function (context) {
      $('.tabs .toggle', context).on('click', function(e) {
        // Reset all expanded content & show clicked
        var is_expanded = $(this).attr('aria-expanded');
        $(this).parent().children().attr('aria-expanded', false);
        if (is_expanded != 'true') show($(this), false); // Set 'true' if the first link should be selected
      });

      function show(toggle, clicked = false) {
        toggle.attr('aria-expanded', true);
        toggle.next('.content').attr('aria-expanded', true);
        toggle.closest('.row').find('.preview').html(toggle.next('.content').html());
        
        // Focus into first link if clicked
        if (clicked == true) {
          toggle.closest('.row').find('.preview li:first-of-type a').focus();
        }
      }

      show($('.toggle').first()); // Auto show first child
    }
  };
})(jQuery, Drupal);