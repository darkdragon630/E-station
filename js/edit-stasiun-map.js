// Map initialization for edit stasiun page
let map, marker;
let selectedLat = stasiunData.lat;
let selectedLng = stasiunData.lng;
const isApproved = stasiunData.isApproved;

// Default Indonesia coordinates
const defaultLat = selectedLat || -2.5489;
const defaultLng = selectedLng || 118.0149;
const defaultZoom = selectedLat && selectedLng ? 16 : 5;

// Initialize map
map = L.map('map').setView([defaultLat, defaultLng], defaultZoom);

// Add OpenStreetMap tiles
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap',
    maxZoom: 19
}).addTo(map);

// Custom marker icon
const customIcon = L.icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});

// Add marker if coordinates exist
if (selectedLat && selectedLng) {
    marker = L.marker([selectedLat, selectedLng], {icon: customIcon}).addTo(map);
    marker.bindPopup(`<b>📍 Lokasi Stasiun</b><br>${stasiunData.nama}<br>Lat: ${selectedLat.toFixed(6)}<br>Lng: ${selectedLng.toFixed(6)}`).openPopup();
}

// Enable map click only if not approved
if (!isApproved) {
    map.on('click', function(e) {
        setLocation(e.latlng.lat, e.latlng.lng, false);
    });
}

/**
 * Set location on map and update form fields
 * @param {number} lat - Latitude
 * @param {number} lng - Longitude
 * @param {boolean} autoFillAddress - Whether to auto-fill address from geocoding
 */
