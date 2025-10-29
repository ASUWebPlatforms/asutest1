(function($, Drupal) {
    // Initialize popup behavior
    let popup = null;

    Drupal.behaviors.wechatLink = {
        attach: function (context) {
            $(context).on('click', '.wechat-nav-link', function (e) {
              if (!isMobileDevice()) {
                e.preventDefault();
                const screenWidth = window.screen.width;
                const screenHeight = window.screen.height;
  
                if (popup && !popup.closed) {
                  popup.close();
                }
  
                popup = window.open('about:blank', 'Thunderbird:WeChat QR', `width=600,height=530,left=${(screenWidth - 600) / 2},top=${(screenHeight - 530) / 2},resizable=no`);
                popup.document.write(`
                  <head>
                    <title>Thunderbird WeChat QR Code</title>
                    <link rel="stylesheet" media="all" href="/themes/custom/thunderbird/assets/css/thunderbird.style.css" />
                  </head>
                  <body>
                    <p style="font-size: 22px; text-align: center; font-weight: 900; color: #0179b7; margin-bottom: 0;">
                      Scan QR Code in WeChat!
                    </p>
                    <img style="display: block; margin-left: auto; margin-right: auto;" src="/themes/custom/thunderbird/assets/img/THUN_WeChat_QR.jpg" />
                  </body>
                `);
              }
            });
        }
    };

    function isMobileDevice() {
      var mobileDetector = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/ig;
      return mobileDetector.test(window.navigator.userAgent);
    };
})(jQuery, Drupal);