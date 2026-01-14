let geoJsonLayer = [];
let clusterLayer = [];
let superClusterIndex;
let markerCache = [];

function buildBasemaps(config) {
  const basemaps = {};

  Object.entries(config).forEach(([name, def]) => {
    let layer;

    if (def.type === "tile") {
      layer = L.tileLayer(def.url, def.options || {});
    }

    if (def.type === "wms") {
      layer = L.tileLayer.wms(def.url, {
        ...(def.options || {}),
        layers: Array.isArray(def.layers) ? def.layers.join(",") : def.layers,
      });
    }

    if (layer) {
      basemaps[name] = layer;
    }
  });

  return basemaps;
}

function cleanupGeoJsonLayer() {
  if (geoJsonLayer) {
    geoJsonLayer.forEach(function (layer) {
      layer.remove();
    });
  }
  markerCache = [];
}

function cleanupClusterLayer() {
  if (clusterLayer) {
    clusterLayer.forEach(function (layer) {
      layer.remove();
    });
  }
}

function updateMap(config, map) {
  const bounds = map.getBounds();
  const zoom = map.getZoom();

  const params = {
    minLat: bounds.getSouthWest().lat,
    minLon: bounds.getSouthWest().lng,
    maxLat: bounds.getNorthEast().lat,
    maxLon: bounds.getNorthEast().lng,
  };

  cleanupClusterLayer();

  if (zoom >= config.map.cluster_zoom) {
    $.get(
      `${config.map.path}?minLat=${params.minLat}&minLon=${params.minLon}&maxLat=${params.maxLat}&maxLon=${params.maxLon}`,
      function (data) {
        geoJsonLayer.push(
          L.geoJSON(data, {
            pointToLayer: function (feature, latlng) {
              if (markerCache.includes(feature.properties.id)) {
                return;
              }

              markerCache.push(feature.properties.id);
              return L.marker(latlng, {
                icon: L.AwesomeMarkers.icon({
                  icon: feature.properties.icon.name,
                  stylePrefix: feature.properties.icon.prefix,
                  prefix: "fa",
                  markerColor: feature.properties.markerColor,
                }),
              });
            },

            onEachFeature: function (feature, layer) {
              layer.bindPopup(feature.properties.popupContent);
            },
          }).addTo(map)
        );
      }
    );
  } else {
    $.get(`${config.map.path}?cluster=true`, function (data) {
      superClusterIndex = new Supercluster({
        log: false,
        radius: 60,
        extent: 256,
        maxZoom: 11,
        minPoints: 0,
      }).load(data);

      const bbox = [
        bounds.getWest(),
        bounds.getSouth(),
        bounds.getEast(),
        bounds.getNorth(),
      ];

      const clusters = superClusterIndex.getClusters(bbox, zoom);

      cleanupGeoJsonLayer();

      clusterLayer.push(
        L.geoJSON(clusters, {
          pointToLayer: function (feature, latlng) {
            if (feature.properties.cluster) {
              return L.marker(latlng, {
                icon: L.divIcon({
                  html: `<div class="cluster-marker">${feature.properties.point_count}</div>`,
                  className: "custom-cluster",
                  iconSize: [40, 40],
                }),
              });
            } else {
              return L.marker(latlng, {
                icon: L.divIcon({
                  html: `<div class="cluster-marker">1</div>`,
                  className: "custom-cluster",
                  iconSize: [40, 40],
                }),
              });
            }
          },
        }).addTo(map)
      );
    });
  }
}

$(document).on("mapsConfigLoaded", function (event) {
  let config = event.detail;
  let map = L.map("map", {
    center: config.map.center,
    zoom: config.map.default_zoom,
    maxBounds: config.map.bounds,
  });

  const basemaps = buildBasemaps(config.map.basemaps);

  L.control.layers(basemaps).addTo(map);

  // default basemap
  Object.values(basemaps)[0].addTo(map);

  L.control.locate().addTo(map);

  updateMap(config, map);
  let debounceTimer = null;

  map.on("zoomend dragend", function () {
    if (debounceTimer) {
      clearTimeout(debounceTimer);
    }
    debounceTimer = setTimeout(function () {
      updateMap(config, map);
    }, 300);
  });
});
