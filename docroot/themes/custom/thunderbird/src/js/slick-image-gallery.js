(function($, Drupal) {
    // Initialize carousel
    Drupal.behaviors.carousel_image_gallery = {
        attach: function (context, settings) {
            // Initialise preview carousel (one time)
            // console.log('context', context);
            $('.image-gallery.block', context).each(function(index, element) {
                var block_index = "image-gallery-" + index;
                $(element).addClass(block_index);
                var carousel_preview = $("." + block_index + ' .slick.image-gallery-preview');
                var carousel_thumbnails = $("." + block_index + ' .slick.image-gallery-thumbnails');
                var carousel_caption = $("." + block_index + ' .slick.image-gallery-caption');

                // Initialize preview carousel
                if (carousel_preview.hasClass('slick-initialized') == false) {
                    carousel_preview.slick({
                        asNavFor: "." + block_index + ' .slick.image-gallery-thumbnails, .' + block_index + ' .slick.image-gallery-caption',
                        fade: true,
                        infinite: false
                    });
                }

                // Initialize thumbnail carousel
                if (carousel_thumbnails.hasClass('slick-initialized') == false) {
                    carousel_thumbnails.slick({
                        arrows: false,
                        asNavFor: "." + block_index + ' .slick.image-gallery-preview, .' + block_index + ' .slick.image-gallery-caption',
                        centerMode: false, // Set true for center behavior
                        centerPadding: '0px',
                        focusOnSelect: true,
                        infinite: false,
                        responsive: [
                            {
                                breakpoint: 1024,
                                settings: {
                                    slidesToShow: 3
                                }
                            }
                        ],
                        slidesToShow: 6
                    });
                }

                // Initialize caption carousel
                if (carousel_caption.hasClass('slick-initialized') == false) {
                    carousel_caption.slick({
                        arrows: false,
                        asNavFor: "." + block_index + ' .slick.image-gallery-preview, .' + block_index + ' .slick.image-gallery-thumbnails',
                        draggable: false,
                        fade: true,
                        infinite: false
                    });
                }
            });
        }
    };
})(jQuery, Drupal);