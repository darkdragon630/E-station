<?php
session_start();
require_once '../config/koneksi.php';
require_once '../pesan/alerts.php';

// Cek authentication mitra
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mitra') {
    header('Location: ../auth/login.php');
    exit;
}

$id_mitra = $_SESSION['user_id'];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#0f2746">
  <title>Tambah Stasiun — E-Station</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/mitra-style.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="../css/alert.css?v=<?= time(); ?>">

  <style>
    .page-header {
      margin-bottom: 30px;
    }

    .page-header h2 {
      background: linear-gradient(90deg, #b98cff, #ff6fa6);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      font-weight: 800;
      margin-bottom: 5px;
    }

    .page-header p {
      color: var(--muted);
    }

    .btn-back {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: #dff2ff;
      padding: 10px 20px;
      border-radius: 10px;
      font-weight: 600;
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
      margin-bottom: 20px;
    }

    .btn-back:hover {
      background: rgba(255, 255, 255, 0.15);
      color: white;
      transform: translateX(-3px);
    }

    body.light .btn-back {
      background: rgba(0, 0, 0, 0.05);
      border-color: rgba(0, 0, 0, 0.1);
      color: #1e293b;
    }

    .info-alert {
      background: rgba(68, 216, 255, 0.1);
      border: 1px solid rgba(68, 216, 255, 0.3);
      border-radius: 12px;
      padding: 15px 20px;
      margin-bottom: 25px;
      display: flex;
      align-items: flex-start;
      gap: 15px;
    }

    .info-alert i {
      color: #44d8ff;
      font-size: 1.5rem;
      margin-top: 2px;
    }

    .info-alert .content h6 {
      color: #44d8ff;
      font-weight: 700;
      margin-bottom: 5px;
    }

    .info-alert .content p {
      color: var(--muted);
      margin: 0;
      font-size: 0.9rem;
    }

    .form-container {
      max-width: 900px;
      margin: 0 auto;
    }

    /* MAP STYLES */
    #map {
      height: 400px;
      border-radius: 15px;
      border: 2px solid rgba(185, 140, 255, 0.3);
      margin-bottom: 15px;
      overflow: hidden;
    }

    .map-info {
      background: rgba(68, 216, 255, 0.1);
      border: 1px solid rgba(68, 216, 255, 0.3);
      border-radius: 10px;
      padding: 12px 15px;
      margin-bottom: 15px;
      font-size: 0.9rem;
    }

    .map-info i {
      color: #44d8ff;
      margin-right: 8px;
    }

    .coordinate-display {
      background: rgba(185, 140, 255, 0.1);
      border: 1px solid rgba(185, 140, 255, 0.3);
      border-radius: 10px;
      padding: 15px;
      margin-top: 10px;
      display: none;
    }

    .coordinate-display.show {
      display: block;
    }

    .coordinate-display .coord-label {
      color: #b98cff;
      font-weight: 600;
      margin-bottom: 8px;
      display: block;
    }

    .coordinate-display .coord-value {
      font-family: 'Courier New', monospace;
      font-size: 1.1rem;
      color: #44d8ff;
      font-weight: 700;
    }

    .gps-status-temp {
      animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 768px) {
      .form-container {
        padding: 0;
      }

      .btn-back {
        margin-bottom: 15px;
      }

      #map {
        height: 300px;
      }
    }
  </style>
</head>
<body>

<!-- DESKTOP THEME TOGGLE -->
<div class="theme-toggle">
    <button id="toggleTheme">🌙</button>
</div>

<!-- DESKTOP NAVBAR -->
<?php include '../components/navbar-mitra.php'; ?>

<!-- MOBILE HEADER -->
<div class="mobile-header d-md-none">
    <div class="header-top">
        <a href="dashboard.php" class="btn-back" style="margin: 0; padding: 8px 15px;">
            <i class="fas fa-arrow-left"></i>
            Kembali
        </a>
        <div class="header-actions">
            <button id="mobileThemeToggle">🌙</button>
        </div>
    </div>
</div>

