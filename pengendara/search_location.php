<?php
session_start();
require_once '../config/koneksi.php';

header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://nominatim.openstreetmap.org; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: https: http:; connect-src 'self' https://nominatim.openstreetmap.org https://*.tile.openstreetmap.org https://raw.githubusercontent.com;");

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if (!isset($koneksi)) {
    die("Koneksi database tidak tersedia.");
}

try {
    $checkTable = $koneksi->query("SHOW TABLES LIKE 'stok_baterai'");
    $tableExists = $checkTable->rowCount() > 0;
    
    if ($tableExists) {
        $stmt = $koneksi->query("
            SELECT s.id_stasiun, s.nama_stasiun, s.alamat, s.latitude, s.longitude, 
                   COALESCE(SUM(sb.jumlah), 0) AS total_stok
            FROM stasiun_pengisian s 
            LEFT JOIN stok_baterai sb ON s.id_stasiun = sb.id_stasiun 
            WHERE s.status_operasional = 'aktif' 
            GROUP BY s.id_stasiun
        ");
    } else {
        $stmt = $koneksi->query("
            SELECT id_stasiun, nama_stasiun, alamat, latitude, longitude, 0 AS total_stok
            FROM stasiun_pengisian WHERE status_operasional = 'aktif'
        ");
    }
    
    $stasiun = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stasiun = [];
    $error_message = "Error database: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a192f">
    <title>Cari Lokasi Stasiun - E-Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/pengendara-style.css">
    <link rel="icon" type="image/png" href="../images/Logo_1.png">
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
* { font-family: 'Poppins', sans-serif; margin: 0; padding: 0; box-sizing: border-box; }

body {
    background: linear-gradient(135deg, #0a192f 0%, #1a237e 50%, #0d47a1 100%);
    color: #e2e8f0; min-height: 100vh; position: relative; overflow-x: hidden;
}
body::before {
    content: ''; position: fixed; width: 100%; height: 100%; top: 0; left: 0;
    background: radial-gradient(circle at 20% 30%, rgba(59, 130, 246, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(96, 165, 250, 0.1) 0%, transparent 50%);
    pointer-events: none; z-index: 0; animation: float 20s ease-in-out infinite;
}
@keyframes float { 0%, 100% { transform: translateY(0) rotate(0deg); } 50% { transform: translateY(-20px) rotate(5deg); } }

.container { position: relative; z-index: 1; }

#map { 
    height: 550px; 
    border-radius: 20px; 
    border: 2px solid rgba(96, 165, 250, 0.3); 
    box-shadow: 0 8px 32px rgba(96, 165, 250, 0.2); 
    overflow: hidden; 
    transition: all 0.3s ease; 
}
#map:hover { 
    border-color: rgba(96, 165, 250, 0.5); 
    box-shadow: 0 12px 40px rgba(96, 165, 250, 0.3); 
}

/* ========== SEARCH AUTOCOMPLETE ========== */
.search-wrapper {
    position: relative;
}
.form-control {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    color: white;
    border: 1.5px solid rgba(255, 255, 255, 0.2);
    border-radius: 14px;
    padding: 14px 20px;
    transition: all 0.3s ease;
}
.form-control:focus {
    background: rgba(255, 255, 255, 0.12);
    border-color: #60a5fa;
    color: white;
    box-shadow: 0 0 0 0.25rem rgba(96, 165, 250, 0.25);
}
.form-control::placeholder { color: rgba(255, 255, 255, 0.5); }

/* Autocomplete dropdown */
#suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: rgba(15, 23, 42, 0.98);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(96, 165, 250, 0.3);
    border-radius: 14px;
    max-height: 400px;
    overflow-y: auto;
    margin-top: 8px;
    z-index: 1000;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
    display: none;
}
#suggestions.show {
    display: block;
    animation: slideDown 0.3s ease-out;
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.suggestion-item {
    padding: 14px 18px;
    cursor: pointer;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    transition: all 0.2s ease;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}
.suggestion-item:hover {
    background: rgba(96, 165, 250, 0.15);
    padding-left: 24px;
}
.suggestion-item:last-child {
    border-bottom: none;
}
.suggestion-icon {
    color: #60a5fa;
    font-size: 1.1rem;
    margin-top: 2px;
    min-width: 20px;
}
.suggestion-content {
    flex: 1;
}
.suggestion-name {
    color: #f1f5f9;
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 4px;
}
.suggestion-address {
    color: #94a3b8;
    font-size: 0.85rem;
    line-height: 1.4;
}
.suggestion-loading {
    padding: 20px;
    text-align: center;
    color: #94a3b8;
}

#suggestions::-webkit-scrollbar { width: 6px; }
#suggestions::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.05); }
#suggestions::-webkit-scrollbar-thumb { background: rgba(96, 165, 250, 0.5); border-radius: 10px; }

.btn { 
    border-radius: 12px; 
    padding: 12px 24px; 
    font-weight: 600; 
    border: none; 
    transition: all 0.3s ease; 
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); 
}
.btn-success { background: linear-gradient(135deg, #16a34a, #22c55e, #4ade80); }
.btn-success:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(22, 163, 74, 0.5); }
.btn-primary { background: linear-gradient(135deg, #2563eb, #3b82f6, #60a5fa); }
.btn-primary:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(37, 99, 235, 0.5); }
.btn-info { background: linear-gradient(135deg, #0ea5e9, #06b6d4); }
.btn-info:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(14, 165, 233, 0.5); }
.btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none !important; }

.card { 
    background: rgba(255, 255, 255, 0.08); 
    backdrop-filter: blur(16px); 
    border: 1px solid rgba(255, 255, 255, 0.12); 
    color: #e2e8f0; 
    border-radius: 18px; 
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); 
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1); 
}
.card:hover { 
    transform: translateY(-6px); 
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2); 
    border-color: rgba(96, 165, 250, 0.5); 
}
.station-card { cursor: pointer; }

