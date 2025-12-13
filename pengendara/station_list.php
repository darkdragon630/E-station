<?php
session_start();
require_once '../config/koneksi.php';
require_once '../pesan/alerts.php';

// Cek authentication - FIXED: Gunakan user_id yang konsisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pengendara') {
    header('Location: ../auth/login.php');
    exit;
}

if (!isset($koneksi)) {
    die("Koneksi database tidak tersedia.");
}

// Cek apakah tabel stok_baterai ada
try {
    $checkTable = $koneksi->query("SHOW TABLES LIKE 'stok_baterai'");
    $tableExists = $checkTable->rowCount() > 0;
} catch (PDOException $e) {
    $tableExists = false;
}

// Query dengan penanganan error
try {
    if ($tableExists) {
        // Cek kolom mana yang tersedia di tabel
        $checkColumns = $koneksi->query("SHOW COLUMNS FROM stasiun_pengisian");
        $columns = $checkColumns->fetchAll(PDO::FETCH_COLUMN);
        
        // Tentukan kolom yang akan diselect berdasarkan ketersediaan
        $selectColumns = "s.id_stasiun, s.nama_stasiun, s.alamat, s.latitude, s.longitude";
        
        // Tambahkan kolom opsional jika ada
        if (in_array('jam_operasional', $columns)) $selectColumns .= ", s.jam_operasional";
        if (in_array('rating', $columns)) $selectColumns .= ", s.rating";
        if (in_array('kapasitas', $columns)) $selectColumns .= ", s.kapasitas";
        
        // JOIN dengan tabel mitra untuk ambil nomor kontak
        $stmt = $koneksi->query("
            SELECT $selectColumns,
                   m.no_telepon AS nomor_kontak,
                   m.nama_mitra AS nama_mitra,
                   COALESCE(SUM(sb.jumlah), 0) AS total_stok
            FROM stasiun_pengisian s 
            LEFT JOIN mitra m ON s.id_mitra = m.id_mitra
            LEFT JOIN stok_baterai sb ON s.id_stasiun = sb.id_stasiun 
            WHERE s.status_operasional = 'aktif' 
            GROUP BY s.id_stasiun
        ");
    } else {
        // Cek kolom yang tersedia
        $checkColumns = $koneksi->query("SHOW COLUMNS FROM stasiun_pengisian");
        $columns = $checkColumns->fetchAll(PDO::FETCH_COLUMN);
        
        $selectColumns = "s.id_stasiun, s.nama_stasiun, s.alamat, s.latitude, s.longitude";
        
        if (in_array('jam_operasional', $columns)) $selectColumns .= ", s.jam_operasional";
        if (in_array('rating', $columns)) $selectColumns .= ", s.rating";
        if (in_array('kapasitas', $columns)) $selectColumns .= ", s.kapasitas";
        
        $stmt = $koneksi->query("
            SELECT $selectColumns,
                   m.no_telepon AS nomor_kontak,
                   m.nama AS nama_mitra,
                   0 AS total_stok
            FROM stasiun_pengisian s
            LEFT JOIN mitra m ON s.id_mitra = m.id_mitra
            WHERE s.status_operasional = 'aktif'
        ");
    }
    
    $stasiun = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stasiun = [];
    $error_message = "Error database: " . $e->getMessage();
}

$detail = null;
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    foreach ($stasiun as $s) {
        if ($s['id_stasiun'] == $id) {
            $detail = $s;
            break;
        }
    }
}

// Fungsi indikator stok
function getStokInfo($stok) {
    if ($stok == 0) return ['class' => 'stock-empty', 'label' => '⚫ Habis', 'color' => 'secondary'];
    if ($stok <= 3) return ['class' => 'stock-low', 'label' => '🔴 Hampir Habis', 'color' => 'danger'];
    if ($stok <= 10) return ['class' => 'stock-medium', 'label' => '🟡 Terbatas', 'color' => 'warning'];
    return ['class' => 'stock-high', 'label' => '🟢 Banyak', 'color' => 'success'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a192f">
    <title><?php echo $detail ? 'Detail ' . htmlspecialchars($detail['nama_stasiun']) : 'Daftar Stasiun'; ?> - E-Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/pengendara-style.css">
    <link rel="stylesheet" href="../css/alert.css">
    
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

* {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: linear-gradient(135deg, #0a192f 0%, #1a237e 50%, #0d47a1 100%);
    color: #e2e8f0;
    min-height: 100vh;
    position: relative;
    overflow-x: hidden;
}

body::before {
    content: '';
    position: fixed;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    background: 
        radial-gradient(circle at 20% 30%, rgba(59, 130, 246, 0.15) 0%, transparent 50%),
        radial-gradient(circle at 80% 70%, rgba(96, 165, 250, 0.1) 0%, transparent 50%);
    pointer-events: none;
    z-index: 0;
    animation: float 20s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(5deg); }
}

.container {
    position: relative;
    z-index: 1;
}

/* Card */
.card {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(16px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 48px rgba(96, 165, 250, 0.3);
}

.card-title {
    font-size: 1.8rem;
    font-weight: 700;
    color: #60a5fa;
    margin-bottom: 20px;
}

.info-item {
    background: rgba(255, 255, 255, 0.05);
    border-left: 4px solid #3b82f6;
    padding: 16px;
    border-radius: 12px;
    margin-bottom: 16px;
    transition: all 0.3s ease;
}

.info-item:hover {
    background: rgba(255, 255, 255, 0.08);
    transform: translateX(5px);
}

.info-item strong {
    color: #60a5fa;
    font-weight: 600;
    display: block;
    margin-bottom: 6px;
}

/* Map */
#map {
    height: 450px;
    border-radius: 20px;
    border: 2px solid rgba(96, 165, 250, 0.3);
    box-shadow: 0 0 40px rgba(96, 165, 250, 0.2);
    overflow: hidden;
}

/* Buttons */
.btn {
    border-radius: 12px;
    padding: 12px 28px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    position: relative;
    overflow: hidden;
}

.btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.btn:hover::before {
    width: 300px;
    height: 300px;
}

.btn-primary {
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    box-shadow: 0 4px 16px rgba(37, 99, 235, 0.3);
}

.btn-primary:hover {
    background: linear-gradient(135deg, #1d4ed8, #2563eb);
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(37, 99, 235, 0.5);
}

.btn-success {
    background: linear-gradient(135deg, #16a34a, #22c55e);
    box-shadow: 0 4px 16px rgba(22, 163, 74, 0.3);
}

.btn-success:hover {
    background: linear-gradient(135deg, #15803d, #16a34a);
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(22, 163, 74, 0.5);
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-3px);
}

/* Stock Badge */
.stock-badge {
    padding: 10px 18px;
    border-radius: 14px;
    font-size: 1rem;
    font-weight: 700;
    display: inline-block;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.stock-high { 
    background: linear-gradient(135deg, #22c55e, #4ade80);
    color: white;
}

.stock-medium { 
    background: linear-gradient(135deg, #facc15, #fde047);
    color: #1e293b;
}

.stock-low { 
    background: linear-gradient(135deg, #ef4444, #f87171);
    color: white;
}

.stock-empty { 
    background: linear-gradient(135deg, #6b7280, #9ca3af);
    color: white;
}

/* Distance Box */
.distance-info {
    background: linear-gradient(135deg, #1e3a8a, #0ea5e9);
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 0 30px rgba(59, 130, 246, 0.4);
    margin-bottom: 24px;
}

.distance-info h5 {
    color: white;
    font-weight: 700;
    margin-bottom: 16px;
}

.distance-value {
    font-size: 2rem;
    font-weight: 800;
    color: #fbbf24;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

/* Rating */
.rating {
    color: #fbbf24;
    font-size: 1.2rem;
}

/* Page Title */
h2 {
    font-weight: 800;
    font-size: 2rem;
    background: linear-gradient(135deg, #60a5fa, #3b82f6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 24px;
}

/* Light Mode */
body.light {
    background: linear-gradient(135deg, #e0f2fe, #bae6fd);
    color: #1e293b;
}

body.light::before {
    background: 
        radial-gradient(circle at 20% 30%, rgba(59, 130, 246, 0.08) 0%, transparent 50%);
}

body.light .card {
    background: rgba(255, 255, 255, 0.9);
    color: #1e293b;
    border: 1px solid rgba(0, 0, 0, 0.08);
}

body.light .info-item {
    background: rgba(0, 0, 0, 0.03);
    border-left-color: #3b82f6;
}

body.light .info-item strong {
    color: #2563eb;
}

body.light h2 {
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* Responsive */
@media (max-width: 768px) {
    #map {
        height: 350px;
    }
    
    .card-title {
        font-size: 1.5rem;
    }
    
    .distance-value {
        font-size: 1.5rem;
    }
}
</style>
</head>
<body>
    <!-- DESKTOP THEME TOGGLE -->
    <div class="theme-toggle">
        <button id="toggleTheme" aria-label="Ganti Tema">🌙</button>
    </div>

    <!-- DESKTOP NAVBAR - Reusable Component -->
    <?php include '../components/navbar-pengendara.php'; ?>

    <!-- MOBILE HEADER -->
    <div class="mobile-header d-md-none">
        <div class="header-top">
            <a href="<?php echo $detail ? 'station_detail.php' : 'dashboard.php'; ?>" style="color: #60a5fa; text-decoration: none; font-size: 1.5rem;">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div style="font-size: 1.1rem; font-weight: 700; color: #fff;">
                <?php echo $detail ? 'Detail Stasiun' : 'Daftar Stasiun'; ?>
            </div>
            <button id="mobileThemeToggle" style="
                background: rgba(255, 255, 255, 0.1);
                border: 2px solid rgba(255, 255, 255, 0.2);
                border-radius: 50%;
                width: 40px;
                height: 40px;
                font-size: 1.3rem;
                cursor: pointer;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                justify-content: center;
            ">🌙</button>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="container mt-md-5 mt-4 mb-5">
        <?php tampilkan_alert(); ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-warning alert-dismissible fade show">
                <strong>⚠️ Perhatian:</strong> <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!$tableExists): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <strong>ℹ️ Info:</strong> Tabel stok_baterai belum tersedia. Jalankan SQL script untuk membuat struktur database.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($detail): ?>
            <!-- DETAIL VIEW -->
            <a href="station_list.php" class="btn btn-secondary mb-4 d-none d-md-inline-block">
                <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar
            </a>
            
            <div id="distanceBox" class="distance-info" style="display:none;">
                <h5>📏 Jarak dari Lokasi Anda</h5>
                <div class="distance-value" id="distanceValue">Menghitung...</div>
                <small>⏱️ Estimasi waktu: <span id="timeValue">-</span> menit</small><br>
                <small>💰 Estimasi biaya: Rp <span id="costValue">-</span></small>
            </div>

            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-body p-4">
                            <h5 class="card-title"><?php echo htmlspecialchars($detail['nama_stasiun']); ?></h5>
                            
                            <div class="info-item">
                                <strong>📍 Alamat</strong>
                                <?php echo htmlspecialchars($detail['alamat']); ?>
                            </div>

                            <div class="info-item">
                                <strong>🔋 Ketersediaan Baterai</strong>
                                <?php 
                                $stokInfo = getStokInfo($detail['total_stok']); 
                                ?>
                                <span class="stock-badge <?php echo $stokInfo['class']; ?>">
                                    <?php echo $stokInfo['label']; ?>: <?php echo $detail['total_stok']; ?> unit
                                </span>
                            </div>

                            <div class="info-item">
                                <strong>⏰ Jam Operasional</strong>
                                <?php echo htmlspecialchars($detail['jam_operasional'] ?? '24 Jam'); ?>
                            </div>

                            <div class="info-item">
                                <strong>📞 Nomor Kontak</strong>
                                <?php if (!empty($detail['nomor_kontak'])): ?>
                                    <a href="tel:<?php echo htmlspecialchars($detail['nomor_kontak']); ?>" 
                                       style="color: #60a5fa; text-decoration: none;">
                                        <?php echo htmlspecialchars($detail['nomor_kontak']); ?>
                                    </a>
                                    <?php if (!empty($detail['nama_mitra'])): ?>
                                        <br><small style="color: #94a3b8;">
                                            Operator: <?php echo htmlspecialchars($detail['nama_mitra']); ?>
                                        </small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #94a3b8;">Tidak tersedia</span>
                                <?php endif; ?>
                            </div>

                            <div class="info-item">
                                <strong>⭐ Rating</strong>
                                <span class="rating">
                                    <?php 
                                    $rating = floatval($detail['rating'] ?? 0);
                                    echo str_repeat('⭐', floor($rating));
                                    echo ($rating - floor($rating) >= 0.5) ? '½' : '';
                                    ?>
                                    (<?php echo number_format($rating, 1); ?>/5.0)
                                </span>
                            </div>

                            <div class="info-item">
                                <strong>🔌 Kapasitas</strong>
                                <?php echo $detail['kapasitas']; ?> slot charging
                            </div>

                            <?php if ($detail['total_stok'] == 0): ?>
                                <div class="alert alert-danger mt-3">
                                    ⚠️ <strong>Perhatian!</strong> Stok baterai habis. Pertimbangkan stasiun lain atau hubungi operator.
                                </div>
                            <?php elseif ($detail['total_stok'] <= 3): ?>
                                <div class="alert alert-warning mt-3">
                                    ⚠️ <strong>Stok terbatas!</strong> Hubungi stasiun terlebih dahulu sebelum berkunjung.
                                </div>
                            <?php endif; ?>

                            <div class="d-grid gap-2 mt-4">
                                <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo $detail['latitude']; ?>,<?php echo $detail['longitude']; ?>" 
                                   target="_blank" 
                                   class="btn btn-primary btn-lg">
                                    🧭 Navigasi dengan Google Maps
                                </a>
                                <button id="getCurrentLocation" class="btn btn-success btn-lg">
                                    📍 Hitung Jarak dari Lokasi Saya
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div id="map"></div>
                </div>
            </div>

        <?php else: ?>
            <!-- LIST VIEW -->
            <h2 class="d-none d-md-block">🗺️ Daftar Stasiun Pengisian</h2>
            
            <?php if (empty($stasiun)): ?>
                <div class="card" style="text-align: center; padding: 60px 20px;">
                    <div style="font-size: 4rem; margin-bottom: 20px;">🏪</div>
                    <h4 style="color: #60a5fa; margin-bottom: 16px;">Belum Ada Stasiun Terdaftar</h4>
                    <p style="color: #cbd5e1; margin-bottom: 24px;">
                        Sistem baru dimulai. Stasiun pengisian akan muncul di sini setelah mitra mendaftar dan menambahkan stasiun mereka.
                    </p>
                    <div style="background: rgba(96, 165, 250, 0.1); padding: 20px; border-radius: 12px; max-width: 500px; margin: 0 auto;">
                        <p style="margin: 0; font-size: 0.9rem;">
                            <strong>💡 Info:</strong> Database sudah siap. Data akan terisi otomatis saat ada pendaftaran mitra dan penambahan stasiun baru.
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($stasiun as $s): 
                        $stokInfo = getStokInfo($s['total_stok']);
                    ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3" style="color: #60a5fa;">
                                        <?php echo htmlspecialchars($s['nama_stasiun']); ?>
                                    </h6>
                                    <p class="small mb-2">
                                        <strong>📍</strong> <?php echo htmlspecialchars($s['alamat']); ?>
                                    </p>
                                    <p class="mb-2">
                                        <span class="stock-badge <?php echo $stokInfo['class']; ?>">
                                            <?php echo $stokInfo['label']; ?>: <?php echo $s['total_stok']; ?>
                                        </span>
                                    </p>
                                    <p class="small mb-3">
                                        <strong>⭐</strong> <?php echo number_format($s['rating'] ?? 0, 1); ?>/5.0
                                    </p>
                                    <a href="?id=<?php echo $s['id_stasiun']; ?>" 
                                       class="btn btn-primary w-100">
                                        Detail Stasiun →
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- BOTTOM NAVIGATION (MOBILE) -->
    <?php include '../components/bottom-nav.php'; ?>

    <!-- BOOTSTRAP JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- LEAFLET JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    
    <script>
        // Theme initialization
        (function() {
            const savedTheme = localStorage.getItem("theme");
            if (savedTheme === "light") {
                document.body.classList.add("light");
                const desktopBtn = document.getElementById("toggleTheme");
                const mobileBtn = document.getElementById("mobileThemeToggle");
                if (desktopBtn) desktopBtn.textContent = "☀️";
                if (mobileBtn) mobileBtn.textContent = "☀️";
            }
        })();

        <?php if ($detail): ?>
        // INISIALISASI MAP
        const map = L.map('map').setView([<?php echo $detail['latitude']; ?>, <?php echo $detail['longitude']; ?>], 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap',
            maxZoom: 19
        }).addTo(map);

        const stationIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        const stationMarker = L.marker([<?php echo $detail['latitude']; ?>, <?php echo $detail['longitude']; ?>], {
            icon: stationIcon
        }).addTo(map);
        
        stationMarker.bindPopup('<b><?php echo htmlspecialchars($detail['nama_stasiun']); ?></b><br><?php echo htmlspecialchars($detail['alamat']); ?>').openPopup();

        let userMarker = null;
        let routeLine = null;

        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) ** 2 + 
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
                      Math.sin(dLon / 2) ** 2;
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }

        document.getElementById('getCurrentLocation').addEventListener('click', () => {
            const btn = document.getElementById('getCurrentLocation');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<span>🔄</span> Mencari lokasi...';
            btn.disabled = true;

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(pos => {
                    const userLat = pos.coords.latitude;
                    const userLng = pos.coords.longitude;

                    if (userMarker) map.removeLayer(userMarker);
                    if (routeLine) map.removeLayer(routeLine);

                    const userIcon = L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    });

                    userMarker = L.marker([userLat, userLng], { icon: userIcon })
                        .addTo(map)
                        .bindPopup('📍 Lokasi Anda')
                        .openPopup();

                    routeLine = L.polyline([
                        [userLat, userLng],
                        [<?php echo $detail['latitude']; ?>, <?php echo $detail['longitude']; ?>]
                    ], {
                        color: '#3b82f6',
                        weight: 4,
                        dashArray: '10, 10',
                        opacity: 0.8
                    }).addTo(map);

                    map.fitBounds([
                        [userLat, userLng],
                        [<?php echo $detail['latitude']; ?>, <?php echo $detail['longitude']; ?>]
                    ], { padding: [50, 50] });

                    const distance = calculateDistance(
                        userLat, userLng,
                        <?php echo $detail['latitude']; ?>,
                        <?php echo $detail['longitude']; ?>
                    );

                    const estimatedTime = (distance / 60) * 60;
                    const estimatedCost = distance * 2000 * 0.15;

                    document.getElementById('distanceBox').style.display = 'block';
                    document.getElementById('distanceValue').textContent = distance.toFixed(2) + ' km';
                    document.getElementById('timeValue').textContent = estimatedTime.toFixed(0);
                    document.getElementById('costValue').textContent = estimatedCost.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ".");

                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                }, err => {
                    alert('Gagal mendapatkan lokasi: ' + err.message);
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                });
            } else {
                alert('Browser Anda tidak mendukung geolocation.');
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }
        });
        <?php endif; ?>

        // Theme toggle
        const toggleButton = document.getElementById("toggleTheme");
        if (toggleButton) {
            const savedTheme = localStorage.getItem("theme");
            if (savedTheme === "light") {
                document.body.classList.add("light");
                toggleButton.textContent = "☀️";
            } else {
                toggleButton.textContent = "🌙";
            }

            toggleButton.addEventListener("click", () => {
                document.body.classList.toggle("light");
                const isLight = document.body.classList.contains("light");
                toggleButton.textContent = isLight ? "☀️" : "🌙";
                localStorage.setItem("theme", isLight ? "light" : "dark");
                
                const mobileBtn = document.getElementById("mobileThemeToggle");
                if (mobileBtn) mobileBtn.textContent = isLight ? "☀️" : "🌙";
            });
        }

        const mobileToggleButton = document.getElementById("mobileThemeToggle");
        if (mobileToggleButton) {
            const savedTheme = localStorage.getItem("theme");
            if (savedTheme === "light") {
                document.body.classList.add("light");
                mobileToggleButton.textContent = "☀️";
            } else {
                mobileToggleButton.textContent = "🌙";
            }

            mobileToggleButton.addEventListener("click", () => {
                document.body.classList.toggle("light");
                const isLight = document.body.classList.contains("light");
                mobileToggleButton.textContent = isLight ? "☀️" : "🌙";
                localStorage.setItem("theme", isLight ? "light" : "dark");
                
                const desktopBtn = document.getElementById("toggleTheme");
                if (desktopBtn) desktopBtn.textContent = isLight ? "☀️" : "🌙";
            });
        }

        // Prevent zoom on double tap (iOS)
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function (event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
    </script>
    
    <script src="../js/clean-url.js"></script>
</body>
</html>