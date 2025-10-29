(function($, Drupal) {
    // Initialize carousel
    Drupal.behaviors.signpostCarouselThreeColumn = {
        attach: function (context) {
            $('.signpost.three-column .slick-slider').not('.slick-initialized').each( function() {
                $(this).slick( {
                    slidesToShow: 3,
                    slidesToScroll: 3,
                    infinite: false,
                    dots: true,
                    focusOnSelect: true,
                    appendArrows: $(this).parent().find('.slick-controls'),
                    responsive: [
                        {
                            breakpoint: 768,
                            settings: {
                                slidesToShow: 1,
                                slidesToScroll: 1
                            }
                        }
                    ]
                });
            });
        }
    };

})(jQuery, Drupal);