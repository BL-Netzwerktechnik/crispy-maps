let map = L.map('map').fitWorld();
mapLink =
    '<a href="https://openstreetmap.org">OpenStreetMap</a>';
L.tileLayer(
    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; ' + mapLink + ' Contributors',
    maxZoom: 18,
}).addTo(map);

map.locate({ setView: true, maxZoom: 16 });