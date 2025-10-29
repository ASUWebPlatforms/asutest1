(function($, Drupal) {
    // Initialize carousel
    Drupal.behaviors.stats = {
        attach: function (context) {
            $('.stats .carousel').not('.slick-initialized').slick({
                infinite: false,
                slidesToShow: 3,
                slidesToScroll: 3,
                responsive: [{
                    breakpoint: 960,
                    settings: {
                        fade: true,
                        slidesToShow: 1,
                        slidesToScroll: 1
                    }
                }]
            });
        }
    };
})(jQuery, Drupal);