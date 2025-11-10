<?php
session_start();
require_once '../config/koneksi.php';
require_once '../pesan/alert.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if (!isset($koneksi)) {
    die("Koneksi database tidak tersedia. Periksa file config/koneksi.php.");
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
            SELECT id_stasiun, nama_stasiun, alamat, latitude, longitude, 
                   0 AS total_stok
            FROM stasiun_pengisian 
            WHERE status_operasional = 'aktif'
        ");
    }
    
    $stasiun = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!$stasiun) {
        $stasiun = [];
    }
} catch (PDOException $e) {
    $stasiun = [];
    $error_message = "Error database: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Lokasi Stasiun - E-Station</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

* {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* === DARK MODE DEFAULT === */
body {
    background: linear-gradient(135deg, #0a192f 0%, #1a237e 50%, #0d47a1 100%);
    color: #e2e8f0;
    transition: background 0.6s ease, color 0.3s ease;
    min-height: 100vh;
    position: relative;
    overflow-x: hidden;
}

/* Animated Background Particles */
body::before {
    content: '';
    position: fixed;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    background: 
        radial-gradient(circle at 20% 30%, rgba(59, 130, 246, 0.15) 0%, transparent 50%),
        radial-gradient(circle at 80% 70%, rgba(96, 165, 250, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 50% 50%, rgba(14, 165, 233, 0.08) 0%, transparent 50%);
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

/* Navbar Glass Effect */
.navbar {
    background: rgba(15, 23, 42, 0.75) !important;
    backdrop-filter: blur(20px) saturate(180%);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.navbar:hover {
    background: rgba(15, 23, 42, 0.85) !important;
}

.navbar-brand {
    color: #e2e8f0 !important;
    font-weight: 800;
    font-size: 1.5rem;
    letter-spacing: -0.5px;
    transition: all 0.3s ease;
    text-shadow: 0 0 20px rgba(96, 165, 250, 0.5);
}

.navbar-brand:hover {
    color: #60a5fa !important;
    transform: translateY(-2px);
    text-shadow: 0 0 30px rgba(96, 165, 250, 0.8);
}

.nav-link {
    color: #cbd5e1 !important;
    font-weight: 500;
    transition: all 0.3s ease;
    position: relative;
    padding: 8px 16px !important;
}

.nav-link::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    width: 0;
    height: 2px;
    background: linear-gradient(90deg, #3b82f6, #60a5fa);
    transition: all 0.3s ease;
    transform: translateX(-50%);
}

.nav-link:hover {
    color: #60a5fa !important;
    transform: translateY(-2px);
}

.nav-link:hover::after {
    width: 80%;
}

/* Map Container with Glow */
#map {
    height: 550px;
    border-radius: 20px;
    border: 2px solid rgba(96, 165, 250, 0.3);
    box-shadow: 
        0 0 40px rgba(96, 165, 250, 0.2),
        0 8px 32px rgba(0, 0, 0, 0.3),
        inset 0 0 20px rgba(96, 165, 250, 0.05);
    overflow: hidden;
    transition: all 0.3s ease;
}

#map:hover {
    border-color: rgba(96, 165, 250, 0.5);
    box-shadow: 
        0 0 60px rgba(96, 165, 250, 0.3),
        0 12px 48px rgba(0, 0, 0, 0.4),
        inset 0 0 30px rgba(96, 165, 250, 0.08);
    transform: translateY(-4px);
}

/* Search Input with Glass Effect */
.form-control {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    color: white;
    border: 1.5px solid rgba(255, 255, 255, 0.2);
    border-radius: 14px;
    padding: 14px 20px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.form-control:focus {
    background: rgba(255, 255, 255, 0.12);
    border-color: #60a5fa;
    box-shadow: 
        0 0 0 4px rgba(96, 165, 250, 0.15),
        0 8px 24px rgba(96, 165, 250, 0.2);
    color: white;
    transform: translateY(-2px);
}

.form-control::placeholder {
    color: #cbd5e1;
    font-weight: 400;
}

/* Enhanced Buttons */
.btn {
    border-radius: 12px;
    padding: 12px 24px;
    font-weight: 600;
    letter-spacing: 0.3px;
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
    background: linear-gradient(135deg, #2563eb 0%, #3b82f6 50%, #60a5fa 100%);
    box-shadow: 0 4px 16px rgba(37, 99, 235, 0.3);
}

.btn-primary:hover {
    background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 50%, #3b82f6 100%);
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(37, 99, 235, 0.5);
}

.btn-success {
    background: linear-gradient(135deg, #16a34a 0%, #22c55e 50%, #4ade80 100%);
    box-shadow: 0 4px 16px rgba(22, 163, 74, 0.3);
}

.btn-success:hover {
    background: linear-gradient(135deg, #15803d 0%, #16a34a 50%, #22c55e 100%);
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(22, 163, 74, 0.5);
}

.btn-sm {
    padding: 8px 16px;
    font-size: 0.85rem;
}

/* Card with Advanced Glass Effect */
.card {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(16px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #e2e8f0;
    border-radius: 18px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(96, 165, 250, 0.1), transparent);
    transition: left 0.5s;
}

.card:hover::before {
    left: 100%;
}

.card:hover {
    transform: translateY(-6px) scale(1.02);
    box-shadow: 
        0 12px 32px rgba(0, 0, 0, 0.2),
        0 0 30px rgba(96, 165, 250, 0.3);
    border-color: rgba(96, 165, 250, 0.5);
    background: rgba(255, 255, 255, 0.12);
}

.card-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #f1f5f9;
    margin-bottom: 8px;
}

.card-text {
    color: #cbd5e1;
    line-height: 1.5;
}

/* Stock Badge with Pulse Animation */
.stock-badge {
    padding: 8px 14px;
    border-radius: 14px;
    font-size: 0.85rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
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

/* Distance Badge */
.distance-badge {
    padding: 6px 12px;
    border-radius: 12px;
    background: linear-gradient(135deg, #0ea5e9, #38bdf8);
    color: white;
    font-size: 0.85rem;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
    display: inline-block;
}

/* Estimation Box with Gradient */
.nearest-info {
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #0ea5e9 100%);
    padding: 28px;
    border-radius: 20px;
    color: white;
    box-shadow: 
        0 0 40px rgba(59, 130, 246, 0.4),
        0 12px 32px rgba(0, 0, 0, 0.3);
    animation: fadeInUp 0.6s ease-out;
    border: 1px solid rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
}

@keyframes fadeInUp {
    from { 
        opacity: 0; 
        transform: translateY(30px);
    }
    to { 
        opacity: 1; 
        transform: translateY(0);
    }
}

.nearest-info h5 {
    font-weight: 700;
    margin-bottom: 20px;
    font-size: 1.3rem;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.nearest-info p {
    margin-bottom: 12px;
    font-size: 0.95rem;
    font-weight: 500;
}

.nearest-info strong {
    font-weight: 700;
}

/* Theme Toggle Button */
.theme-toggle {
    position: fixed;
    top: 90px;
    right: 24px;
    z-index: 1050;
}

.theme-toggle button {
    font-size: 2rem;
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(16px);
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    width: 60px;
    height: 60px;
    cursor: pointer;
    color: inherit;
    transition: all 0.4s ease;
    box-shadow: 
        0 4px 16px rgba(0, 0, 0, 0.2),
        0 0 20px rgba(96, 165, 250, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
}

.theme-toggle button:hover {
    transform: rotate(180deg) scale(1.1);
    box-shadow: 
        0 8px 24px rgba(0, 0, 0, 0.3),
        0 0 40px rgba(96, 165, 250, 0.5);
    background: rgba(255, 255, 255, 0.2);
}

.theme-toggle button:active {
    transform: rotate(180deg) scale(0.95);
}

/* Alert Messages */
.alert {
    border-radius: 16px;
    border: none;
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    padding: 16px 20px;
    font-weight: 500;
}

.alert-warning {
    background: rgba(250, 204, 21, 0.15);
    color: #fef3c7;
    border-left: 4px solid #facc15;
}

.alert-info {
    background: rgba(14, 165, 233, 0.15);
    color: #e0f2fe;
    border-left: 4px solid #0ea5e9;
}

.alert-danger {
    background: rgba(239, 68, 68, 0.15);
    color: #fee2e2;
    border-left: 4px solid #ef4444;
}

/* Page Title */
h2 {
    font-weight: 800;
    font-size: 2rem;
    background: linear-gradient(135deg, #60a5fa, #3b82f6, #2563eb);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 24px;
    text-shadow: 0 4px 12px rgba(96, 165, 250, 0.3);
}

h5 {
    font-weight: 700;
    margin-bottom: 12px;
}

/* Station List Scrollbar */
#stationList::-webkit-scrollbar {
    width: 8px;
}

#stationList::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 10px;
}

#stationList::-webkit-scrollbar-thumb {
    background: rgba(96, 165, 250, 0.5);
    border-radius: 10px;
    transition: background 0.3s;
}

#stationList::-webkit-scrollbar-thumb:hover {
    background: rgba(96, 165, 250, 0.8);
}

/* === LIGHT MODE === */
body.light {
    background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 50%, #7dd3fc 100%);
    color: #1e293b;
}

body.light::before {
    background: 
        radial-gradient(circle at 20% 30%, rgba(59, 130, 246, 0.08) 0%, transparent 50%),
        radial-gradient(circle at 80% 70%, rgba(96, 165, 250, 0.05) 0%, transparent 50%);
}

body.light .navbar {
    background: rgba(255, 255, 255, 0.8) !important;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

body.light .navbar-brand {
    color: #1e293b !important;
    text-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
}

body.light .nav-link {
    color: #475569 !important;
}

body.light .card {
    background: rgba(255, 255, 255, 0.9);
    color: #1e293b;
    border: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
}

body.light .card:hover {
    background: white;
    box-shadow: 
        0 12px 32px rgba(0, 0, 0, 0.1),
        0 0 30px rgba(59, 130, 246, 0.2);
}

body.light .card-title {
    color: #0f172a;
}

body.light .card-text {
    color: #475569;
}

body.light .form-control {
    background: rgba(255, 255, 255, 0.95);
    color: #1e293b;
    border: 1.5px solid #cbd5e1;
}

body.light .form-control:focus {
    background: white;
    border-color: #3b82f6;
    color: #1e293b;
}

body.light .form-control::placeholder {
    color: #64748b;
}

body.light #map {
    border: 2px solid rgba(59, 130, 246, 0.3);
    box-shadow: 
        0 0 40px rgba(59, 130, 246, 0.15),
        0 8px 32px rgba(0, 0, 0, 0.1);
}

body.light .nearest-info {
    background: linear-gradient(135deg, #3b82f6, #60a5fa, #38bdf8);
}

body.light .theme-toggle button {
    background: rgba(0, 0, 0, 0.05);
    border-color: rgba(0, 0, 0, 0.1);
    box-shadow: 
        0 4px 16px rgba(0, 0, 0, 0.1),
        0 0 20px rgba(59, 130, 246, 0.2);
}

body.light h2 {
    background: linear-gradient(135deg, #2563eb, #3b82f6, #60a5fa);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

body.light .alert-warning {
    background: rgba(250, 204, 21, 0.2);
    color: #854d0e;
}

body.light .alert-info {
    background: rgba(14, 165, 233, 0.15);
    color: #0c4a6e;
}

/* Responsive Design */
@media (max-width: 768px) {
    #map {
        height: 400px;
    }
    
    h2 {
        font-size: 1.5rem;
    }
    
    .theme-toggle button {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }
    
    .nearest-info {
        padding: 20px;
    }
}

/* Loading Animation */
@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading {
    animation: spin 1s linear infinite;
}
</style>
</head>
<body>
    <div class="theme-toggle">
        <button id="toggleTheme" aria-label="Ganti Tema">🌙</button>
    </div>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">⚡ E-Station</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <div class="navbar-nav">
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                    <a class="nav-link" href="transaction_history.php">Riwayat</a>
                    <a class="nav-link" href="battery_stock.php">Stok Baterai</a>
                    <a class="nav-link" href="../auth/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- CONTENT -->
    <div class="container mt-4 mb-5">
        <?php tampilkan_alert(); ?>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong>⚠️ Perhatian:</strong> <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!$tableExists): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <strong>ℹ️ Info:</strong> Tabel stok baterai belum tersedia. Semua stasiun ditampilkan dengan stok 0.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <h2 class="mb-4">🗺️ Cari Lokasi Stasiun Pengisian</h2>
        <div class="row">
            <!-- MAP -->
            <div class="col-lg-8 mb-4">
                <div class="mb-3">
                    <input id="searchInput" type="text" class="form-control" placeholder="🔍 Cari alamat atau kota...">
                    <div class="d-flex gap-2 mt-3">
                        <button id="searchBtn" class="btn btn-primary flex-grow-1">
                            <span>🔍</span> Cari Lokasi
                        </button>
                        <button id="getCurrentLocation" class="btn btn-success flex-grow-1">
                            <span>📍</span> Lokasi Saya
                        </button>
                    </div>
                </div>
                <div id="map"></div>
            </div>

            <!-- STATION LIST -->
            <div class="col-lg-4">
                <h5>📍 Stasiun Terdekat</h5>
                <small class="text-muted d-block mb-3">Diurutkan berdasarkan jarak & stok baterai</small>
                <div id="stationList" class="mt-3" style="max-height: 600px; overflow-y: auto;">
                    <?php if (empty($stasiun)): ?>
                        <div class="alert alert-warning">
                            Tidak ada stasiun aktif yang tersedia.
                        </div>
                    <?php else: ?>
                        <?php foreach ($stasiun as $s): 
                            $stock = (int)$s['total_stok'];
                            if ($stock == 0) { $stockClass = 'stock-empty'; $stockLabel='⚫ Habis'; }
                            elseif ($stock <=3) { $stockClass='stock-low'; $stockLabel='🔴 Hampir Habis'; }
                            elseif ($stock <=10) { $stockClass='stock-medium'; $stockLabel='🟡 Terbatas'; }
                            else { $stockClass='stock-high'; $stockLabel='🟢 Banyak'; }
                        ?>
                        <div class="card station-card mb-3 <?php echo $stock==0?'opacity-75':''; ?>" 
                             data-id="<?php echo $s['id_stasiun']; ?>"
                             data-lat="<?php echo $s['latitude']; ?>"
                             data-lng="<?php echo $s['longitude']; ?>"
                             data-stock="<?php echo $s['total_stok']; ?>">
                            <div class="card-body p-3">
                                <h6 class="card-title"><?php echo htmlspecialchars($s['nama_stasiun']); ?></h6>
                                <p class="card-text small mb-2"><?php echo htmlspecialchars($s['alamat']); ?></p>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge <?php echo $stockClass; ?> stock-badge">
                                        <?php echo $stockLabel; ?>: <?php echo $stock; ?> unit
                                    </span>
                                </div>
                                <span class="distance-badge" data-distance="">📏 Menghitung...</span>
                                <a href="station_detail.php?id=<?php echo $s['id_stasiun']; ?>" 
                                   class="btn btn-sm btn-primary mt-2 w-100"
                                   <?php echo $stock==0?'onclick="return confirm(\'Stok baterai habis! Yakin ingin melihat detail?\')"' : ''; ?>>
                                   Detail Stasiun →
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ESTIMATION BOX -->
        <div id="estimation" class="nearest-info mt-4" style="display:none;">
            <h5>📊 Stasiun Terdekat dari Lokasi Anda</h5>
            <div id="estimationContent"></div>
        </div>
    </div>

    <!-- LEAFLET JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        let map, userMarker, routeLine;
        const stations = <?php echo json_encode($stasiun); ?>;
        const stationMarkers = [];
        let userLocation = null;

        // INISIALISASI MAP
        map = L.map('map').setView([-6.2088,106.8456],12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
            attribution:'© OpenStreetMap',
            maxZoom:19
        }).addTo(map);

        // ICON
        const userIcon = L.icon({
            iconUrl:'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            shadowUrl:'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize:[25,41], iconAnchor:[12,41], popupAnchor:[1,-34], shadowSize:[41,41]
        });

        // MARKER STASIUN
        stations.forEach(st=>{
            const stock=parseInt(st.total_stok);
            let color='green';
            if(stock==0) color='grey';
            else if(stock<=3) color='red';
            else if(stock<=10) color='orange';

            const icon=L.icon({
                iconUrl:`https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-${color}.png`,
                shadowUrl:'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize:[25,41], iconAnchor:[12,41], popupAnchor:[1,-34], shadowSize:[41,41]
            });

            const marker=L.marker([parseFloat(st.latitude),parseFloat(st.longitude)],{icon:icon}).addTo(map);
            marker.bindPopup(`<b>${st.nama_stasiun}</b><br>${st.alamat}<br>🔋 ${st.total_stok} unit`);
            stationMarkers.push({marker:marker,data:st});
        });

        // FUNGSI JARAK
        function calculateDistance(lat1,lon1,lat2,lon2){
            const R=6371;
            const dLat=(lat2-lat1)*Math.PI/180;
            const dLon=(lon2-lon1)*Math.PI/180;
            const a=Math.sin(dLat/2)**2+Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLon/2)**2;
            const c=2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
            return R*c;
        }

        // UPDATE JARAK UNTUK SEMUA CARD
        function updateAllDistances(userLat, userLng) {
            userLocation = {lat: userLat, lng: userLng};
            
            // Update setiap card dengan jarak
            document.querySelectorAll('.station-card').forEach(card=>{
                const lat = parseFloat(card.dataset.lat);
                const lng = parseFloat(card.dataset.lng);
                const dist = calculateDistance(userLat, userLng, lat, lng);
                
                card.dataset.distance = dist;
                const badge = card.querySelector('.distance-badge');
                badge.textContent = `📏 ${dist.toFixed(2)} km`;
                badge.style.display = 'inline-block';
            });
        }

        // FUNGSI STASIUN TERDEKAT
        function findNearestStations(userLat,userLng){
            updateAllDistances(userLat, userLng);
            
            const stationsWithDistance=stations.map(st=>{
                const dist=calculateDistance(userLat,userLng,parseFloat(st.latitude),parseFloat(st.longitude));
                return {...st,distance:dist};
            });
            
            stationsWithDistance.sort((a,b)=>{
                if(Math.abs(a.distance-b.distance)<0.5)
                    return parseInt(b.total_stok)-parseInt(a.total_stok);
                return a.distance-b.distance;
            });

            const nearest=stationsWithDistance[0];

            // URUTKAN CARD
            const stationListDiv=document.getElementById('stationList');
            const cards=Array.from(document.querySelectorAll('.station-card'));
            cards.sort((a,b)=>{
                const distA=parseFloat(a.dataset.distance)||999;
                const distB=parseFloat(b.dataset.distance)||999;
                if(Math.abs(distA-distB)<0.5) return parseInt(b.dataset.stock)-parseInt(a.dataset.stock);
                return distA-distB;
            });
            cards.forEach(c=>stationListDiv.appendChild(c));

            // ESTIMASI
            const estimatedTime=(nearest.distance/60)*60;
            const estimatedCost=nearest.distance*2000*0.15;
            const stock=parseInt(nearest.total_stok);
            let stockBadge='',stockWarning='';
            if(stock==0){
                stockBadge='<span class="badge stock-empty">⚫ Habis</span>'; 
                stockWarning='<div class="alert alert-danger mt-3 mb-0">⚠️ Stok habis! Pertimbangkan stasiun lain.</div>';
            }
            else if(stock<=3){
                stockBadge='<span class="badge stock-low">🔴 Hampir Habis</span>'; 
                stockWarning='<div class="alert alert-warning mt-3 mb-0" style="background: rgba(250, 204, 21, 0.2); color: #fef3c7; border-left: 4px solid #facc15;">⚠️ Stok terbatas, hubungi stasiun terlebih dahulu.</div>';
            }
            else if(stock<=10){
                stockBadge='<span class="badge stock-medium">🟡 Terbatas</span>';
            }
            else{
                stockBadge='<span class="badge stock-high">🟢 Banyak</span>';
            }

            document.getElementById('estimation').style.display='block';
            document.getElementById('estimationContent').innerHTML=`
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>🏢 Stasiun:</strong> ${nearest.nama_stasiun}</p>
                        <p><strong>📍 Alamat:</strong> ${nearest.alamat}</p>
                        <p><strong>📏 Jarak:</strong> <span style="font-size: 1.2rem; color: #fbbf24;">${nearest.distance.toFixed(2)} km</span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>⏱️ Estimasi Waktu:</strong> <span style="font-size: 1.2rem; color: #fbbf24;">${estimatedTime.toFixed(0)} menit</span></p>
                        <p><strong>💰 Estimasi Biaya:</strong> <span style="font-size: 1.2rem; color: #fbbf24;">Rp ${estimatedCost.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g,".")}</span></p>
                        <p><strong>🔋 Ketersediaan:</strong> ${stockBadge} ${stock} unit</p>
                    </div>
                </div>
                ${stockWarning}
                <a href="station_detail.php?id=${nearest.id_stasiun}" class="btn btn-light mt-3 fw-bold" style="border-radius: 12px; padding: 12px 24px;">Detail Lengkap →</a>
            `;

            if(routeLine) map.removeLayer(routeLine);
            routeLine=L.polyline([[userLat,userLng],[parseFloat(nearest.latitude),parseFloat(nearest.longitude)]],{
                color:'#3b82f6',
                weight:4,
                dashArray:'10,10',
                opacity:0.8
            }).addTo(map);
            map.fitBounds([[userLat,userLng],[parseFloat(nearest.latitude),parseFloat(nearest.longitude)]],{padding:[50,50]});
        }

        // AUTO-DETECT LOKASI SAAT PAGE LOAD
        if(navigator.geolocation && stations.length > 0){
            navigator.geolocation.getCurrentPosition(pos=>{
                const lat=pos.coords.latitude;
                const lng=pos.coords.longitude;
                updateAllDistances(lat, lng);
                
                // Urutkan card berdasarkan jarak
                const stationListDiv=document.getElementById('stationList');
                const cards=Array.from(document.querySelectorAll('.station-card'));
                cards.sort((a,b)=>{
                    const distA=parseFloat(a.dataset.distance)||999;
                    const distB=parseFloat(b.dataset.distance)||999;
                    if(Math.abs(distA-distB)<0.5) return parseInt(b.dataset.stock)-parseInt(a.dataset.stock);
                    return distA-distB;
                });
                cards.forEach(c=>stationListDiv.appendChild(c));
            }, err=>{
                console.log('Auto-detect location disabled:', err.message);
            });
        }

        // GEOLOCATION BUTTON
        document.getElementById('getCurrentLocation').addEventListener('click',()=>{
            const btn = document.getElementById('getCurrentLocation');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<span class="loading">🔄</span> Mencari...';
            btn.disabled = true;
            
            if(navigator.geolocation){
                navigator.geolocation.getCurrentPosition(pos=>{
                    const lat=pos.coords.latitude;
                    const lng=pos.coords.longitude;
                    if(userMarker) map.removeLayer(userMarker);
                    userMarker=L.marker([lat,lng],{icon:userIcon}).addTo(map).bindPopup('📍 Lokasi Anda').openPopup();
                    map.setView([lat,lng],14);
                    findNearestStations(lat,lng);
                    
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                },err=>{
                    alert('Gagal mendapatkan lokasi: '+err.message);
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                });
            }else{
                alert('Geolocation tidak didukung browser Anda.');
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }
        });

        // SEARCH BUTTON
        document.getElementById('searchBtn').addEventListener('click',()=>{
            const query=document.getElementById('searchInput').value;
            if(!query){alert('Masukkan lokasi!'); return;}
            
            const btn = document.getElementById('searchBtn');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<span class="loading">🔄</span> Mencari...';
            btn.disabled = true;
            
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=id`)
            .then(res=>res.json())
            .then(data=>{
                if(data.length>0){
                    const lat=parseFloat(data[0].lat), lng=parseFloat(data[0].lon);
                    if(userMarker) map.removeLayer(userMarker);
                    userMarker=L.marker([lat,lng],{icon:userIcon}).addTo(map).bindPopup(`🔍 ${data[0].display_name}`).openPopup();
                    map.setView([lat,lng],14);
                    findNearestStations(lat,lng);
                }else{
                    alert('Lokasi tidak ditemukan!');
                }
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            })
            .catch(err=>{
                alert('Error: '+err.message);
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            });
        });

        // ENTER KEY SEARCH
        document.getElementById('searchInput').addEventListener('keypress',e=>{
            if(e.key==='Enter') document.getElementById('searchBtn').click();
        });

        // STATION CARD CLICK
        document.querySelectorAll('.station-card').forEach(card=>{
            card.addEventListener('click',e=>{
                if(e.target.tagName==='A') return;
                const lat=parseFloat(card.dataset.lat), lng=parseFloat(card.dataset.lng);
                map.setView([lat,lng],16);
                stationMarkers.forEach(sm=>{
                    if(sm.marker.getLatLng().lat===lat && sm.marker.getLatLng().lng===lng) 
                        sm.marker.openPopup();
                });
            });
        });

        // THEME TOGGLE
        const themeBtn=document.getElementById('toggleTheme');
        const savedTheme = localStorage.getItem('theme');
        if(savedTheme === 'light'){
            document.body.classList.add('light'); 
            themeBtn.textContent='☀️';
        } else {
            themeBtn.textContent='🌙';
        }
        
        themeBtn.addEventListener('click',()=>{
            document.body.classList.toggle('light');
            if(document.body.classList.contains('light')){
                localStorage.setItem('theme','light');
                themeBtn.textContent='☀️';
            } else {
                localStorage.setItem('theme','dark');
                themeBtn.textContent='🌙';
            }
        });
    </script>

    <!-- BOOTSTRAP JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>