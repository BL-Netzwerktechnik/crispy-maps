let map = L.map('map').fitWorld();
mapLink =
    '<a href="https://openstreetmap.org">OpenStreetMap</a>';
L.tileLayer(
    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; ' + mapLink + ' Contributors',
    maxZoom: 18,
}).addTo(map);

L.control.locate().addTo(map);

map.fitBounds([
    [48.603931996685255, -1.6040039062500002],
    [53.57952828271051, 25.290527343750004]
]);