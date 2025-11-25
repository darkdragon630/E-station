<?php
session_start();
require_once '../config/koneksi.php';
require_once '../pesan/alerts.php';

// Cek authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mitra') {
    header("Location: ../auth/login.php");
    exit;
}

$id_mitra = $_SESSION['user_id'];

try {
    // mengambil data mitra 
    $stmt = $koneksi->prepare("SELECT nama_mitra, status FROM mitra WHERE id_mitra = ?");
    $stmt->execute([$id_mitra]);
    $dataMitra = $stmt->fetch(PDO::FETCH_ASSOC);

    // hitung jumlah stasiun pengisian yang aktif
    $stmt = $koneksi->prepare("SELECT COUNT(*) FROM stasiun_pengisian WHERE id_mitra = ? AND status = 'disetujui' AND status_operasional = 'aktif'");
    $stmt->execute([$id_mitra]);
    $jumlahStasiun = $stmt->fetchColumn();

    // Hitung transaksi bulan ini
    $stmt = $koneksi->prepare("
        SELECT COUNT(*) FROM transaksi t
        JOIN stasiun_pengisian sp ON t.id_stasiun = sp.id_stasiun
        WHERE sp.id_mitra = ? AND MONTH(t.tanggal_transaksi) = MONTH(CURRENT_DATE())
        AND YEAR(t.tanggal_transaksi) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute([$id_mitra]);
    $jumlahTransaksi = $stmt->fetchColumn();

    // Hitung total pendapatan bulan ini
    $stmt = $koneksi->prepare("
        SELECT COALESCE(SUM(t.total_harga), 0) FROM transaksi t
        JOIN stasiun_pengisian sp ON t.id_stasiun = sp.id_stasiun
        WHERE sp.id_mitra = ? AND MONTH(t.tanggal_transaksi) = MONTH(CURRENT_DATE())
        AND YEAR(t.tanggal_transaksi) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute([$id_mitra]);
    $totalPendapatan = $stmt->fetchColumn();

    // Hitung total stok baterai dari semua stasiun milik mitra
    $stmt = $koneksi->prepare("
        SELECT COALESCE(SUM(sb.jumlah), 0) as total_stok
        FROM stok_baterai sb
        JOIN stasiun_pengisian sp ON sb.id_stasiun = sp.id_stasiun
        WHERE sp.id_mitra = ?
    ");
    $stmt->execute([$id_mitra]);
    $totalStokBaterai = $stmt->fetchColumn();

    // Ambil daftar stasiun milik mitra
    $stmt = $koneksi->prepare("
        SELECT id_stasiun, nama_stasiun, alamat, kapasitas, status, status_operasional, created_at, alasan_penolakan
        FROM stasiun_pengisian 
        WHERE id_mitra = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$id_mitra]);
    $daftarStasiun = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambil notifikasi terbaru
    $stmt = $koneksi->prepare("
        SELECT judul, pesan, dikirim_pada, dibaca 
        FROM notifikasi 
        WHERE id_penerima = ? AND tipe_penerima = 'mitra'
        ORDER BY dikirim_pada DESC LIMIT 5
    ");
    $stmt->execute([$id_mitra]);
    $notifikasi = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Hitung notifikasi belum dibaca
    $stmt = $koneksi->prepare("
        SELECT COUNT(*) FROM notifikasi 
        WHERE id_penerima = ? AND tipe_penerima = 'mitra' AND dibaca = 0
    ");
    $stmt->execute([$id_mitra]);
    $notifBelumDibaca = $stmt->fetchColumn();

} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    if (!isset($dataMitra)) {
        $dataMitra = ['nama_mitra' => $_SESSION['nama'] ?? 'Mitra', 'status' => 'pending'];
    }
    $jumlahStasiun = 0;
    $jumlahTransaksi = 0;
    $totalPendapatan = 0;
    $totalStokBaterai = 0;
    $daftarStasiun = [];
    $notifikasi = [];
    $notifBelumDibaca = 0;
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#0f2746">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <title>Dashboard Mitra — E-Station</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/mitra-style.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="../css/alert.css?v=<?= time(); ?>">
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
        <div class="logo">
            <i class="fas fa-bolt"></i>
            E-Station Mitra
        </div>
        <div class="header-actions">
            <button id="mobileThemeToggle">🌙</button>
            <button onclick="window.location.href='notifications.php'">
                <i class="fas fa-bell"></i>
            </button>
        </div>
    </div>
    <div class="welcome-text">
        <h2>Hai, <?= htmlspecialchars($dataMitra['nama_mitra'] ?? 'Mitra'); ?>! 👋</h2>
        <p>Kelola stasiun pengisian Anda dengan mudah</p>
    </div>
</div>

<!-- CONTENT -->
<div class="container mt-md-5 mb-5">
    <?php tampilkan_alert(); ?>
    
    <!-- DESKTOP WELCOME -->
    <h2 class="fw-bold mb-3 d-none d-md-block">👋 Selamat Datang, <?= htmlspecialchars($dataMitra['nama_mitra'] ?? 'Mitra'); ?>!</h2>
    <p class="mb-4 d-none d-md-block">Kelola stasiun pengisian dan pantau performa usaha Anda dengan mudah</p>

    <!-- MOBILE QUICK STATS -->
    <div class="stats-grid d-md-none">
        <div class="stat-card" style="background: linear-gradient(135deg, rgba(124, 58, 237, 0.2), rgba(168, 85, 247, 0.15)); border: 1px solid rgba(168, 85, 247, 0.3);">
            <i class="fas fa-charging-station" style="color: #a855f7;"></i>
            <h4><?= $jumlahStasiun ?? 0; ?></h4>
            <small>Stasiun Aktif</small>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(220, 38, 38, 0.15)); border: 1px solid rgba(239, 68, 68, 0.3);">
            <i class="fas fa-battery-three-quarters" style="color: #ef4444;"></i>
            <h4><?= $totalStokBaterai ?? 0; ?></h4>
            <small>Stok Baterai</small>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, rgba(6, 182, 212, 0.2), rgba(8, 145, 178, 0.15)); border: 1px solid rgba(6, 182, 212, 0.3);">
            <i class="fas fa-receipt" style="color: #06b6d4;"></i>
            <h4><?= $jumlahTransaksi ?? 0; ?></h4>
            <small>Transaksi</small>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(5, 150, 105, 0.15)); border: 1px solid rgba(16, 185, 129, 0.3);">
            <i class="fas fa-check-circle" style="color: #10b981;"></i>
            <h4><?= ucfirst($dataMitra['status'] ?? 'Pending'); ?></h4>
            <small>Status</small>
        </div>
    </div>

    <!-- Quick Stats Desktop -->
    <div class="row mb-4 d-none d-md-flex">
        <div class="col-md-3 mb-3">
            <div class="card text-center" style="background: linear-gradient(135deg, #7b61ff, #ff6b9a); color: white;">
                <div class="card-body">
                    <i class="fas fa-charging-station fa-2x mb-2"></i>
                    <h4 class="mb-0"><?= $jumlahStasiun ?? 0; ?></h4>
                    <small>Stasiun Aktif</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center" style="background: linear-gradient(135deg, #ff7aa2, #ffb166); color: white;">
                <div class="card-body">
                    <i class="fas fa-battery-three-quarters fa-2x mb-2"></i>
                    <h4 class="mb-0"><?= $totalStokBaterai ?? 0; ?></h4>
                    <small>Total Stok Baterai</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center" style="background: linear-gradient(135deg, #44d8ff, #5ee6c8); color: white;">
                <div class="card-body">
                    <i class="fas fa-receipt fa-2x mb-2"></i>
                    <h4 class="mb-0"><?= $jumlahTransaksi ?? 0; ?></h4>
                    <small>Transaksi Bulan Ini</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center" style="background: linear-gradient(135deg, #31d28a, #2bd6ff); color: white;">
                <div class="card-body">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <h4 class="mb-0"><?= ucfirst($dataMitra['status'] ?? 'Pending'); ?></h4>
                    <small>Status Akun</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">

        <!-- KELOLA STASIUN -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <h5 class="card-title"><i class="fas fa-charging-station me-2"></i>Stasiun Saya</h5>
                <?php if (!empty($daftarStasiun)): ?>
                    <div class="mb-3">
                        <p class="mb-2"><strong><?= htmlspecialchars($daftarStasiun[0]['nama_stasiun']); ?></strong></p>
                        <p class="mb-2">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <small><?= htmlspecialchars(substr($daftarStasiun[0]['alamat'], 0, 40)); ?>...</small>
                        </p>
                        <span class="status-badge status-<?= $daftarStasiun[0]['status'] == 'disetujui' ? 'approved' : ($daftarStasiun[0]['status'] == 'ditolak' ? 'rejected' : 'pending'); ?>">
                            <?= ucfirst($daftarStasiun[0]['status']); ?>
                        </span>
                    </div>
                    <a href="kelola_stasiun.php" class="btn btn-sm btn-outline-primary w-100">
                        <i class="fas fa-cog me-1"></i>Kelola Stasiun (<?= count($daftarStasiun); ?>)
                    </a>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="fas fa-map-marker-alt fa-3x text-muted mb-3" style="opacity: 0.3;"></i>
                        <p class="text-muted mb-3">Belum ada stasiun terdaftar</p>
                        <a href="tambah_stasiun.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Tambah Stasiun
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- NOTIFIKASI -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <h5 class="card-title"><i class="fas fa-bell me-2"></i>Notifikasi</h5>
                <?php if (!empty($notifikasi)): ?>
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
                        <p class="text-muted">Tidak ada notifikasi baru</p>
                        <small class="text-muted">Anda akan menerima update di sini</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- LAPORAN & RIWAYAT -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <h5 class="card-title"><i class="fas fa-chart-line me-2"></i>Laporan & Riwayat</h5>
                <div class="py-3">
                    <div class="d-grid gap-2">
                        <a href="stok_baterai.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-battery-three-quarters me-1"></i>Kelola Stok Baterai
                        </a>
                        <a href="usage_history.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-history me-1"></i>Riwayat Penggunaan
                        </a>
                        <a href="reports.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-file-invoice-dollar me-1"></i>Laporan Pendapatan
                        </a>
                        <a href="edit_profile.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-user-edit me-1"></i>Edit Profil
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- STATUS PENGAJUAN STASIUN -->
    <div class="station-form" id="status-table">
        <h4><i class="fas fa-list-check me-2"></i>Status Pengajuan Stasiun</h4>
        <p class="form-description">Pantau status pengajuan stasiun Anda.</p>
        
        <div class="table-responsive">
            <table class="status-table">
                <thead>
                    <tr>
                        <th>Nama Stasiun</th>
                        <th>Tanggal</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarStasiun)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2" style="opacity: 0.3;"></i>
                            <p class="mb-0">Belum ada pengajuan stasiun</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($daftarStasiun as $stasiun): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($stasiun['nama_stasiun']); ?></strong>
                            <br><small class="text-muted"><?= htmlspecialchars(substr($stasiun['alamat'], 0, 30)); ?>...</small>
                        </td>
                        <td><?= date('d/m/Y', strtotime($stasiun['created_at'])); ?></td>
                        <td class="text-center">
                            <span class="status-badge status-<?= $stasiun['status'] == 'disetujui' ? 'approved' : ($stasiun['status'] == 'ditolak' ? 'rejected' : 'pending'); ?>">
                                <?= ucfirst($stasiun['status']); ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="detail_stasiun.php?id=<?= $stasiun['id_stasiun']; ?>" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TIPS SECTION -->
    <div class="card" style="background: linear-gradient(135deg, rgba(123, 97, 255, 0.1), rgba(255, 107, 154, 0.1)); border: 1px solid rgba(123, 97, 255, 0.3);">
        <div class="card-body">
            <h5 class="card-title"><i class="fas fa-lightbulb me-2" style="color: #fbbf24;"></i>Tips Pengelolaan Stasiun</h5>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h6><i class="fas fa-map-marker-alt me-2" style="color: #7b61ff;"></i>Lengkapi Informasi</h6>
                    <p class="small mb-0">Pastikan semua data stasiun lengkap dan akurat termasuk koordinat lokasi</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h6><i class="fas fa-battery-three-quarters me-2" style="color: #ff7aa2;"></i>Pantau Stok Baterai</h6>
                    <p class="small mb-0">Selalu pastikan stok baterai mencukupi untuk melayani pelanggan</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h6><i class="fas fa-chart-line me-2" style="color: #31d28a;"></i>Analisis Data</h6>
                    <p class="small mb-0">Gunakan laporan untuk memantau performa dan pendapatan stasiun</p>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- BOTTOM NAVIGATION (MOBILE) -->
<?php include '../components/bottom-nav-mitra.php'; ?>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/clean-url.js?v=<?= time(); ?>"></script>
<script src="../js/mitra-dashboard.js?v=<?= time(); ?>"></script>

</body>
</html>