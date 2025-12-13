<?php
session_start();
require_once "../config/koneksi.php";
require_once "../pesan/alerts.php";

// Security headers
session_regenerate_id(true);
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: no-referrer");

// ==================== AUTHENTICATION ====================
function checkAdminAuth() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
        set_flash_message('../auth/login.php', 'error', 'unauthorized');
    }
}

// ==================== DATA RETRIEVAL ====================
function getDashboardStatistics($koneksi) {
    try {
        $stats = [
            'pengendara' => 0,
            'mitra' => 0,
            'transaksi' => 0,
            'stasiun' => 0,
            'pending' => 0,
            'stok_baterai' => 0
        ];
        
        // Total pengendara
        $stmt = $koneksi->query("SELECT COUNT(*) AS total FROM pengendara");
        $stats['pengendara'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total mitra
        $stmt = $koneksi->query("SELECT COUNT(*) AS total FROM mitra");
        $stats['mitra'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total transaksi
        $stmt = $koneksi->query("SELECT COUNT(*) AS total FROM transaksi");
        $stats['transaksi'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total stasiun aktif
        $stmt = $koneksi->query("SELECT COUNT(*) AS total FROM stasiun_pengisian WHERE status = 'disetujui' AND status_operasional = 'aktif'");
        $stats['stasiun'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Stasiun pending
        $stmt = $koneksi->query("SELECT COUNT(*) AS total FROM stasiun_pengisian WHERE status = 'pending'");
        $stats['pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total stok baterai
        $stmt = $koneksi->query("SELECT COALESCE(SUM(jumlah), 0) AS total FROM stok_baterai");
        $stats['stok_baterai'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return $stats;
    } catch (PDOException $e) {
        error_log("Error fetching dashboard stats: " . $e->getMessage());
        return [
            'pengendara' => 0,
            'mitra' => 0,
            'transaksi' => 0,
            'stasiun' => 0,
            'pending' => 0,
            'stok_baterai' => 0
        ];
    }
}

// ==================== MAIN EXECUTION ====================
checkAdminAuth();

$nama_admin = $_SESSION['nama'];
$stats = getDashboardStatistics($koneksi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - E-Station</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/admin-style.css">
    <link rel="stylesheet" href="../css/alert.css">
    <link rel="icon" type="image/png" href="../images/Logo_1.png">

</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <?php tampilkan_alert(); ?>
    
    <!-- Top Bar -->
    <div class="top-bar">
        <h5><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h5>
        <div class="user-menu">
            <div class="notification">
                <i class="fas fa-bell"></i>
                <?php if ($stats['pending'] > 0): ?>
                    <span class="badge"><?= $stats['pending']; ?></span>
                <?php endif; ?>
            </div>
            <div class="profile">
                <div class="avatar"><?= strtoupper(substr($nama_admin, 0, 2)); ?></div>
                <div>
                    <div style="font-weight: 600; font-size: 14px;"><?= htmlspecialchars($nama_admin); ?></div>
                    <div style="font-size: 12px; color: #999;">Administrator</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Welcome Card -->
    <div class="welcome-card">
        <h4><i class="fas fa-hand-wave me-2"></i>Selamat Datang, <?= htmlspecialchars($nama_admin); ?>!</h4>
        <p>Berikut adalah ringkasan sistem E-Station hari ini</p>
    </div>
    
    <!-- Notification Card -->
    <?php if ($stats['pending'] > 0): ?>
    <div class="notif-card">
        <h6><i class="fas fa-bell me-2"></i>Notifikasi</h6>
        <div class="notif-item">
            <div>
                <i class="fas fa-clock text-warning me-2"></i>
                <strong><?= $stats['pending']; ?> Stasiun</strong> menunggu persetujuan
            </div>
            <a href="approval_stasiun.php" class="btn btn-sm btn-primary">
                <i class="fas fa-eye me-1"></i>Lihat
            </a>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-motorcycle"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($stats['pengendara']); ?></h3>
                <p>Pengendara Terdaftar</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-store"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($stats['mitra']); ?></h3>
                <p>Mitra Terdaftar</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($stats['transaksi']); ?></h3>
                <p>Total Transaksi</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-charging-station"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($stats['stasiun']); ?></h3>
                <p>Stasiun Aktif</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon danger">
                <i class="fas fa-battery-three-quarters"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($stats['stok_baterai']); ?></h3>
                <p>Total Stok Baterai</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon secondary">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($stats['pending']); ?></h3>
                <p>Stasiun Pending</p>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-bolt me-2"></i>Aksi Cepat
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="pengendara.php" class="btn btn-outline-primary">
                            <i class="fas fa-motorcycle me-2"></i>Kelola Pengendara
                        </a>
                        <a href="mitra.php" class="btn btn-outline-success">
                            <i class="fas fa-store me-2"></i>Kelola Mitra
                        </a>
                        <a href="approval_stasiun.php" class="btn btn-outline-warning">
                            <i class="fas fa-check-circle me-2"></i>Approval Stasiun
                        </a>
                        <a href="stok_baterai.php" class="btn btn-outline-danger">
                            <i class="fas fa-battery-three-quarters me-2"></i>Kelola Stok Baterai
                        </a>
                        <a href="transaksi.php" class="btn btn-outline-info">
                            <i class="fas fa-exchange-alt me-2"></i>Lihat Transaksi
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-info-circle me-2"></i>Informasi Sistem
                </div>
                <div class="card-body">
                    <div class="info-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-server text-primary me-2"></i>Status Server</span>
                            <span class="badge bg-success">Online</span>
                        </div>
                    </div>
                    <div class="info-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-database text-info me-2"></i>Database</span>
                            <span class="badge bg-success">Connected</span>
                        </div>
                    </div>
                    <div class="info-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-clock text-warning me-2"></i>Waktu Server</span>
                            <span class="text-muted"><?= date('d M Y, H:i'); ?></span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-code-branch text-secondary me-2"></i>Versi</span>
                            <span class="text-muted">v1.0.0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/clean-url.js"></script>

</body>
</html>