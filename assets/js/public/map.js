let geoJsonLayer = [];
let clusterLayer = [];
let superClusterIndex;
let markerCache = [];
let activeLayer = null;

function updateActiveLayer(e, config, map) {
  activeLayer = e.name;
  updateHash(config, map);
}

function updateHash(config, map) {
  const center = map.getCenter();
  const zoom = map.getZoom();

  // start with existing hash params
  const params = new URLSearchParams(location.hash.replace("#", ""));

  // override only these values
  params.set("lat", center.lat.toFixed(6));
  params.set("lng", center.lng.toFixed(6));
  params.set("zoom", zoom);

  history.replaceState(null, "", "#" + params.toString());
}

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
  updateHash(config, map);
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
  let params = new URLSearchParams(location.hash.slice(1));
  let config = event.detail;
  let center = config.map.center;
  let zoom = config.map.default_zoom;
  let basemaps = buildBasemaps(config.map.basemaps);
  let overlays = buildBasemaps(config.map.overlays);
  let overlayParam = params.get("overlay");

  if (params.get("lat") && params.get("lng")) {
    center = [parseFloat(params.get("lat")), parseFloat(params.get("lng"))];
  }

  if (params.get("zoom")) {
    zoom = parseInt(params.get("zoom"), 10);
  }

  let map = L.map("map", {
    center: center,
    zoom: zoom,
    maxBounds: config.map.bounds,
    zoomControl: params.get("hideControls") ? false : true,
    dragging: params.get("hideControls") ? false : true,
    scrollWheelZoom: params.get("hideControls") ? false : true,
  });

  if (!params.get("base") && !params.get("hideControls")) {
    L.control.layers(basemaps, overlays).addTo(map);
  }

  if (!params.get("base")) {
    // default basemap
    Object.values(basemaps)[0].addTo(map);
  } else {
    const base = params.get("base");
    if (config.map.basemaps[base]) {
      basemaps[base].addTo(map);
    } else {
      // fallback to default basemap
      Object.values(basemaps)[0].addTo(map);
    }
  }

  if (overlayParam) {
    const overlayNames = overlayParam.split(",");

    overlayNames.forEach((name) => {
      if (config.map.overlays[name]) {
        overlays[name].addTo(map);
      }
    });
  }

  if (!params.get("hideControls")) {
    L.control.locate().addTo(map);
  }

  if (!params.get("noMarkers")) {
    updateMap(config, map);
    updateHash(config, map);
  }
  let debounceTimer = null;

  map.on("zoomend dragend", function () {
    if (debounceTimer) {
      clearTimeout(debounceTimer);
    }
    debounceTimer = setTimeout(function () {
      if (!params.get("noMarkers")) {
        updateMap(config, map);
        updateHash(config, map);
      }
    }, 300);
  });

  map.on("baselayerchange", function (e) {
    updateActiveLayer(e, config, map);
  });
});
