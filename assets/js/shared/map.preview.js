$(document).on("leafletFinishedLoading", function (event) {
    let config = event.detail;

    let map = L.map('map', {
        zoomControl: false,
        dragging: false,
        touchZoom: false,
        doubleClickZoom: false,
        scrollWheelZoom: false,
        boxZoom: false,
        keyboard: false,
        tap: false,
        closePopupOnClick: false
    })
    let defaultLayer = L.tileLayer(
        config.map.tileLayer.server, {
        attribution: config.map.tileLayer.attribution,
        maxZoom: config.map.tileLayer.maxZoom,
    });


    defaultLayer.addTo(map);

    let id = parseInt(window.location.pathname.split('/').pop(), 10);

    $.get(`${config.map.path}?location=${id}`, function (data) {
        let geojson = L.geoJSON(data, {
            pointToLayer: function (feature, latlng) {
                map.setView(latlng, 15);
                return L.marker(latlng, {
                    icon: L.AwesomeMarkers.icon({
                        icon: feature.properties.icon.name,
                        stylePrefix: feature.properties.icon.prefix,
                        prefix: 'fa',
                        markerColor: feature.properties.markerColor
                    })
                });
            },
        }).addTo(map);


        geojson.eachLayer(function (layer) {
            layer.openPopup();

        });
    });
});