(function($) {

    $(document).on('ready', function() {

        $('.field-type-bing-map').each(initItem);

        // When a new form is added, run it again!
        $(window).bind('cmf.newform', function(e, data) {
            data.wrap.find('.field-type-bing-map').each(initItem);
        });

    });
    
    function initItem() {

        var $wrap = $(this),
        fieldName = $wrap.attr('data-field-name'),
        settings = typeof(field_settings[fieldName]) != 'undefined' ? field_settings[fieldName] : {},
        initial = settings.initial || { lat:-53, lng:0, zoom:3 },
        $map = $wrap.find('div.map'),
        $lat = $wrap.find('input.lat'),
        $lng = $wrap.find('input.lng'),
        $zoom = $wrap.find('input.zoom'),
        $search = $wrap.find('input.search-input').on('keydown', cancelEnterKey),
        $searchButton = $wrap.find('.search-form button').on('click', updateSearch),
        $tab = $map.parents('.tab-pane'),
        map = null,
        searchManager = null,
        marker = null,
        dragging = false;

        if (!$map.length) { return; }
        if ($lat.attr('name').indexOf('__TEMP__') > -1) { return; }

        if ($tab.length > 0 && !$tab.is(':visible')) {
            $('a[data-toggle="tab"][href="#'+$tab.attr('id')+'"]').on('shown', initialise);
        } else {
            initialise();
        }

        function initialise()
        {
            if (map !== null) { return; }

            var mapCenter = new Microsoft.Maps.Location($lat.val() || initial.lat, $lng.val() || initial.lng);
            
            map = new Microsoft.Maps.Map($map[0], {
                center: mapCenter,
                zoom: parseInt($zoom.val() || initial.zoom),
                showScalebar: false,
                showMapTypeSelector: false,
                mapTypeId: Microsoft.Maps.MapTypeId.arial,
                enableSearchLogo: false,
                enableClickableLogo: false,
                inertiaIntensity: .5,
                disableZooming: true,
                disableBirdseye: true,
                enableHighDpi: true,
                credentials: settings.api_key
            });

            // Set up the search manager for geocoding
            Microsoft.Maps.loadModule('Microsoft.Maps.Search', { callback: initSearch });

            // Listen for zoom change
            Microsoft.Maps.Events.addHandler(map, 'viewchangeend', function(event) {
                var zoomLevel = map.getZoom();
                $zoom.val(zoomLevel);
            });

            if (settings.marker) {

                marker = new Microsoft.Maps.Pushpin(mapCenter, {
                    draggable:true
                }); 
                map.entities.push(marker);

                Microsoft.Maps.Events.addHandler(map, 'click', function(event) {
                    if (event.mouseMoved || event.targetType == 'pushpin') return;
                    var point = new Microsoft.Maps.Point(event.getX() + 5, event.getY() + 5);
                    var loc = map.tryPixelToLocation(point);
                    marker.setLocation(loc);
                    $lat.val(loc.latitude);
                    $lng.val(loc.longitude);
                });

                Microsoft.Maps.Events.addHandler(marker, 'dragend', function(event) {
                    if (!event.entity) return;
                    var location = event.entity.getLocation();
                    $lat.val(location.latitude);
                    $lng.val(location.longitude);
                });

            } else {

                // Dragging the map changes position
                Microsoft.Maps.Events.addHandler(map, 'viewchangeend', function(event) {
                    var center = map.getCenter();
                    $lat.val(center.latitude);
                    $lng.val(center.longitude);
                });

            }
        }

        function cancelEnterKey(evt)
        {
            if (evt.keyCode == 13) {
                updateSearch();
                return false;
            }
        }

        function initSearch()
        {
            searchManager = new Microsoft.Maps.Search.SearchManager(map);
        }

        function updateSearch()
        {
            if (!searchManager || $.trim($search.val()) == '') { return false; }

            searchManager.geocode({
                where: $search.val(),
                count: 1,
                callback: function(result, userData) {

                    if (result.results.length) {
                        
                        var loc = result.results[0].location;

                        map.setView({
                            center: loc
                        });
                        
                        if (settings.marker)  {
                            var cZoom = map.getZoom() || 0;
                            if (cZoom < 12) {
                                map.setZoom(12);
                            }
                        }
                        
                        if (settings.marker && marker != null) {
                            marker.setLocation(loc);
                        } else {
                            // Nothing
                        }

                        $lat.val(loc.latitude);
                        $lng.val(loc.longitude);
                        $zoom.val(map.getZoom());
                    }

                }
            });

            return false;
        }
        
    }
    
})(jQuery);