function setLocation(lat, lng, autoFillAddress = false) {
    selectedLat = lat;
    selectedLng = lng;
    
    // Remove existing marker
    if (marker) {
        map.removeLayer(marker);
    }
    
    // Add new marker
    marker = L.marker([lat, lng], {icon: customIcon}).addTo(map);
    marker.bindPopup(`<b>📍 Lokasi Stasiun</b><br>Lat: ${lat.toFixed(6)}<br>Lng: ${lng.toFixed(6)}`).openPopup();
    
    // Update hidden form fields
    document.getElementById('latitude').value = lat.toFixed(6);
    document.getElementById('longitude').value = lng.toFixed(6);
    
    // Update coordinate display
    document.getElementById('displayLat').textContent = lat.toFixed(6);
    document.getElementById('displayLng').textContent = lng.toFixed(6);
    document.getElementById('coordinateDisplay').classList.add('show');
    
    // Auto-fill address if requested
    if (autoFillAddress) {
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`)
            .then(res => res.json())
            .then(data => {
                const addr = data.address || {};
                let addressParts = [];
                
                const road = addr.road || '';
                const houseNumber = addr.house_number || '';
                const village = addr.village || addr.hamlet || addr.suburb || '';
                const district = addr.town || addr.city_district || '';
                const city = addr.city || addr.county || '';
                const province = addr.state || '';
                
                if (road) addressParts.push(houseNumber ? `${road} No. ${houseNumber}` : road);
                if (village) addressParts.push(village);
                if (district) addressParts.push(`Kec. ${district}`);
                if (city) addressParts.push(city);
                if (province) addressParts.push(province);
                
                const fullAddress = addressParts.length > 0 ? addressParts.join(', ') : data.display_name;
                const addressField = document.querySelector('textarea[name="alamat"]');
                if (addressField && !addressField.hasAttribute('readonly')) {
                    addressField.value = fullAddress;
                }
            })
            .catch(err => console.log('Geocoding error:', err));
    }
    
    // Center map on new location
    map.setView([lat, lng], 16);
}

// GPS Location Button Handler (only if not approved)
if (!isApproved) {
    const useLocationBtn = document.getElementById('useCurrentLocation');
    
    if (useLocationBtn) {
        useLocationBtn.addEventListener('click', function() {
            const btn = this;
            const gpsStatus = document.createElement('div');
            gpsStatus.className = 'alert alert-info mt-3 gps-status-temp';
            gpsStatus.innerHTML = '<i class="fas fa-satellite-dish"></i> <span id="gpsText">Memindai lokasi GPS...</span>';
            
            // Add status display if not exists
            if (!document.querySelector('.gps-status-temp')) {
                btn.parentElement.insertAdjacentElement('afterend', gpsStatus);
            }
            
            // Update button state
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scanning GPS...';
            btn.disabled = true;
            
            if (navigator.geolocation) {
                let bestPos = null;
                let scans = 0;
                const MAX_SCANS = 15;
                const MAX_ACCURACY = 20;
                
                const watchId = navigator.geolocation.watchPosition(
                    function(position) {
                        scans++;
                        const acc = position.coords.accuracy;
                        
                        // Keep track of best position
                        if (!bestPos || acc < bestPos.coords.accuracy) {
                            bestPos = position;
                        }
                        
                        // Update status text
                        const statusText = document.getElementById('gpsText');
                        if (statusText) {
                            let accColor = acc <= 20 ? '#22c55e' : acc <= 50 ? '#facc15' : '#ef4444';
                            let accLabel = acc <= 20 ? '⭐⭐ Sangat Akurat' : acc <= 50 ? '⭐ Baik' : '⚠️ Kurang Akurat';
                            statusText.innerHTML = `Scan ${scans}/${MAX_SCANS} – Akurasi: <span style="color: ${accColor}; font-weight: 700;">±${acc.toFixed(1)}m</span> ${accLabel}`;
                        }
                        
                        // Stop when accuracy is good enough or max scans reached
                        if (acc <= MAX_ACCURACY || scans >= MAX_SCANS) {
                            navigator.geolocation.clearWatch(watchId);
                            
                            const finalLat = bestPos.coords.latitude;
                            const finalLng = bestPos.coords.longitude;
                            const finalAcc = bestPos.coords.accuracy;
                            
                            // Set location on map with address auto-fill
                            setLocation(finalLat, finalLng, true);
                            
                            // Update button state
                            btn.innerHTML = `<i class="fas fa-check-circle"></i> GPS Aktif (±${finalAcc.toFixed(0)}m)`;
                            btn.classList.remove('btn-success');
                            btn.classList.add('btn-info');
                            
                            // Remove status after 3 seconds
                            setTimeout(() => {
                                const tempStatus = document.querySelector('.gps-status-temp');
                                if (tempStatus) tempStatus.remove();
                                btn.disabled = false;
                            }, 3000);
                        }
                    },
                    function(error) {
                        navigator.geolocation.clearWatch(watchId);
                        
                        let msg = error.code === 1 ? '❌ Akses lokasi ditolak!\n\n💡 Izinkan akses lokasi di browser Anda.' 
                                : error.code === 2 ? '❌ GPS tidak tersedia!\n\n💡 Pastikan GPS device Anda aktif.' 
                                : '❌ Timeout!\n\n💡 Pastikan Anda berada di area dengan sinyal GPS yang baik.';
                        
                        alert(msg);
                        
                        // Reset button
                        btn.innerHTML = '<i class="fas fa-crosshairs"></i> Gunakan GPS Saya';
                        btn.classList.remove('btn-info');
                        btn.classList.add('btn-success');
                        btn.disabled = false;
                        
                        // Remove status
                        const tempStatus = document.querySelector('.gps-status-temp');
                        if (tempStatus) tempStatus.remove();
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            } else {
                alert('❌ Browser Anda tidak mendukung geolocation.\n\n💡 Gunakan browser modern seperti Chrome, Firefox, atau Safari.');
                btn.innerHTML = '<i class="fas fa-crosshairs"></i> Gunakan GPS Saya';
                btn.disabled = false;
                
                const tempStatus = document.querySelector('.gps-status-temp');
                if (tempStatus) tempStatus.remove();
            }
        });
    }

    // Reset Map Button Handler
    const resetMapBtn = document.getElementById('resetMap');
    
    if (resetMapBtn) {
        resetMapBtn.addEventListener('click', function() {
            // Remove marker
            if (marker) {
                map.removeLayer(marker);
                marker = null;
            }
            
            // Reset to original coordinates if they exist
            selectedLat = stasiunData.lat;
            selectedLng = stasiunData.lng;
            
            if (selectedLat && selectedLng) {
                setLocation(selectedLat, selectedLng, false);
            } else {
                // Clear form fields
                document.getElementById('latitude').value = '';
                document.getElementById('longitude').value = '';
                document.getElementById('coordinateDisplay').classList.remove('show');
                
                // Reset map view to Indonesia
                map.setView([-2.5489, 118.0149], 5);
            }
        });
    }
}