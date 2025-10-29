(function($, Drupal) {
    if ($.once) {
        // Initialize accordion for Drupal users (not supported in Drupal 10)
        Drupal.behaviors.accordion = {
            attach: function (context) {
                $('.snapdown .toggle', context).once('snapdown-toggle').on('click', function(e) {
                    updateSnapdown($(this));
                });
            }
        };
    }
    else {
        // Initialize accordion for public
        $(document).on('click', '.snapdown .toggle', function(e) {
            updateSnapdown($(this));
        });
    }

    function updateSnapdown($elem) {
        var toggle = $elem;
        var state = toggle.attr('aria-expanded') == 'true';
        var content = toggle.next('.content');

        // Toggle aria-expanded state
        toggle.attr('aria-expanded', !state);
        content.attr('aria-expanded', !state)
    }
})(jQuery, Drupal);