<!-- CONTENT -->
<div class="container mt-4 mb-5">
    <?php tampilkan_alert(); ?>

    <div class="form-container">

        <!-- Back Button Desktop -->
        <a href="dashboard.php" class="btn-back d-none d-md-inline-flex">
            <i class="fas fa-arrow-left"></i>
            Kembali ke Dashboard
        </a>

        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="fas fa-plus-circle me-2"></i>Tambah Stasiun Baru</h2>
            <p>Lengkapi formulir di bawah ini untuk mendaftarkan stasiun pengisian baru</p>
        </div>

        <!-- Info Alert -->
        <div class="info-alert">
            <i class="fas fa-info-circle"></i>
            <div class="content">
                <h6>Informasi Penting</h6>
                <p>Setelah pengajuan berhasil dikirim, status stasiun akan menjadi <span class="status-badge status-pending">Menunggu Verifikasi</span>. Admin akan mereview dan memverifikasi data stasiun Anda dalam 1-3 hari kerja.</p>
            </div>
        </div>

        <!-- FORMULIR PENDAFTARAN STASIUN -->
        <div class="station-form">
            <h4><i class="fas fa-clipboard-list me-2"></i>Formulir Pendaftaran Stasiun</h4>
            <p class="form-description">Pastikan semua data yang diisi sudah benar dan lengkap.</p>
            
            <form id="stationForm" action="proses_tambah_stasiun.php" method="POST">
                
                <div class="form-group">
                    <label for="nama_stasiun">Nama Stasiun <span class="text-danger">*</span></label>
                    <input type="text" id="nama_stasiun" name="nama_stasiun" placeholder="Contoh: Stasiun E-Charge Jakarta Pusat" required>
                </div>

                <div class="form-group">
                    <label for="alamat">Alamat Lengkap <span class="text-danger">*</span></label>
                    <textarea id="alamat" name="alamat" rows="3" placeholder="Jl. Contoh No. 123, Kelurahan, Kecamatan, Kota, Provinsi" required></textarea>
                    <small class="form-text text-muted">
                        <i class="fas fa-lightbulb"></i> Alamat akan terisi otomatis saat Anda menggunakan GPS
                    </small>
                </div>

                <!-- MAP SECTION -->
                <div class="form-group">
                    <label>Pilih Lokasi di Peta <span class="text-danger">*</span></label>
                    <div class="map-info">
                        <i class="fas fa-mouse-pointer"></i>
                        <strong>Klik pada peta</strong> untuk menentukan lokasi stasiun Anda, atau gunakan tombol GPS untuk akurasi maksimal.
                    </div>
                    
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" id="useCurrentLocation" class="btn btn-success flex-grow-1">
                            <i class="fas fa-crosshairs"></i> Gunakan GPS Saya (Auto-Fill Alamat)
                        </button>
                        <button type="button" id="resetMap" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                    </div>
                    
                    <div id="map"></div>
                    
                    <div id="coordinateDisplay" class="coordinate-display">
                        <span class="coord-label">📍 Koordinat Terpilih:</span>
                        <div class="d-flex gap-3 flex-wrap">
                            <div>
                                <small class="text-muted d-block">Latitude</small>
                                <span class="coord-value" id="displayLat">-</span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Longitude</small>
                                <span class="coord-value" id="displayLng">-</span>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" id="latitude" name="latitude" required>
                    <input type="hidden" id="longitude" name="longitude" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="kapasitas">Kapasitas Charger <span class="text-danger">*</span></label>
                        <input type="number" id="kapasitas" name="kapasitas" placeholder="10" min="1" required>
                        <small class="form-text">Jumlah total unit charger</small>
                    </div>

                    <div class="form-group">
                        <label for="jumlah_slot">Jumlah Slot <span class="text-danger">*</span></label>
                        <input type="number" id="jumlah_slot" name="jumlah_slot" placeholder="5" min="1" required>
                        <small class="form-text">Slot yang tersedia untuk pengisian</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="tarif">Tarif per kWh (Rp) <span class="text-danger">*</span></label>
                        <input type="number" id="tarif" name="tarif" placeholder="2500" min="0" required>
                        <small class="form-text">Harga dalam Rupiah</small>
                    </div>

                    <div class="form-group">
                        <label for="jam_operasional">Jam Operasional <span class="text-danger">*</span></label>
                        <input type="text" id="jam_operasional" name="jam_operasional" placeholder="08:00 - 22:00" required>
                        <small class="form-text">Format: 08:00 - 22:00</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="fasilitas">Fasilitas Tambahan</label>
                    <textarea id="fasilitas" name="fasilitas" rows="2" placeholder="WiFi gratis, Mushola, Toilet, Kantin, Parkir luas, dll"></textarea>
                    <small class="form-text">Opsional - pisahkan dengan koma</small>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane me-2"></i>Ajukan Pendaftaran Stasiun
                </button>
                
                <p class="form-note text-center">
                    <i class="fas fa-shield-alt me-1"></i>
                    Data Anda akan diverifikasi oleh admin sebelum stasiun aktif di aplikasi
                </p>
            </form>
        </div>

    </div>
