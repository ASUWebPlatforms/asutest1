(function ($, Drupal, drupalSettings) {
    $(document).ready(function() {
        if (window.innerWidth <= 992) {
            let videos = $(".field-media-video-file-1 video");
            videos.each(function () {
                let video = $(this).get(0);
                video.muted = true;
                video.autoplay = true;
                video.playsInline = true;
                video.play();
            });
        }
    });
  })(jQuery, Drupal, drupalSettings);