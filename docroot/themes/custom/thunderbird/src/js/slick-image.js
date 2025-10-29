(function($, Drupal) {
  // Initialize carousel
  Drupal.behaviors.carousel_image = {
    attach: function (context, settings) {
      // Initialise slider behavaiour.
      $(once('init-carousel-image', '.carousel-image .slick', context)).each((index, element) => {
        var $carousel = $(element);

        // Initialize carousel once
        if ($carousel.hasClass('slick-initialized') == false) {
          // Move navigation below carousel after initialized
          $carousel.on('init', function(event, slick){
            var nav = $('<div class="navigation"></div>');
            nav.append($carousel.find('.slick-prev')); // Left arrow
            nav.append($carousel.find('.slick-dots')); // Dots
            nav.append($carousel.find('.slick-next')); // Right arrow
            $carousel.append(nav);
          });

          // Initialize Slick carousel if more than one slide exists
          if ($carousel.children().length > 1) {
            $carousel.slick({
              dots: true,
              infinite: false,
              slidesToShow: 1,
              slidesToScroll: 1
            });
          }
        }
      });
    }
  };
})(jQuery, Drupal);