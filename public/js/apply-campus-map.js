// Toggle map visibility
document.getElementById('toggleMapBtn').addEventListener('click', function() {
    var mapContainer = document.getElementById('map');
    if (mapContainer.style.display === 'none' || mapContainer.style.display === '') {
        mapContainer.style.display = 'block';
        this.innerHTML = '<i class="bi bi-map"></i> Skjul kart';
        map.invalidateSize(); // Fix map display issue when shown
    } else {
        mapContainer.style.display = 'none';
        this.innerHTML = '<i class="bi bi-map"></i> Vis kart';
    }
});

// Determine coordinates based on position location
var positionLocation = document.getElementById('location-badge').innerText;
if (positionLocation === 'Kristiansand') {
    var coords = [58.163878627399995, 8.003390795141652];
} else {
    // Grimstad
    var coords = [58.33443208634853, 8.576996682251023];
}
// Initialize Leaflet map
var map = L.map('map').setView(coords, 15);
var marker = L.marker(coords).addTo(map);
marker.bindPopup("Campus " + positionLocation);

// Add OpenStreetMap tiles
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
}).addTo(map);

        