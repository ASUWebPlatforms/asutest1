(function($, Drupal) {
    // Initialize carousel
    Drupal.behaviors.imageGalleryCarouselGlide = {
        attach: function (context) {
            setTimeout(function() { 
                new Glide('.glide.image-gallery', {
                    type: 'slider',
                    gap: 0,
                    peek: 0,
                    perView: 1
                }).mount();

                // Check if arrows need to be removed
                $('.glide.image-gallery').each(function() {
                    var thumbnails = $(this).find('.bullet-image-container');
                    var arrows = $(this).find('.glide__arrow');
                    
                    // Remove arrows if  6 thumbnails do not exist
                    if (thumbnails.length < 6) {
                        arrows.remove();
                    }
                });
            }, 500);
        }
    };
})(jQuery, Drupal);