let geoJsonLayer;
let clusterLayer;
let superClusterIndex;

function updateMap(config, map) {

    const bounds = map.getBounds();
    const zoom = map.getZoom();

    const params = {
        minLat: bounds.getSouthWest().lat,
        minLon: bounds.getSouthWest().lng,
        maxLat: bounds.getNorthEast().lat,
        maxLon: bounds.getNorthEast().lng
    };

    if (geoJsonLayer) {
        map.removeLayer(geoJsonLayer);
    }

    if (zoom >= 10) {
        $.get(`${config.map.path}?minLat=${params.minLat}&minLon=${params.minLon}&maxLat=${params.maxLat}&maxLon=${params.maxLon}`, function (data) {

            if (geoJsonLayer) {
                map.removeLayer(geoJsonLayer);
            }
            geoJsonLayer = L.geoJSON(data, {
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
    }
    $.get(`${config.map.path}?cluster=true`, function (data) {

        superClusterIndex = new Supercluster({
            log: false,
            radius: 60,
            extent: 256,
            maxZoom: 11,
            minPoints: 1
        }).load(data);

        const bbox = [
            bounds.getWest(),
            bounds.getSouth(),
            bounds.getEast(),
            bounds.getNorth()
        ];


        const clusters = superClusterIndex.getClusters(bbox, zoom);


        if (clusterLayer) {
            map.removeLayer(clusterLayer);
        }

        clusterLayer = L.geoJSON(clusters, {
            pointToLayer: function (feature, latlng) {
                if (feature.properties.cluster) {
                    return L.marker(latlng, {
                        icon: L.divIcon({
                            html: `<div class="cluster-marker">${feature.properties.point_count}</div>`,
                            className: 'custom-cluster',
                            iconSize: [40, 40]
                        })
                    });
                } else {
                    return null;
                }
            }
        }).addTo(map);
    });

}


$(document).on("configLoaded", function (event, config) {
    let map = L.map('map').fitWorld();
    L.tileLayer(
        config.map.tileLayer.server, {
        attribution: config.map.tileLayer.attribution,
        maxZoom: config.map.tileLayer.maxZoom,
    }).addTo(map);

    L.control.locate().addTo(map);

    map.fitBounds(config.map.bounds);



    updateMap(config, map);
    let debounceTimer = null;

    map.on('zoomend moveend', function () {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }
        debounceTimer = setTimeout(function () {
            updateMap(config, map);
        }, 300);
    });
});