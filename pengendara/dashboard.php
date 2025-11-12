<?php
session_start();
require_once '../config/koneksi.php';
require_once '../pesan/alerts.php';

// ✅ FIXED: Cek authentication dengan konsisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pengendara') {
    header('Location: ../auth/login.php');
    exit;
}

// ✅ FIXED: Gunakan user_id sebagai id_pengendara
$id_pengendara = $_SESSION['user_id'];

try {
    // Ambil data pengendara
    $stmt = $koneksi->prepare("SELECT nama FROM pengendara WHERE id_pengendara = ?");
    $stmt->execute([$id_pengendara]);
    $dataPengendara = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ambil kendaraan
    $stmt = $koneksi->prepare("SELECT merk, model, no_plat FROM kendaraan WHERE id_pengendara = ? LIMIT 1");
    $stmt->execute([$id_pengendara]);
    $kendaraan = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ambil transaksi terbaru
    $stmt = $koneksi->prepare("SELECT t.jumlah_kwh, t.total_harga, s.nama_stasiun 
        FROM transaksi t 
        JOIN stasiun_pengisian s ON t.id_stasiun = s.id_stasiun 
        WHERE t.id_pengendara = ? 
        ORDER BY t.tanggal_transaksi DESC LIMIT 3");
    $stmt->execute([$id_pengendara]);
    $transaksi_terbaru = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambil notifikasi
    $stmt = $koneksi->prepare("SELECT judul, pesan FROM notifikasi 
        WHERE id_penerima = ? AND tipe_penerima = 'pengendara'
        ORDER BY dikirim_pada DESC LIMIT 5");
    $stmt->execute([$id_pengendara]);
    $notifikasi = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $transaksi_terbaru = [];
    $notifikasi = [];
    $kendaraan = null;
    $dataPengendara = ['nama' => $_SESSION['nama'] ?? 'Pengendara'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="theme-color" content="#0a192f">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<title>Dashboard - E-Station</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../css/pengendara-style.css">
<link rel="stylesheet" href="../css/alert.css">
</head>

<body>

<!-- DESKTOP THEME TOGGLE -->
<div class="theme-toggle">
    <button id="toggleTheme">🌙</button>
</div>

<!-- DESKTOP NAVBAR -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">⚡ E-Station</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="search_location.php"><i class="fas fa-map-marked-alt me-1"></i> Cari Lokasi</a>
                <a class="nav-link" href="transaction_history.php"><i class="fas fa-history me-1"></i> Riwayat</a>
                <a class="nav-link" href="battery_stock.php"><i class="fas fa-battery-full me-1"></i> Stok Baterai</a>
                <a class="nav-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
            </div>
        </div>
    </div>
</nav>

<!-- MOBILE HEADER -->
<div class="mobile-header d-md-none">
    <div class="header-top">
        <div class="logo">
            <i class="fas fa-bolt"></i>
            E-Station
        </div>
        <div class="header-actions">
            <button id="mobileThemeToggle">🌙</button>
            <button onclick="window.location.href='notifications.php'">
                <i class="fas fa-bell"></i>
            </button>
        </div>
    </div>
    <div class="welcome-text">
        <h2>Hai, <?= htmlspecialchars($dataPengendara['nama'] ?? 'Pengendara'); ?>! 👋</h2>
        <p>Kelola charging Anda dengan mudah</p>
    </div>
</div>

<!-- CONTENT -->
<div class="container mt-md-5 mb-5">
    <?php tampilkan_alert(); ?>
    
    <!-- DESKTOP WELCOME -->
    <h2 class="fw-bold mb-3 d-none d-md-block">👋 Selamat Datang, <?= htmlspecialchars($dataPengendara['nama'] ?? 'Pengendara'); ?>!</h2>
    <p class="mb-4 d-none d-md-block">Kelola perjalanan charging Anda dengan mudah</p>

    <!-- MOBILE QUICK STATS -->
    <div class="stats-grid d-md-none">
        <div class="stat-card" style="background: linear-gradient(135deg, rgba(124, 58, 237, 0.2), rgba(168, 85, 247, 0.15));">
            <i class="fas fa-bolt" style="color: #a855f7;"></i>
            <h4><?= count($transaksi_terbaru); ?></h4>
            <small>Transaksi</small>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(220, 38, 38, 0.15));">
            <i class="fas fa-car" style="color: #ef4444;"></i>
            <h4><?= $kendaraan ? '1' : '0'; ?></h4>
            <small>Kendaraan</small>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, rgba(6, 182, 212, 0.2), rgba(8, 145, 178, 0.15));">
            <i class="fas fa-bell" style="color: #06b6d4;"></i>
            <h4><?= count($notifikasi); ?></h4>
            <small>Notifikasi</small>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(5, 150, 105, 0.15));">
            <i class="fas fa-charging-station" style="color: #10b981;"></i>
            <h4>Aktif</h4>
            <small>Status</small>
        </div>
    </div>

    <!-- Quick Stats Desktop -->
    <div class="row mb-4 d-none d-md-flex">
        <div class="col-md-3 mb-3">
            <div class="card text-center" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
                <div class="card-body">
                    <i class="fas fa-bolt fa-2x mb-2"></i>
                    <h4 class="mb-0"><?= count($transaksi_terbaru); ?></h4>
                    <small>Transaksi Terbaru</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center" style="background: linear-gradient(135deg, #f093fb, #f5576c); color: white;">
                <div class="card-body">
                    <i class="fas fa-car fa-2x mb-2"></i>
                    <h4 class="mb-0"><?= $kendaraan ? '1' : '0'; ?></h4>
                    <small>Kendaraan Terdaftar</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center" style="background: linear-gradient(135deg, #4facfe, #00f2fe); color: white;">
                <div class="card-body">
                    <i class="fas fa-bell fa-2x mb-2"></i>
                    <h4 class="mb-0"><?= count($notifikasi); ?></h4>
                    <small>Notifikasi Baru</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center" style="background: linear-gradient(135deg, #43e97b, #38f9d7); color: white;">
                <div class="card-body">
                    <i class="fas fa-charging-station fa-2x mb-2"></i>
                    <h4 class="mb-0">Aktif</h4>
                    <small>Status Akun</small>
                </div>
            </div>
        </div>
    </div>

    <!-- MOBILE QUICK ACTIONS -->
    <div class="quick-actions d-md-none">
        <h5><i class="fas fa-bolt"></i>Aksi Cepat</h5>
        <div class="action-grid">
            <a href="search_location.php" class="action-btn">
                <i class="fas fa-map-marked-alt"></i>
                <strong>Cari Stasiun</strong>
                <small>Terdekat</small>
            </a>
            <a href="battery_stock.php" class="action-btn">
                <i class="fas fa-battery-full"></i>
                <strong>Stok Baterai</strong>
                <small>Real-time</small>
            </a>
            <a href="transaction_history.php" class="action-btn">
                <i class="fas fa-history"></i>
                <strong>Riwayat</strong>
                <small>Transaksi</small>
            </a>
            <a href="profile.php" class="action-btn">
                <i class="fas fa-user-circle"></i>
                <strong>Profil</strong>
                <small>Akun Saya</small>
            </a>
        </div>
    </div>

    <!-- Quick Actions Desktop -->
    <div class="card mb-4 d-none d-md-block" style="background: rgba(255,255,255,0.05); backdrop-filter: blur(10px);">
        <div class="card-body">
            <h5 class="card-title mb-3"><i class="fas fa-bolt me-2"></i>Aksi Cepat</h5>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <a href="search_location.php" class="btn btn-primary w-100" style="background: linear-gradient(135deg, #667eea, #764ba2); padding: 30px 20px;">
                        <i class="fas fa-map-marked-alt d-block mb-2" style="font-size: 2rem;"></i>
                        <strong>Cari Stasiun</strong>
                        <br><small>Temukan stasiun terdekat</small>
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="battery_stock.php" class="btn btn-success w-100" style="background: linear-gradient(135deg, #43e97b, #38f9d7); padding: 30px 20px;">
                        <i class="fas fa-battery-full d-block mb-2" style="font-size: 2rem;"></i>
                        <strong>Cek Stok Baterai</strong>
                        <br><small>Real-time availability</small>
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="transaction_history.php" class="btn btn-info w-100" style="background: linear-gradient(135deg, #4facfe, #00f2fe); padding: 30px 20px;">
                        <i class="fas fa-history d-block mb-2" style="font-size: 2rem;"></i>
                        <strong>Riwayat</strong>
                        <br><small>Lihat transaksi Anda</small>
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="profile.php" class="btn btn-warning w-100" style="background: linear-gradient(135deg, #f093fb, #f5576c); padding: 30px 20px;">
                        <i class="fas fa-user-circle d-block mb-2" style="font-size: 2rem;"></i>
                        <strong>Profil</strong>
                        <br><small>Kelola akun Anda</small>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">

        <!-- KENDARAAN -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <h5 class="card-title"><i class="fas fa-car me-2"></i>Kendaraan Aktif</h5>
                <?php if ($kendaraan): ?>
                    <div class="mb-3">
                        <p class="mb-2"><strong><?= htmlspecialchars($kendaraan['merk'] . ' ' . $kendaraan['model']); ?></strong></p>
                        <p class="mb-2">
                            <i class="fas fa-id-card me-2"></i>
                            <span class="badge bg-primary"><?= htmlspecialchars($kendaraan['no_plat']); ?></span>
                        </p>
                        <hr>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Kendaraan ini terdaftar untuk pengisian baterai
                        </small>
                    </div>
                    <a href="manage_vehicles.php" class="btn btn-sm btn-outline-primary w-100">
                        <i class="fas fa-cog me-1"></i>Kelola Kendaraan
                    </a>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="fas fa-car-side fa-3x text-muted mb-3" style="opacity: 0.3;"></i>
                        <p class="text-muted mb-3">Belum ada kendaraan terdaftar</p>
                        <a href="add_vehicle.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i>Tambah Kendaraan
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TRANSAKSI -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <h5 class="card-title"><i class="fas fa-receipt me-2"></i>Transaksi Terbaru</h5>
                <?php if ($transaksi_terbaru): ?>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($transaksi_terbaru as $t): ?>
                            <li class="mb-3 pb-3" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <strong style="color: #60a5fa;">
                                        <i class="fas fa-bolt me-1"></i>
                                        <?= htmlspecialchars($t['jumlah_kwh']); ?> kWh
                                    </strong>
                                    <span class="badge bg-success">
                                        Rp <?= number_format($t['total_harga'], 0, ',', '.'); ?>
                                    </span>
                                </div>
                                <small style="color: #94a3b8;">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?= htmlspecialchars($t['nama_stasiun']); ?>
                                </small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="transaction_history.php" class="btn btn-sm btn-outline-primary w-100">
                        <i class="fas fa-history me-1"></i>Lihat Semua
                    </a>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="fas fa-receipt fa-3x text-muted mb-3" style="opacity: 0.3;"></i>
                        <p class="text-muted">Belum ada transaksi</p>
                        <small style="color: #64748b;">Mulai charging untuk melihat riwayat</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- NOTIFIKASI -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <h5 class="card-title"><i class="fas fa-bell me-2"></i>Notifikasi</h5>
                <?php if ($notifikasi): ?>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($notifikasi as $n): ?>
                            <li class="mb-3 pb-3" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <strong style="color: #f59e0b;">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <?= htmlspecialchars($n['judul']); ?>
                                </strong>
                                <br>
                                <small style="color: #cbd5e1;">
                                    <?= htmlspecialchars($n['pesan']); ?>
                                </small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="notifications.php" class="btn btn-sm btn-outline-primary w-100">
                        <i class="fas fa-bell me-1"></i>Lihat Semua
                    </a>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="fas fa-bell-slash fa-3x text-muted mb-3" style="opacity: 0.3;"></i>
                        <p class="text-muted">Tidak ada notifikasi</p>
                        <small style="color: #64748b;">Anda akan menerima update di sini</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Tips Section -->
    <div class="card" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(168, 85, 247, 0.1)); border: 1px solid rgba(102, 126, 234, 0.3);">
        <div class="card-body">
            <h5 class="card-title"><i class="fas fa-lightbulb me-2" style="color: #fbbf24;"></i>Tips Penggunaan</h5>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h6><i class="fas fa-map-marked-alt me-2 text-primary"></i>Cari Stasiun Terdekat</h6>
                    <p class="small mb-0">Gunakan fitur "Cari Lokasi" untuk menemukan stasiun pengisian terdekat dari posisi Anda</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h6><i class="fas fa-battery-three-quarters me-2 text-success"></i>Cek Ketersediaan</h6>
                    <p class="small mb-0">Periksa stok baterai real-time sebelum berkunjung untuk menghindari kehabisan stok</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h6><i class="fas fa-route me-2 text-info"></i>Estimasi Perjalanan</h6>
                    <p class="small mb-0">Lihat estimasi waktu dan jarak untuk merencanakan perjalanan Anda</p>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- BOTTOM NAVIGATION (MOBILE) -->
<nav class="bottom-nav d-md-none" style="display: flex !important; flex-direction: row !important;">
    <a href="dashboard.php" class="active" style="display: flex !important; flex-direction: column !important;">
        <i class="fas fa-home"></i>
        <span>Beranda</span>
    </a>
    <a href="search_location.php" style="display: flex !important; flex-direction: column !important;">
        <i class="fas fa-map-marked-alt"></i>
        <span>Lokasi</span>
    </a>
    <a href="battery_stock.php" style="display: flex !important; flex-direction: column !important;">
        <i class="fas fa-battery-full"></i>
        <span>Stok</span>
    </a>
    <a href="transaction_history.php" style="display: flex !important; flex-direction: column !important;">
        <i class="fas fa-history"></i>
        <span>Riwayat</span>
    </a>
    <a href="profile.php" style="display: flex !important; flex-direction: column !important;">
        <i class="fas fa-user"></i>
        <span>Profil</span>
    </a>
</nav>

<!-- SCRIPT -->
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

// Active bottom nav highlight
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.bottom-nav a');
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        const href = link.getAttribute('href');
        if (href === currentPage || (currentPage === '' && href === 'dashboard.php')) {
            link.classList.add('active');
        }
    });
});
</script>

</body>
</html>