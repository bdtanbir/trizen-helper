function in_array(el, arr) {
    for (var i in arr) {
        if (arr[i] == el) return true;
    }
    return false;
}
jQuery(function ($) {
    "use strict";

        var ts_gmap_input_zoom = $(this).find('#zoom_level');
        var ts_gmap_input_lat  = $(this).find('#latitude');
        var ts_gmap_input_lng  = $(this).find('#longitude');
        // var ts_gmap_searchbox  = $(this).find('#pac-input');

        var current_marker,old_lat=37,old_lng=2,old_zoom=17;

        if(ts_gmap_input_lat.val()){
            old_lat = ts_gmap_input_lat.val();
            old_lat = parseFloat(old_lat);
        }
        if(ts_gmap_input_lng.val()){
            old_lng = ts_gmap_input_lng.val();
            old_lng = parseFloat(old_lng);
        }
        if(ts_gmap_input_zoom.val()){
            old_zoom = ts_gmap_input_zoom.val();
            old_zoom = parseFloat(old_zoom);
        }

        var map = new google.maps.Map(document.getElementById("ts_gmap"), {
            center: { lat: old_lat, lng: old_lng },
            zoom: old_zoom,
            mapTypeId: "roadmap",
            scrollwheel: true,
        });

        /*new google.maps.Marker({
            position: { lat: -33.8688, lng: 151.2195 },
            map,
            title: "Hello World!",
        });*/
        // Create the search box and link it to the UI element.
        const input = document.getElementById("pac-input");
        const searchBox = new google.maps.places.SearchBox(input);
        map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
        // Bias the SearchBox results towards current map's viewport.
        map.addListener("bounds_changed", () => {
            searchBox.setBounds(map.getBounds());
        });
        let markers = [];
        // Listen for the event fired when the user selects a prediction and retrieve
        // more details for that place.
        searchBox.addListener("places_changed", () => {
            const places = searchBox.getPlaces();

            if (places.length == 0) {
                return;
            }
            // Clear out the old markers.
            markers.forEach((marker) => {
                marker.setMap(null);
            });
            markers = [];
            // For each place, get the icon, name and location.
            const bounds = new google.maps.LatLngBounds();
            places.forEach((place) => {
                if (!place.geometry || !place.geometry.location) {
                    console.log("Returned place contains no geometry");
                    return;
                }
                const icon = {
                    url: place.icon,
                    size: new google.maps.Size(71, 71),
                    origin: new google.maps.Point(0, 0),
                    anchor: new google.maps.Point(17, 34),
                    scaledSize: new google.maps.Size(25, 25),
                };
                // Create a marker for each place.
                /*markers.push(
                    new google.maps.Marker({
                        map,
                        icon,
                        title: place.name,
                        position: place.geometry.location,
                    })
                );*/

                new google.maps.Marker({
                    position: place.geometry.location,
                    map,
                    title: "Hello World!",
                });

                if (place.geometry.viewport) {
                    // Only geocodes have viewport.
                    bounds.union(place.geometry.viewport);
                } else {
                    bounds.extend(place.geometry.location);
                }
            });
            map.fitBounds(bounds);
        });

        current_marker=new google.maps.Marker({
            position:new google.maps.LatLng(old_lat,old_lng),
            zoom: old_zoom,
            center: [old_lat,old_lng],
            map: map
        });
        map.addListener('click', (mapsMouseEnter) => {
            current_marker.setPosition(mapsMouseEnter.latLng);
            ts_gmap_input_lat.val(mapsMouseEnter.latLng.lat());
            ts_gmap_input_lng.val(mapsMouseEnter.latLng.lng());
        })

        map.addListener("zoom_changed", (event) => {
            ts_gmap_input_zoom.val(map.getZoom());
        });

});

/*jQuery(document).ready(function($){
    $('.location_map_and_latlng').each(function(){
        var self               = $(this);
        var gmap_el            = $(this).find('.ts_gmap');
        var ts_gmap_input_zoom = $(this).find('.zoom_level');
        var ts_gmap_input_lat  = $(this).find('#latitude');
        var ts_gmap_input_lng  = $(this).find('#longitude');
        var ts_gmap_searchbox  = $(this).find('#pac-input');
        var gmap_obj;
        var current_marker,old_lat = 37, old_lng = 2, old_zoom = 1;

        var markers=[];

        if(ts_gmap_input_lat.val()){
            old_lat = ts_gmap_input_lat.val();
            old_lat = parseFloat(old_lat);
        }

        if(ts_gmap_input_lng.val()){
            old_lng = ts_gmap_input_lng.val();
            old_lng = parseFloat(old_lng);
        }

        if(ts_gmap_input_zoom.val()){
            old_zoom = ts_gmap_input_zoom.val();
            old_zoom = parseFloat(old_zoom);
        }

        gmap_el.gmap3({
            map:{
                options:{
                    center:[old_lat, old_lng],
                    zoom:old_zoom,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    mapTypeControl: true,
                    mapTypeControlOptions: {
                        style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
                    },
                    navigationControl: true,
                    scrollwheel: true,
                    streetViewControl: true
                },
                events:{
                    click: function(map){

                    }
                }
            }
        });

        gmap_obj = gmap_el.gmap3('get');
        if(ts_gmap_searchbox.length){
            gmap_obj.controls[google.maps.ControlPosition.TOP_LEFT].push(ts_gmap_searchbox[0]);
            var searchBox = new google.maps.places.SearchBox((ts_gmap_searchbox[0]));
            google.maps.event.addListener(searchBox, 'places_changed', function() {
                var places = searchBox.getPlaces();
                if (places.length == 0) {
                    return;
                }
                // For each place, get the icon, place name, and location.
                var bounds = new google.maps.LatLngBounds();
                for (var i = 0, place; place = places[i]; i++) {
                    bounds.extend(place.geometry.location);
                    if(i==0){
                        current_marker.setPosition(place.geometry.location);
                        ts_gmap_input_lat.val(place.geometry.location.lat());
                        ts_gmap_input_lng.val(place.geometry.location.lng());
                        ts_gmap_input_zoom.val(gmap_obj.getZoom());
                    }
                }
                gmap_obj.fitBounds(bounds);
            });
        }

        current_marker=new google.maps.Marker({
            position:new google.maps.LatLng(old_lat,old_lng),
            map:gmap_obj
        });

        google.maps.event.addListener(gmap_obj, "click", function(event) {
            ts_gmap_input_lat.val(event.latLng.lat());
            ts_gmap_input_lng.val(event.latLng.lng());
            current_marker.setPosition(event.latLng);
        });

        google.maps.event.addListener(gmap_obj, "zoom_changed", function(event) {
            ts_gmap_input_zoom.val(gmap_obj.getZoom());
        });

        $('.bt_ot_gmap_search',$(this)).click(function(){
            var addr = self.find('.bt_ot_gmap_input_addr').val();
            if(addr){

            }
        });
    });
});*/


