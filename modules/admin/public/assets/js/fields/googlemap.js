(function($) {

    google.maps.event.addDomListener(window, 'load', function() {

        $('.field-type-google-map').each(initItem);

        // When a new form is added, run it again!
        $(window).bind('cmf.newform', function(e, data) {
            data.wrap.find('.field-type-google-map').each(initItem);
        });

    });
    
    function initItem() {
        
        var $wrap = $(this),
        fieldName = $wrap.attr('data-field-name'),
        settings = typeof(field_settings[fieldName]) != 'undefined' ? field_settings[fieldName] : {},
        $map = $wrap.find('div.map'),
        $lat = $wrap.find('input.lat'),
        $lng = $wrap.find('input.lng'),
        $zoom = $wrap.find('input.zoom'),
        $search = $wrap.find('input.search-input').on('keydown', cancelEnterKey),
        $searchButton = $wrap.find('.search-form button').on('click', updateSearch);

        if ($lat.attr('name').indexOf('__TEMP__') > -1) { return; }

        var geocoder = new google.maps.Geocoder();
        var myLatlng = new google.maps.LatLng(parseFloat($lat.val() || -53), parseFloat($lng.val() || 0));
        var mapOptions = {
            scrollwheel: false,
            zoom: parseInt($zoom.val() || 3),
            center: myLatlng
        }
        var map = new google.maps.Map($map[0], mapOptions);

        // Listen for zoom change
        google.maps.event.addListener(map, 'zoom_changed', function() {
            var zoomLevel = map.getZoom();
            $zoom.val(zoomLevel);

            if (settings.marker) {
                map.panTo(marker.getPosition());
            }
        });

        if (settings.marker) {

            var marker = new google.maps.Marker({
                position: myLatlng,
                map: map,
                draggable: true
            });

            // Click event changes marker position
            google.maps.event.addListener(map, 'click', function(event) {
                marker.setPosition(event.latLng);
                $lat.val(event.latLng.lat());
                $lng.val(event.latLng.lng());
            });

            // Dragging the marker changes position too
            google.maps.event.addListener(marker, 'dragend', function(event) {
                $lat.val(event.latLng.lat());
                $lng.val(event.latLng.lng());
            });

        } else {

            // Dragging the map changes position
            google.maps.event.addListener(map, 'center_changed', function(event) {
                var center = map.getCenter();
                $lat.val(center.lat());
                $lng.val(center.lng());
            });

        }
        

        $tab = $map.parents('.tab-pane');
        if ($tab.length > 0) {
            $('a[data-toggle="tab"][href="#'+$tab.attr('id')+'"]').on('shown', function() {
                google.maps.event.trigger(map, 'resize');
                map.setZoom(parseInt($zoom.val() || 8));
                map.setCenter(new google.maps.LatLng(parseFloat($lat.val() || 0), parseFloat($lng.val() || 0)));
            });
        }

        function cancelEnterKey(evt)
        {
            if (evt.keyCode == 13) {
                updateSearch();
                return false;
            }
        }

        function updateSearch()
        {
            if ($.trim($search.val()) == '') { return false; }

            geocoder.geocode({

                'address': $search.val()

            }, function(results, status) {

                if (status == google.maps.GeocoderStatus.OK) {

                    map.panTo(results[0].geometry.location);

                    if (settings.marker) {
                        marker.setPosition(results[0].geometry.location);
                    } else {
                        // Nothing
                    }
                    
                } else {
                    // console.log('Geocode was not successful for the following reason: ' + status);
                }

            });

            return false;
        }
        
    }
    
})(jQuery);