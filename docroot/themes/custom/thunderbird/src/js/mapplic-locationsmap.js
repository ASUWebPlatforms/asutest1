(function($, Drupal) {
    // var jsonPath = '/themes/custom/thunderbird/src/json/locationsmap-data.json';
    var jsonPath = '/locations.json';
    var debug = window.location.search.includes('debug=true');
    var map = null;
    var mapSelf = null;
    var mapData = {
        "developer": debug,
        "mapwidth": "1200",
        "mapheight": "600",
        "levels": [
            {
                "id": "world",
                "title": "World",
                "map": "/themes/custom/thunderbird/src/img/mapplic/world-continents.svg",
                "locations": []
            }
        ]
    };

    // Initialize carousel
    Drupal.behaviors.locationsMap = {
        attach: function (context) {
            // AJAX call to server
            if ($('#mapplic').hasClass('data-loaded') == false) {
                $.ajax({
                    url: jsonPath,
                    dataType: 'json',
                    success: function(response) {
                        updateLocations(response);
                        initializeMap();
                        addMapEvents();
                    },
                    beforeSend: function() {
                        // Prevent Drupal behavior from performing multiple ajax calls
                        $('#mapplic').addClass('data-loaded');
                    }
                });
            }
        }
    };

    function updateLocations(response) {
        for (var i = 0; i < response.length; i++) {
            mapData.levels[0].locations.push({
                "id": i + '', // Convert to string
                "title": response[i].name + ", " + response[i].field_country,
                "description": response[i].description__value,
                "image": response[i].field_location_image.replace(/\n/g, '').trim(),
                "link": response[i].field_location_url,
                "pin": response[i].field_pin_class != null ? response[i].field_pin_class : '',
                "x": response[i].field_x_coordinate,
                "y": response[i].field_y_coordinate
            });
        }
    }

    function initializeMap() {
        let height = 600;
        if ($(window).width() < 1200) {
            height = 400;
        }
        map = $('#mapplic').mapplic({
            closezoomout: false,
            height: height,
            hovertip: false,
            maxscale: 3,
            minimap: false,
            mousewheel: false,
            sidebar: false,
            source: mapData,
            thumbholder: true,
            zoomoutclose: true
        });
        mapSelf = map.data('mapplic');
    }

    function addMapEvents() {
        // Replace the tooltip default image with a background iamge
        map.on('locationopened', function(e, location) {
            $('.mapplic-element').addClass('zoomed');
            $('.mapplic-image').remove(); // Remove for new image background
            $('.mapplic-tooltip-body').before('<div class="mapplic-custom-image" style="background-image: url(\'' + location.image + '\');"></div>');
            $('.mapplic-tooltip-body').wrapInner('<a href="' + location.link + '" class="location-link" />');
            $('.mapplic-popup-link').remove(); // Remove button
        });

        // Add empty href for keyboard support
        map.on('mapready', function(e, self) {
            map.find('.mapplic-pin').attr('href', '')
        });

        // Remove the zoomed class to unstyle default pin
        map.on('locationclosed', function(e){
            $('.mapplic-element').removeClass('zoomed');
        })

        // Fix click not working bug
        $(document).on('click', '.mapplic-pin', function(e) {
            var isActive = $(this).hasClass('mapplic-active');
            if (isActive == false) {
                mapSelf.showLocation($(this).attr('data-location'));
            }
        });
    }
})(jQuery, Drupal);