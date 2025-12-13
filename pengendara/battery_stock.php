<?php
session_start();
require_once '../config/koneksi.php';
require_once '../pesan/alerts.php';

// Cek authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pengendara') {
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

// Query data stok baterai per stasiun
$stokData = [];
try {
    if ($tableExists) {
        $stmt = $koneksi->query("
            SELECT 
                s.id_stasiun,
                s.nama_stasiun,
                s.alamat,
                s.status_operasional,
                sb.tipe_baterai,
                sb.jumlah,
                sb.terakhir_update
            FROM stasiun_pengisian s
            LEFT JOIN stok_baterai sb ON s.id_stasiun = sb.id_stasiun
            WHERE s.status_operasional = 'aktif'
            ORDER BY s.nama_stasiun, sb.tipe_baterai
        ");
        $stokData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error_message = "Error database: " . $e->getMessage();
}

// Fungsi untuk mengelompokkan data per stasiun
function groupByStation($data) {
    $grouped = [];
    foreach ($data as $row) {
        $id = $row['id_stasiun'];
        if (!isset($grouped[$id])) {
            $grouped[$id] = [
                'id_stasiun' => $row['id_stasiun'],
                'nama_stasiun' => $row['nama_stasiun'],
                'alamat' => $row['alamat'],
                'status_operasional' => $row['status_operasional'],
                'baterai' => []
            ];
        }
        if ($row['tipe_baterai']) {
            $grouped[$id]['baterai'][] = [
                'tipe' => $row['tipe_baterai'],
                'jumlah' => $row['jumlah'],
                'terakhir_update' => $row['terakhir_update']
            ];
        }
    }
    return $grouped;
}

$stasiunData = groupByStation($stokData);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="theme-color" content="#0a192f">
<title>Stok Baterai - E-Station</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../css/pengendara-style.css">
<link rel="stylesheet" href="../css/alert.css">
<link rel="icon" type="image/png" href="../images/Logo_1.png">

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap');

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background: linear-gradient(135deg, #0a192f 0%, #1e3a8a 50%, #312e81 100%);
    color: #e2e8f0;
    min-height: 100vh;
    transition: all 0.4s ease;
    position: relative;
    overflow-x: hidden;
}

body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(168, 85, 247, 0.1) 0%, transparent 50%);
    pointer-events: none;
    z-index: 0;
}

.container {
    position: relative;
    z-index: 1;
}

/* Page Header */
.page-header {
    text-align: center;
    margin: 40px 0;
    animation: fadeInUp 0.8s ease;
}

.page-header h1 {
    font-size: 2.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #60a5fa, #a855f7, #ec4899);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 10px;
}

.page-header p {
    color: #94a3b8;
    font-size: 1.1rem;
}

/* Station Card */
.station-card {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 20px;
    padding: 25px;
    margin-bottom: 20px;
    transition: all 0.4s ease;
}

.station-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(96, 165, 250, 0.3);
    border-color: rgba(96, 165, 250, 0.5);
}

.station-name {
    font-size: 1.3rem;
    font-weight: 700;
    color: #60a5fa;
    margin-bottom: 10px;
}

.station-address {
    color: #cbd5e1;
    font-size: 0.9rem;
    margin-bottom: 20px;
}

/* Battery Item */
.battery-item {
    background: rgba(255, 255, 255, 0.05);
    border-left: 4px solid #3b82f6;
    padding: 15px;
    border-radius: 12px;
    margin-bottom: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
}

.battery-item:hover {
    background: rgba(255, 255, 255, 0.08);
    transform: translateX(5px);
}

.battery-type {
    font-weight: 600;
    color: #e2e8f0;
    font-size: 1rem;
}

.battery-stock {
    display: flex;
    align-items: center;
    gap: 10px;
}

.stock-badge {
    padding: 8px 16px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.9rem;
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

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.empty-state i {
    font-size: 5rem;
    color: #475569;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state h3 {
    color: #94a3b8;
    margin-bottom: 10px;
}

/* Last Update */
.last-update {
    font-size: 0.8rem;
    color: #94a3b8;
    margin-top: 8px;
}

/* Light Mode */
body.light {
    background: linear-gradient(135deg, #f0f9ff 0%, #dbeafe 50%, #e0e7ff 100%);
    color: #1e293b;
}

body.light::before {
    background: 
        radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.05) 0%, transparent 50%);
}

