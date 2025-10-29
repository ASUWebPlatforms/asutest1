(function($, Drupal) {
  'use strict';

  Drupal.behaviors.flipcards = {
    attach: function (context) {
      // Trigger only once
      var flipcards = $(once('flipcardsInit', '.flipcards', context));
      if (flipcards.length) {
        // Add click listener to flip cards
        $('.flipcard .toggle', context).on('click', function(e) {
          e.preventDefault();
          var card = $(this).closest('.flipcard');
          card.toggleClass('flipped');
          updateSingleCardLinks(card, true);
        });

        // Update each card link
        updateAllCardLinks();
      }
    }
  };

  function updateSingleCardLinks(card, focus = false) {
    if (card.hasClass('flipped')) {
      card.find('.front a').attr('tabindex', -1);
      card.find('.back a').attr('tabindex', 0);
      if (focus == true) card.find('.back a:first-of-type').focus();
    }
    else {
      card.find('.front a').attr('tabindex', 0);
      card.find('.back a').attr('tabindex', -1);
      if (focus == true) card.find('.front a:first-of-type').focus();
    }
  }

  function updateAllCardLinks() {
    $('.flipcard').each(function(index, elem) {
      var card = $(elem);
      updateSingleCardLinks(card);
    });
  }
})(jQuery, Drupal);