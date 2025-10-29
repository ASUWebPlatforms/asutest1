(function($, Drupal) {
  // Initialize carousel
  Drupal.behaviors.blockquotes = {
    attach: function (context) {
      // Initialise slider behavaiour.
      $(once('init-blockquotes', '.blockquote .slick', context)).each((index, element) => {
        var carousel = $(element);

        // Initialize carousel once
        if (carousel.hasClass('slick-initialized') == false) {
          // Initialize Slick carousel if more than one slide exists
          if (carousel.children().length > 1) {
            carousel.slick({
              dots: true,
              infinite: false,
              slidesToShow: 1,
              slidesToScroll: 1,
              responsive: [
                {
                  breakpoint: 992,
                  settings: {
                    adaptiveHeight: true
                  }
                }
              ]
            });
          }
        }
      });

      // Refresh the carousel if selected from the Tabs Horiztonal component
      $('.tabs.horizontal .nav-item').on('click', function(e) {
        $('.tabs.horizontal .blockquote .slick').each(function(index, element) {
          $(element).slick('refresh');
        });
      });
    }
  };
})(jQuery, Drupal);