body.light .station-card {
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(0, 0, 0, 0.1);
}

body.light .battery-item {
    background: rgba(0, 0, 0, 0.03);
}

body.light .battery-type {
    color: #1e293b;
}

body.light .empty-state {
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(0, 0, 0, 0.1);
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

/* Responsive */
@media (max-width: 768px) {
    .page-header h1 {
        font-size: 2rem;
    }
    
    .station-card {
        padding: 20px;
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
            <div class="logo">
                <i class="fas fa-bolt"></i>
                E-Station
            </div>
            <div class="header-actions">
                <button id="mobileThemeToggle">🌙</button>
            </div>
        </div>
        <div class="welcome-text">
            <h2>🔋 Stok Baterai</h2>
            <p>Cek ketersediaan baterai di setiap stasiun</p>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="container mt-md-5 mb-5">
        <?php tampilkan_alert(); ?>
        
        <!-- DESKTOP HEADER -->
        <div class="page-header d-none d-md-block">
            <h1><i class="fas fa-battery-full me-3"></i>Stok Baterai</h1>
            <p>Pantau ketersediaan baterai real-time di semua stasiun</p>
        </div>

        <?php if (!$tableExists): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <strong>ℹ️ Info:</strong> Tabel stok_baterai belum tersedia. Silakan jalankan script SQL untuk membuat struktur database.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>❌ Error:</strong> <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($stasiunData)): ?>
            <div class="empty-state">
                <i class="fas fa-battery-empty"></i>
                <h3>Belum Ada Data Stok</h3>
                <p>Data stok baterai akan muncul setelah mitra menambahkan inventori mereka</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($stasiunData as $stasiun): ?>
                    <div class="col-md-6 mb-4">
                        <div class="station-card">
                            <div class="station-name">
                                <i class="fas fa-charging-station me-2"></i>
                                <?php echo htmlspecialchars($stasiun['nama_stasiun']); ?>
                            </div>
                            <div class="station-address">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                <?php echo htmlspecialchars($stasiun['alamat']); ?>
                            </div>

                            <?php if (empty($stasiun['baterai'])): ?>
                                <div class="alert alert-warning mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Belum ada data stok baterai
                                </div>
                            <?php else: ?>
                                <?php foreach ($stasiun['baterai'] as $baterai): 
                                    $jumlah = (int)$baterai['jumlah'];
                                    if ($jumlah == 0) {
                                        $stockClass = 'stock-empty';
                                        $stockLabel = '⚫ Habis';
                                    } elseif ($jumlah <= 3) {
                                        $stockClass = 'stock-low';
                                        $stockLabel = '🔴 Rendah';
                                    } elseif ($jumlah <= 10) {
                                        $stockClass = 'stock-medium';
                                        $stockLabel = '🟡 Sedang';
                                    } else {
                                        $stockClass = 'stock-high';
                                        $stockLabel = '🟢 Banyak';
                                    }
                                ?>
                                    <div class="battery-item">
                                        <div>
                                            <div class="battery-type">
                                                <i class="fas fa-car-battery me-2"></i>
                                                <?php echo htmlspecialchars($baterai['tipe']); ?>
                                            </div>
                                            <?php if ($baterai['terakhir_update']): ?>
                                                <div class="last-update">
                                                    <i class="far fa-clock me-1"></i>
                                                    Update: <?php echo date('d M Y, H:i', strtotime($baterai['terakhir_update'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="battery-stock">
                                            <span class="stock-badge <?php echo $stockClass; ?>">
                                                <?php echo $stockLabel; ?>
                                            </span>
                                            <span style="font-size: 1.3rem; font-weight: 700; color: #60a5fa;">
                                                <?php echo $jumlah; ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- BOTTOM NAVIGATION (MOBILE) -->
    <?php include '../components/bottom-nav.php'; ?>

    <!-- SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/clean-url.js"></script>
    <script>
        // Desktop Theme Toggle
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
            });
        }

        // Mobile Theme Toggle
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
</body>
</html>