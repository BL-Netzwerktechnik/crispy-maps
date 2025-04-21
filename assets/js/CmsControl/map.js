$(document).on("configLoaded", function (event, config) {
    let map = L.map('map').fitWorld();
    let newMarker;
    let defaultLayer = L.tileLayer(
        config.map.tileLayer.server, {
        attribution: config.map.tileLayer.attribution,
        maxZoom: config.map.tileLayer.maxZoom,
    });


    defaultLayer.addTo(map);

    L.control.locate().addTo(map);

    map.fitBounds(config.map.bounds);


    $.get(`${config.map.path}?editMode=true`, function (data) {
        L.geoJSON(data, {
            pointToLayer: function (feature, latlng) {
                console.log(feature);
                return L.marker(latlng, {
                    icon: L.AwesomeMarkers.icon({
                        icon: feature.properties.icon.name,
                        stylePrefix: feature.properties.icon.prefix,
                        prefix: 'fa',
                        markerColor: feature.properties.markerColor
                    })
                });
            },

            onEachFeature: function (feature, layer) {
                layer.bindPopup(feature.properties.popupContent);
            }
        }).addTo(map);
    });



    map.on('click', function (data) {

        if (newMarker) {
            map.removeLayer(newMarker);
        }

        newMarker = L.marker([data.latlng.lat, data.latlng.lng], { icon: L.AwesomeMarkers.icon({ icon: 'plus', stylePrefix: 'fa-solid', prefix: 'fa', markerColor: 'green' }) }).addTo(map);
        newMarker.bindPopup(`<a class="btn btn-primary text-white" role="button" href="/admin/location/create?lat=${data.latlng.lat}&lng=${data.latlng.lng}">Neue Location Erstellen</a>`).openPopup();
    });


    map.on('popupclose', function (e) {
        if (e.popup._source === newMarker) {
            map.removeLayer(newMarker);
            newMarker = null;
        }
    });
});