.stock-badge { 
    padding: 8px 14px; 
    border-radius: 14px; 
    font-size: 0.85rem; 
    font-weight: 700; 
    display: inline-flex; 
    align-items: center; 
    gap: 6px; 
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2); 
}
.stock-high { background: linear-gradient(135deg, #22c55e, #4ade80); color: white; }
.stock-medium { background: linear-gradient(135deg, #facc15, #fde047); color: #1e293b; }
.stock-low { background: linear-gradient(135deg, #ef4444, #f87171); color: white; }
.stock-empty { background: linear-gradient(135deg, #6b7280, #9ca3af); color: white; }
.distance-badge { 
    padding: 6px 12px; 
    border-radius: 12px; 
    background: linear-gradient(135deg, #0ea5e9, #38bdf8); 
    color: white; 
    font-size: 0.85rem; 
    font-weight: 600; 
    display: inline-block; 
    box-shadow: 0 2px 8px rgba(14, 165, 233, 0.3); 
}

.nearest-info { 
    background: linear-gradient(135deg, #1e3a8a, #1e40af, #0ea5e9); 
    padding: 28px; 
    border-radius: 20px; 
    color: white; 
    box-shadow: 0 8px 40px rgba(59, 130, 246, 0.4); 
    animation: fadeInUp 0.6s ease-out; 
}
@keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

.alert { 
    border-radius: 16px; 
    backdrop-filter: blur(10px); 
    padding: 16px 20px; 
    border: 1px solid rgba(255, 255, 255, 0.1); 
}
.alert-info { background: rgba(59, 130, 246, 0.15); border-color: rgba(59, 130, 246, 0.3); color: #dbeafe; }
.alert-warning { background: rgba(251, 191, 36, 0.15); border-color: rgba(251, 191, 36, 0.3); color: #fef3c7; }
.alert-danger { background: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.3); color: #fee2e2; }

h2 { 
    font-weight: 800; 
    font-size: 2rem; 
    background: linear-gradient(135deg, #60a5fa, #3b82f6, #2563eb); 
    -webkit-background-clip: text; 
    -webkit-text-fill-color: transparent; 
    background-clip: text; 
    margin-bottom: 24px; 
}
h5, h6 { font-weight: 600; color: #f1f5f9; }

@keyframes spin { to { transform: rotate(360deg); } }
.loading { animation: spin 1s linear infinite; display: inline-block; }

#stationList::-webkit-scrollbar { width: 8px; }
#stationList::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.05); border-radius: 10px; }
#stationList::-webkit-scrollbar-thumb { background: rgba(96, 165, 250, 0.5); border-radius: 10px; }
#stationList::-webkit-scrollbar-thumb:hover { background: rgba(96, 165, 250, 0.7); }

@media (max-width: 768px) { 
    #map { height: 400px; } 
    h2 { font-size: 1.5rem; } 
    .btn { padding: 10px 20px; font-size: 0.9rem; }
    .nearest-info { padding: 20px; }
    #suggestions { max-height: 300px; }
}
</style>
</head>
<body>
    <div class="theme-toggle"><button id="toggleTheme">🌙</button></div>
    <?php include '../components/navbar-pengendara.php'; ?>
    
    <div class="mobile-header d-md-none">
        <div class="header-top">
            <div class="logo"><i class="fas fa-bolt"></i> E-Station</div>
            <div class="header-actions"><button id="mobileThemeToggle">🌙</button></div>
        </div>
        <div class="welcome-text">
            <h2>🗺️ Cari Lokasi Stasiun</h2>
            <p>Temukan stasiun pengisian terdekat</p>
        </div>
    </div>

    <div class="container mt-4 mb-5">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-warning alert-dismissible fade show">
                <strong>⚠️</strong> <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <h2 class="mb-4 d-none d-md-block">🗺️ Cari Lokasi Stasiun Pengisian</h2>
        
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="mb-3">
                    <div class="d-flex gap-2 mb-3">
                        <button id="getCurrentLocation" class="btn btn-success flex-grow-1" style="font-size: 1.05rem; padding: 16px;">
                            <i class="fas fa-crosshairs"></i> <strong>Gunakan GPS Saya</strong>
                        </button>
                        <button id="refreshLocation" class="btn btn-info" title="Refresh Lokasi" style="display:none;">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    
                    <div class="search-wrapper">
                        <input id="searchInput" type="text" class="form-control" 
                               placeholder="🔍 Ketik lokasi (Jepara, Mlonggo, Sekuro RT 11, Jl. Raya, dll)">
                        <div id="suggestions"></div>
                    </div>
                    
                    <div id="gpsStatus" class="alert alert-info mt-3 mb-0" style="font-size: 0.9rem; display: none;">
                        <i class="fas fa-satellite-dish"></i> <span id="gpsText">Memindai lokasi...</span>
                    </div>

                    <div class="alert alert-info mt-3 mb-0" style="font-size: 0.9rem;">
                        <strong>💡 Tips Pencarian:</strong>
                        <ul class="mb-0 mt-2" style="font-size: 0.85rem; line-height: 1.6;">
                            <li><strong>GPS = Akurasi Tertinggi</strong> (±5-20 meter) ⭐</li>
                            <li><strong>Autocomplete:</strong> Ketik min. 3 huruf, pilih dari daftar</li>
                            <li>Mendukung: Kota, Kecamatan, Desa, RT/RW, Jalan, Landmark</li>
                        </ul>
                    </div>
                </div>
                
                <div id="map"></div>
            </div>

            <div class="col-lg-4">
                <h5>📍 Stasiun Terdekat</h5>
                <small class="text-muted d-block mb-3">Diurutkan berdasarkan jarak & stok</small>
                
                <div id="stationList" style="max-height: 600px; overflow-y: auto;">
                    <?php if (empty($stasiun)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Tidak ada stasiun tersedia.
                        </div>
                    <?php else: 
                        foreach ($stasiun as $s): 
                            $stock = (int)$s['total_stok'];
                            if ($stock == 0) { 
                                $stockClass = 'stock-empty'; 
                                $stockLabel = '⚫ Habis'; 
                            } elseif ($stock <= 3) { 
                                $stockClass = 'stock-low'; 
                                $stockLabel = '🔴 Hampir Habis'; 
                            } elseif ($stock <= 10) { 
                                $stockClass = 'stock-medium'; 
                                $stockLabel = '🟡 Terbatas'; 
                            } else { 
                                $stockClass = 'stock-high'; 
                                $stockLabel = '🟢 Banyak'; 
                            }
                        ?>
                            <div class="card station-card mb-3" 
                                 data-id="<?php echo $s['id_stasiun']; ?>"
                                 data-lat="<?php echo $s['latitude']; ?>"
                                 data-lng="<?php echo $s['longitude']; ?>"
                                 data-stock="<?php echo $s['total_stok']; ?>">
                                <div class="card-body p-3">
                                    <h6 class="card-title mb-2">
                                        <i class="fas fa-charging-station text-primary"></i>
                                        <?php echo htmlspecialchars($s['nama_stasiun']); ?>
                                    </h6>
                                    <p class="card-text small text-muted mb-2">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($s['alamat']); ?>
                                    </p>
                                    <div class="mb-2">
                                        <span class="badge <?php echo $stockClass; ?> stock-badge">
                                            <?php echo $stockLabel; ?>: <?php echo $stock; ?> unit
                                        </span>
                                    </div>
                                    <span class="distance-badge d-block mb-2">
                                        <i class="fas fa-route"></i> Menghitung...
                                    </span>
                                    <a href="station_list.php?id=<?php echo $s['id_stasiun']; ?>" 
                                       class="btn btn-sm btn-primary w-100">
                                        Detail <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; 
                    endif; ?>
                </div>
            </div>
        </div>

        <div id="estimation" class="nearest-info mt-4" style="display:none;">
            <h5><i class="fas fa-star"></i> Stasiun Terdekat dari Lokasi Anda</h5>
            <hr style="border-color: rgba(255,255,255,0.2); margin: 16px 0;">
            <div id="estimationContent"></div>
        </div>
    </div>

    <?php include '../components/bottom-nav.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    
    <script>
        (function() {
            const theme = localStorage.getItem("theme");
            if (theme === "light") {
                document.body.classList.add("light");
                const toggleBtn = document.getElementById("toggleTheme");
                const mobileBtn = document.getElementById("mobileThemeToggle");
                if (toggleBtn) toggleBtn.textContent = "☀️";
                if (mobileBtn) mobileBtn.textContent = "☀️";
            }
        })();

        const stations = <?php echo json_encode($stasiun); ?>;
        let map, userMarker, accuracyCircle, routeLine;
        const stationMarkers = [];
        let initialLocationSet = false;
        let searchTimeout;
        
        map = L.map('map').setView([-2.5489, 118.0149], 5);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap',
            maxZoom: 19
        }).addTo(map);

        const userIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        stations.forEach(st => {
            const stock = parseInt(st.total_stok);
            let color = stock == 0 ? 'grey' : stock <= 3 ? 'red' : stock <= 10 ? 'orange' : 'green';
            const icon = L.icon({
                iconUrl: `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-${color}.png`,
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
            });
            const marker = L.marker([parseFloat(st.latitude), parseFloat(st.longitude)], {icon}).addTo(map);
            marker.bindPopup(`<b>${st.nama_stasiun}</b><br>${st.alamat}<br>🔋 ${st.total_stok} unit`);
            stationMarkers.push({marker, data: st});
        });

        if (stations.length > 0) {
            const bounds = L.latLngBounds(stations.map(s => [parseFloat(s.latitude), parseFloat(s.longitude)]));
            map.fitBounds(bounds, {padding: [50, 50], maxZoom: 12});
        }

        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371, dLat = (lat2 - lat1) * Math.PI / 180, dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon / 2) ** 2;
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        }

        function updateDistances(userLat, userLng) {
            document.querySelectorAll('.station-card').forEach(card => {
                const dist = calculateDistance(userLat, userLng, parseFloat(card.dataset.lat), parseFloat(card.dataset.lng));
                card.dataset.distance = dist;
                card.querySelector('.distance-badge').innerHTML = `<i class="fas fa-route"></i> ${dist.toFixed(2)} km`;
            });
            sortStations();
        }

        function sortStations() {
            const list = document.getElementById('stationList');
            const cards = Array.from(document.querySelectorAll('.station-card'));
            cards.sort((a, b) => {
                const distA = parseFloat(a.dataset.distance) || 999, distB = parseFloat(b.dataset.distance) || 999;
                if (Math.abs(distA - distB) < 0.5) return parseInt(b.dataset.stock) - parseInt(a.dataset.stock);
                return distA - distB;
            });
            cards.forEach(c => list.appendChild(c));
        }

        function findNearest(userLat, userLng) {
            if (!stations.length) return;
            const withDist = stations.map(st => ({...st, distance: calculateDistance(userLat, userLng, parseFloat(st.latitude), parseFloat(st.longitude))})).sort((a, b) => a.distance - b.distance);
            const nearest = withDist[0];
            const time = (nearest.distance / 60) * 60, cost = nearest.distance * 2000 * 0.15, stock = parseInt(nearest.total_stok);
            
            let badge = '', warn = '';
            if (stock == 0) {
                badge = '<span class="badge stock-empty">⚫ Habis</span>';
                warn = '<div class="alert alert-danger mt-3 mb-0"><i class="fas fa-exclamation-triangle"></i> Stok habis!</div>';
            } else if (stock <= 3) {
                badge = '<span class="badge stock-low">🔴 Hampir Habis</span>';
                warn = '<div class="alert alert-warning mt-3 mb-0"><i class="fas fa-exclamation-circle"></i> Stok terbatas!</div>';
            } else if (stock <= 10) {
                badge = '<span class="badge stock-medium">🟡 Terbatas</span>';
            } else {
                badge = '<span class="badge stock-high">🟢 Banyak</span>';
            }

            document.getElementById('estimation').style.display = 'block';
            document.getElementById('estimationContent').innerHTML = `
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <p class="mb-2"><strong><i class="fas fa-charging-station"></i> Stasiun:</strong><br><span style="font-size: 1.1rem;">${nearest.nama_stasiun}</span></p>
                        <p class="mb-2"><strong><i class="fas fa-map-marker-alt"></i> Alamat:</strong><br><small>${nearest.alamat}</small></p>
                        <p class="mb-0"><strong><i class="fas fa-route"></i> Jarak:</strong><br><span style="font-size: 1.3rem; color: #fbbf24; font-weight: 700;">${nearest.distance.toFixed(2)} km</span></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <p class="mb-2"><strong><i class="fas fa-clock"></i> Waktu:</strong><br><span style="font-size: 1.3rem; color: #fbbf24; font-weight: 700;">${time.toFixed(0)} menit</span></p>
                        <p class="mb-2"><strong><i class="fas fa-wallet"></i> Biaya:</strong><br><span style="font-size: 1.3rem; color: #fbbf24; font-weight: 700;">Rp ${cost.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g,".")}</span></p>
                        <p class="mb-0"><strong><i class="fas fa-battery-full"></i> Stok:</strong><br>${badge} ${stock} unit</p>
                    </div>
                </div>
                ${warn}
                <a href="station_list.php?id=${nearest.id_stasiun}" class="btn btn-light mt-3 fw-bold w-100" style="border-radius:12px;padding:12px 24px;background:white;color:#2563eb">
                    <i class="fas fa-info-circle"></i> Detail Lengkap
                </a>
            `;

            if (routeLine) map.removeLayer(routeLine);
            routeLine = L.polyline([[userLat, userLng], [parseFloat(nearest.latitude), parseFloat(nearest.longitude)]], {color: '#3b82f6', weight: 4, dashArray: '10,10', opacity: 0.8}).addTo(map);
            map.fitBounds([[userLat, userLng], [parseFloat(nearest.latitude), parseFloat(nearest.longitude)]], {padding: [50, 50]});
        }

        function useGPS() {
            const btn = document.getElementById('getCurrentLocation');
            const status = document.getElementById('gpsStatus');
            const text = document.getElementById('gpsText');

            let bestPos = null, watchId, scans = 0;
            const MAX_SCANS = 15, MAX_ACCURACY = 20;

            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scan GPS...';
            btn.disabled = true;
            status.style.display = 'block';
            text.innerHTML = 'Memindai...';

            watchId = navigator.geolocation.watchPosition(
                pos => {
                    scans++;
                    const acc = pos.coords.accuracy;
                    if (!bestPos || acc < bestPos.coords.accuracy) bestPos = pos;
                    text.innerHTML = `Scan ${scans} – ±${acc.toFixed(1)}m`;

                    if (acc <= MAX_ACCURACY || scans >= MAX_SCANS) {
                        navigator.geolocation.clearWatch(watchId);
                        renderPosition(bestPos, true);
                        btn.innerHTML = `<i class="fas fa-check-circle"></i> GPS Aktif (±${bestPos.coords.accuracy.toFixed(0)}m)`;
                        btn.disabled = false;
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-info');
                        document.getElementById('refreshLocation').style.display = 'block';
                        status.style.display = 'none';
                        initialLocationSet = true;
                    }
                },
                err => {
                    navigator.geolocation.clearWatch(watchId);
                    let msg = err.code === 1 ? 'Izinkan lokasi di browser' : err.code === 2 ? 'GPS tidak tersedia' : 'Timeout';
                    alert(`❌ ${msg}\n\n💡 Gunakan pencarian manual sebagai alternatif`);
                    btn.innerHTML = '<i class="fas fa-crosshairs"></i> <strong>Gunakan GPS Saya</strong>';
                    btn.disabled = false;
                    status.style.display = 'none';
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }

        function renderPosition(pos, isGPS) {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            const acc = pos.coords.accuracy;

            if (userMarker) map.removeLayer(userMarker);
            if (accuracyCircle) map.removeLayer(accuracyCircle);

            let accColor = acc <= 20 ? '#22c55e' : acc <= 50 ? '#facc15' : '#ef4444';
            let accLabel = acc <= 20 ? '⭐⭐ Sangat Akurat' : acc <= 50 ? '⭐ Baik' : '⚠️ Kurang Akurat';

            userMarker = L.marker([lat, lng], {icon: userIcon})
                .addTo(map)
                .bindPopup(`
                    <div style="min-width: 200px;">
                        <strong style="color: #2563eb; font-size: 1.1em;">
                            ${isGPS ? '📍 Lokasi GPS Anda' : '🔍 Hasil Pencarian'}
                        </strong>
                        <hr style="margin: 8px 0;">
                        <div style="font-size: 0.9em; line-height: 1.6;">
                            <strong>🎯 Koordinat:</strong><br>
                            <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.85em;">
                                ${lat.toFixed(6)}, ${lng.toFixed(6)}
                            </code>
                            <br><br>
                            <strong>📏 Akurasi:</strong><br>
                            <span style="color: ${accColor}; font-weight: 700;">±${acc.toFixed(1)} meter</span><br>
                            <small style="color: #64748b;">${accLabel}</small>
                        </div>
                    </div>
                `)
                .openPopup();

            const displayRadius = Math.max(acc, 20);
            accuracyCircle = L.circle([lat, lng], {
                radius: displayRadius,
                color: accColor,
                fillColor: accColor,
                fillOpacity: 0.15,
                weight: 2,
                dashArray: '5, 5'
            }).addTo(map);

            const zoom = acc < 10 ? 18 : acc < 20 ? 17 : acc < 50 ? 16 : 15;
            map.setView([lat, lng], zoom);

            updateDistances(lat, lng);
            findNearest(lat, lng);

            if (isGPS) {
                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`)
                    .then(res => res.json())
                    .then(data => {
                        const addr = data.address || {};
                        let addressParts = [];
                        
                        const village = addr.village || addr.hamlet || addr.suburb || addr.neighbourhood || '';
                        const district = addr.town || addr.municipality || addr.city_district || '';
                        const city = addr.city || addr.county || '';
                        const province = addr.state || '';

                        if (village) addressParts.push(`Desa ${village}`);
                        if (district) addressParts.push(`Kec. ${district}`);
                        if (city) addressParts.push(city);
                        if (province) addressParts.push(province);

                        const addressText = addressParts.length > 0 ? addressParts.join(', ') : data.display_name;

                        userMarker.setPopupContent(`
                            <div style="min-width: 220px;">
                                <strong style="color: #2563eb; font-size: 1.1em;">📍 Lokasi GPS Anda</strong>
                                <hr style="margin: 8px 0;">
                                <div style="font-size: 0.9em; line-height: 1.6;">
                                    <strong>📌 Alamat:</strong><br>
                                    <span style="color: #475569; font-size: 0.85em;">${addressText}</span>
                                    <br><br>
                                    <strong>🎯 Koordinat:</strong><br>
                                    <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.8em;">
                                        ${lat.toFixed(6)}, ${lng.toFixed(6)}
                                    </code>
                                    <br><br>
                                    <strong>📏 Akurasi:</strong><br>
                                    <span style="color: ${accColor}; font-weight: 700;">±${acc.toFixed(1)} meter</span><br>
                                    <small style="color: #64748b;">${accLabel}</small>
                                </div>
                            </div>
                        `).openPopup();
                    })
                    .catch(err => console.log('Reverse geocoding skipped:', err));
            }
        }

        // ========== AUTOCOMPLETE SEARCH ==========
        const searchInput = document.getElementById('searchInput');
        const suggestionsDiv = document.getElementById('suggestions');

        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            clearTimeout(searchTimeout);
            
            if (query.length < 3) {
                suggestionsDiv.classList.remove('show');
                suggestionsDiv.innerHTML = '';
                return;
            }

            suggestionsDiv.innerHTML = '<div class="suggestion-loading"><i class="fas fa-spinner fa-spin"></i> Mencari...</div>';
            suggestionsDiv.classList.add('show');

            searchTimeout = setTimeout(() => {
                const searchQuery = query.toLowerCase().includes('indonesia') ? query : `${query}, Indonesia`;
                
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery)}&countrycodes=id&limit=8&addressdetails=1`)
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.length > 0) {
                            suggestionsDiv.innerHTML = data.map((item, index) => {
                                const addr = item.address || {};
                                let icon = '📍';
                                let name = '';
                                let address = [];

                                // Determine icon and name based on type
                                if (item.type === 'city' || item.type === 'town') {
                                    icon = '🏙️';
                                    name = item.name;
                                } else if (item.type === 'village' || item.type === 'hamlet') {
                                    icon = '🏘️';
                                    name = item.name;
                                } else if (item.type === 'road' || item.class === 'highway') {
                                    icon = '🛣️';
                                    name = item.name || item.display_name.split(',')[0];
                                } else if (item.type === 'house' || item.class === 'building') {
                                    icon = '🏠';
                                    name = item.name || item.display_name.split(',')[0];
                                } else {
                                    icon = '📍';
                                    name = item.name || item.display_name.split(',')[0];
                                }

                                // Build address hierarchy
                                if (addr.village || addr.hamlet) address.push(addr.village || addr.hamlet);
                                if (addr.town || addr.municipality) address.push(addr.town || addr.municipality);
                                if (addr.city || addr.county) address.push(addr.city || addr.county);
                                if (addr.state) address.push(addr.state);

                                const addressText = address.length > 0 ? address.join(', ') : item.display_name;

                                return `
                                    <div class="suggestion-item" data-lat="${item.lat}" data-lon="${item.lon}" data-address="${addressText}" data-name="${name}">
                                        <div class="suggestion-icon">${icon}</div>
                                        <div class="suggestion-content">
                                            <div class="suggestion-name">${name}</div>
                                            <div class="suggestion-address">${addressText}</div>
                                        </div>
                                    </div>
                                `;
                            }).join('');

                            // Add click handlers
                            document.querySelectorAll('.suggestion-item').forEach(item => {
                                item.addEventListener('click', function() {
                                    const lat = parseFloat(this.dataset.lat);
                                    const lon = parseFloat(this.dataset.lon);
                                    const address = this.dataset.address;
                                    const name = this.dataset.name;

                                    searchInput.value = name;
                                    suggestionsDiv.classList.remove('show');

                                    selectLocation(lat, lon, address);
                                });
                            });
                        } else {
                            suggestionsDiv.innerHTML = '<div class="suggestion-loading" style="color: #f87171;">❌ Lokasi tidak ditemukan</div>';
                        }
                    })
                    .catch(err => {
                        console.error('Search error:', err);
                        suggestionsDiv.innerHTML = '<div class="suggestion-loading" style="color: #f87171;">❌ Error pencarian</div>';
                    });
            }, 500);
        });

        function selectLocation(lat, lng, address) {
            if (userMarker) map.removeLayer(userMarker);
            if (accuracyCircle) map.removeLayer(accuracyCircle);

            userMarker = L.marker([lat, lng], {icon: userIcon})
                .addTo(map)
                .bindPopup(`
                    <div style="min-width: 220px;">
                        <strong style="color: #f59e0b; font-size: 1.1em;">🔍 Lokasi Terpilih</strong>
                        <hr style="margin: 8px 0;">
                        <div style="font-size: 0.9em; line-height: 1.6;">
                            <strong>📌 Alamat:</strong><br>
                            <span style="color: #475569; font-size: 0.85em;">${address}</span>
                            <br><br>
                            <strong>🎯 Koordinat:</strong><br>
                            <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.8em;">
                                ${lat.toFixed(6)}, ${lng.toFixed(6)}
                            </code>
                        </div>
                    </div>
                `)
                .openPopup();

            accuracyCircle = L.circle([lat, lng], {
                radius: 200,
                color: '#f59e0b',
                fillColor: '#f59e0b',
                fillOpacity: 0.15,
                weight: 2,
                dashArray: '10, 5'
            }).addTo(map);

            map.setView([lat, lng], 16);

            updateDistances(lat, lng);
            findNearest(lat, lng);
        }

        // Close suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                suggestionsDiv.classList.remove('show');
            }
        });

        // ========== EVENT LISTENERS ==========
        setTimeout(() => {
            if (navigator.geolocation && stations.length > 0) {
                console.log('🚀 Auto GPS...');
                useGPS();
            }
        }, 500);

        document.getElementById('getCurrentLocation').addEventListener('click', useGPS);
        document.getElementById('refreshLocation').addEventListener('click', useGPS);

        document.querySelectorAll('.station-card').forEach(card => {
            card.addEventListener('click', e => {
                if (e.target.tagName === 'A') return;
                const lat = parseFloat(card.dataset.lat), lng = parseFloat(card.dataset.lng);
                map.setView([lat, lng], 16);
                stationMarkers.forEach(sm => {
                    if (sm.marker.getLatLng().lat === lat && sm.marker.getLatLng().lng === lng) {
                        sm.marker.openPopup();
                    }
                });
            });
        });

        // ========== THEME TOGGLE ==========
        [document.getElementById("toggleTheme"), document.getElementById("mobileThemeToggle")].forEach(btn => {
            if (btn) {
                btn.addEventListener("click", () => {
                    document.body.classList.toggle("light");
                    const isLight = document.body.classList.contains("light");
                    btn.textContent = isLight ? "☀️" : "🌙";
                    localStorage.setItem("theme", isLight ? "light" : "dark");
                    [document.getElementById("toggleTheme"), document.getElementById("mobileThemeToggle")].forEach(b => {
                        if (b) b.textContent = isLight ? "☀️" : "🌙";
                    });
                });
            }
        });

        let lastTouchEnd = 0;
        document.addEventListener('touchend', function(event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) event.preventDefault();
            lastTouchEnd = now;
        }, false);
    </script>
    <script src="../js/clean-url.js"></script>
</body>
</html>