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

$(document).on("leafletFinishedLoading", function (event) {
  let config = event.detail;

  let map = L.map("map", {
    zoomControl: false,
    dragging: false,
    touchZoom: false,
    doubleClickZoom: false,
    scrollWheelZoom: false,
    boxZoom: false,
    keyboard: false,
    tap: false,
    closePopupOnClick: false,
  });

  let basemaps = buildBasemaps(config.map.basemaps);
  let overlays = buildBasemaps(config.map.overlays);

  L.control.layers(basemaps, overlays).addTo(map);

  Object.values(basemaps)[0].addTo(map);

  let id = parseInt(window.location.pathname.split("/").pop(), 10);

  $.get(`${config.map.path}?location=${id}`, function (data) {
    let geojson = L.geoJSON(data, {
      pointToLayer: function (feature, latlng) {
        map.setView(latlng, 15);
        return L.marker(latlng, {
          icon: L.AwesomeMarkers.icon({
            icon: feature.properties.icon.name,
            stylePrefix: feature.properties.icon.prefix,
            prefix: "fa",
            markerColor: feature.properties.markerColor,
          }),
        });
      },
    }).addTo(map);

    geojson.eachLayer(function (layer) {
      layer.openPopup();
    });
  });
});
