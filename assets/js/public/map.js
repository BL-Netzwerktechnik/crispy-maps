$(document).on("configLoaded", function (event, config) {
    let map = L.map('map').fitWorld();
    L.tileLayer(
        config.map.tileLayer.server, {
        attribution: config.map.tileLayer.attribution,
        maxZoom: config.map.tileLayer.maxZoom,
    }).addTo(map);

    L.control.locate().addTo(map);

    map.fitBounds(config.map.bounds);

    
    $.get(`${config.map.path}`, function (data) {
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
});