</div>

<!-- BOTTOM NAVIGATION (MOBILE) -->
<?php include '../components/bottom-nav-mitra.php'; ?>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
// Theme Toggle
function initTheme(btnId) {
    const btn = document.getElementById(btnId);
    if (!btn) return;
    
    const saved = localStorage.getItem("theme");
    if (saved === "light") {
        document.body.classList.add("light");
        btn.textContent = "☀️";
    } else {
        btn.textContent = "🌙";
    }

    btn.addEventListener("click", () => {
        document.body.classList.toggle("light");
        const isLight = document.body.classList.contains("light");
        btn.textContent = isLight ? "☀️" : "🌙";
        localStorage.setItem("theme", isLight ? "light" : "dark");
        
        const other = btnId === "toggleTheme" ? "mobileThemeToggle" : "toggleTheme";
        const otherBtn = document.getElementById(other);
        if (otherBtn) otherBtn.textContent = isLight ? "☀️" : "🌙";
    });
}

initTheme("toggleTheme");
initTheme("mobileThemeToggle");

// ========== MAP INITIALIZATION ==========
let map, marker;
let selectedLat = null, selectedLng = null;

// Initialize map (center of Indonesia)
map = L.map('map').setView([-2.5489, 118.0149], 5);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap',
    maxZoom: 19
}).addTo(map);

// Custom marker icon (red)
const customIcon = L.icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});

// Click on map to set location
map.on('click', function(e) {
    setLocation(e.latlng.lat, e.latlng.lng, false);
});

