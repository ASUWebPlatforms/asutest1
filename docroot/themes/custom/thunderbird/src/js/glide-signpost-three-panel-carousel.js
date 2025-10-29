(function($, Drupal) {
    // Initialize carousel
    Drupal.behaviors.signpostThreePanelCarousel = {
        attach: function (context) {
            new Glide('.signpost.three-panel .glide', {
                type: 'slider',
                gap: 0,
                rewind: false
            }).on('move.after', function() {
                setTimeout(hideArrow, 200);
            }).mount();
        }
    };

    function hideArrow() {
        $('.glide__slides').each(function () {
            var slides = $(this).find('.glide__slide');
            if (slides.length < 2) {
                return;
            }

            var index = $(this).find('.glide__slide--active').index();
            if (index < 0) {
                return;
            }

            var firstArrow = $(this).closest('.glide').find('.glide__arrow:first-child');
            var lastArrow = $(this).closest('.glide').find('.glide__arrow:last-child');
            firstArrow.removeClass('end-state');
            lastArrow.removeClass('end-state');

            if (index === 0) {
                firstArrow.addClass('end-state');
            }
            if (index === (slides.length - 1)) {
                lastArrow.addClass('end-state');
            }
        });
    }

})(jQuery, Drupal);