// Function to set location with optional auto-fill address
function setLocation(lat, lng, autoFillAddress = false) {
    selectedLat = lat;
    selectedLng = lng;
    
    // Remove existing marker
    if (marker) {
        map.removeLayer(marker);
    }
    
    // Add new marker
    marker = L.marker([lat, lng], {icon: customIcon}).addTo(map);
    marker.bindPopup(`<b>📍 Lokasi Stasiun Anda</b><br>Lat: ${lat.toFixed(6)}<br>Lng: ${lng.toFixed(6)}`).openPopup();
    
    // Update form fields
    document.getElementById('latitude').value = lat.toFixed(6);
    document.getElementById('longitude').value = lng.toFixed(6);
    
    // Update display
    document.getElementById('displayLat').textContent = lat.toFixed(6);
    document.getElementById('displayLng').textContent = lng.toFixed(6);
    document.getElementById('coordinateDisplay').classList.add('show');
    
    // Auto-fill address if requested (from GPS)
    if (autoFillAddress) {
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`)
            .then(res => res.json())
            .then(data => {
                const addr = data.address || {};
                let addressParts = [];
                
                // Build detailed address
                const road = addr.road || '';
                const houseNumber = addr.house_number || '';
                const village = addr.village || addr.hamlet || addr.suburb || addr.neighbourhood || '';
                const district = addr.town || addr.municipality || addr.city_district || '';
                const city = addr.city || addr.county || '';
                const province = addr.state || '';
                const postcode = addr.postcode || '';
                
                // Format address
                if (road) {
                    addressParts.push(houseNumber ? `${road} No. ${houseNumber}` : road);
                }
                if (village) addressParts.push(village);
                if (district) addressParts.push(`Kec. ${district}`);
                if (city) addressParts.push(`${city}`);
                if (province) addressParts.push(province);
                if (postcode) addressParts.push(postcode);
                
                const fullAddress = addressParts.length > 0 ? addressParts.join(', ') : data.display_name;
                
                // Fill the address textarea
                document.getElementById('alamat').value = fullAddress;
                
                // Update marker popup with address
                marker.setPopupContent(`
                    <div style="min-width: 220px;">
                        <strong style="color: #b98cff; font-size: 1.1em;">📍 Lokasi Stasiun</strong>
                        <hr style="margin: 8px 0;">
                        <div style="font-size: 0.9em; line-height: 1.6;">
                            <strong>📌 Alamat:</strong><br>
                            <span style="color: #475569; font-size: 0.85em;">${fullAddress}</span>
                            <br><br>
                            <strong>🎯 Koordinat:</strong><br>
                            <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.8em;">
                                ${lat.toFixed(6)}, ${lng.toFixed(6)}
                            </code>
                        </div>
                    </div>
                `).openPopup();
            })
            .catch(err => {
                console.log('Reverse geocoding error:', err);
            });
    }
    
    // Center map on marker with appropriate zoom
    map.setView([lat, lng], 16);
}

// Use current location button with high accuracy GPS
document.getElementById('useCurrentLocation').addEventListener('click', function() {
    const btn = this;
    const gpsStatus = document.createElement('div');
    gpsStatus.className = 'alert alert-info mt-3 gps-status-temp';
    gpsStatus.innerHTML = '<i class="fas fa-satellite-dish"></i> <span id="gpsText">Memindai lokasi GPS...</span>';
    
    // Insert status after button
    if (!document.querySelector('.gps-status-temp')) {
        btn.parentElement.insertAdjacentElement('afterend', gpsStatus);
    }
    
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
                
                // Update best position
                if (!bestPos || acc < bestPos.coords.accuracy) {
                    bestPos = position;
                }
                
                // Update status
                const statusText = document.getElementById('gpsText');
                if (statusText) {
                    let accColor = acc <= 20 ? '#22c55e' : acc <= 50 ? '#facc15' : '#ef4444';
                    let accLabel = acc <= 20 ? '⭐⭐ Sangat Akurat' : acc <= 50 ? '⭐ Baik' : '⚠️ Kurang Akurat';
                    statusText.innerHTML = `Scan ${scans}/${MAX_SCANS} – Akurasi: <span style="color: ${accColor}; font-weight: 700;">±${acc.toFixed(1)}m</span> ${accLabel}`;
                }
                
                // Stop if accuracy is good or max scans reached
                if (acc <= MAX_ACCURACY || scans >= MAX_SCANS) {
                    navigator.geolocation.clearWatch(watchId);
                    
                    // Use best position found
                    const finalLat = bestPos.coords.latitude;
                    const finalLng = bestPos.coords.longitude;
                    const finalAcc = bestPos.coords.accuracy;
                    
                    // Set location with auto-fill address
                    setLocation(finalLat, finalLng, true);
                    
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
                btn.innerHTML = '<i class="fas fa-crosshairs"></i> Gunakan GPS Saya (Auto-Fill Alamat)';
                btn.classList.remove('btn-info');
                btn.classList.add('btn-success');
                btn.disabled = false;
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
        btn.innerHTML = '<i class="fas fa-crosshairs"></i> Gunakan GPS Saya (Auto-Fill Alamat)';
        btn.disabled = false;
        const tempStatus = document.querySelector('.gps-status-temp');
        if (tempStatus) tempStatus.remove();
    }
});

// Reset map button
document.getElementById('resetMap').addEventListener('click', function() {
    if (marker) {
        map.removeLayer(marker);
        marker = null;
    }
    
    selectedLat = null;
    selectedLng = null;
    
    document.getElementById('latitude').value = '';
    document.getElementById('longitude').value = '';
    document.getElementById('alamat').value = '';
    document.getElementById('coordinateDisplay').classList.remove('show');
    
    map.setView([-2.5489, 118.0149], 5);
});

// Form Validation
document.getElementById('stationForm').addEventListener('submit', function(e) {
    // Check if location is selected
    if (!selectedLat || !selectedLng) {
        e.preventDefault();
        alert('⚠️ Silakan pilih lokasi stasiun di peta terlebih dahulu!');
        document.getElementById('map').scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }
    
    const lat = parseFloat(document.getElementById('latitude').value);
    const lng = parseFloat(document.getElementById('longitude').value);
    
    // Validasi koordinat
    if (isNaN(lat) || lat < -90 || lat > 90) {
        e.preventDefault();
        alert('⚠️ Latitude tidak valid!');
        return;
    }
    
    if (isNaN(lng) || lng < -180 || lng > 180) {
        e.preventDefault();
        alert('⚠️ Longitude tidak valid!');
        return;
    }
    
    if (!confirm('✅ Ajukan pendaftaran stasiun ini?\n\nPastikan semua data sudah benar karena akan direview oleh admin.')) {
        e.preventDefault();
    }
});
</script>

</body>
</html>