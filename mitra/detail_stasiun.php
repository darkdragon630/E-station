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
$id_stasiun = isset($_GET['id']) ? intval($_GET['id']) : 0;

// DEBUG MODE - Aktifkan untuk cek masalah
// error_log("DEBUG - ID Mitra: $id_mitra, ID Stasiun: $id_stasiun");

if ($id_stasiun <= 0) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'message' => 'ID Stasiun tidak valid'
    ];
    header("Location: dashboard.php");
    exit;
}

try {
    // DEBUG: Tampilkan query yang dijalankan
    // error_log("Query Detail Stasiun - ID Stasiun: $id_stasiun, ID Mitra: $id_mitra");
    
    // Ambil detail stasiun
    $stmt = $koneksi->prepare("
        SELECT 
            sp.*,
            COALESCE(SUM(sb.jumlah), 0) as total_stok
        FROM stasiun_pengisian sp
        LEFT JOIN stok_baterai sb ON sp.id_stasiun = sb.id_stasiun
        WHERE sp.id_stasiun = ? AND sp.id_mitra = ?
        GROUP BY sp.id_stasiun
    ");
    $stmt->execute([$id_stasiun, $id_mitra]);
    $stasiun = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // DEBUG: Cek apakah data ditemukan
    // error_log("Stasiun ditemukan: " . ($stasiun ? 'YA' : 'TIDAK'));

    if (!$stasiun) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => 'Stasiun tidak ditemukan atau bukan milik Anda'
        ];
        header("Location: dashboard.php");
        exit;
    }

    // Ambil detail stok baterai per tipe
    $stmt = $koneksi->prepare("
        SELECT tipe_baterai, jumlah, terakhir_update
        FROM stok_baterai
        WHERE id_stasiun = ?
        ORDER BY tipe_baterai
    ");
    $stmt->execute([$id_stasiun]);
    $stokBaterai = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambil riwayat transaksi 10 terakhir
    $stmt = $koneksi->prepare("
        SELECT 
            t.id_transaksi,
            t.tanggal_transaksi,
            t.jumlah_kwh,
            t.total_harga,
            t.status_pembayaran,
            p.nama as nama_pengguna,
            p.email
        FROM transaksi t
        JOIN pengguna p ON t.id_pengguna = p.id_pengguna
        WHERE t.id_stasiun = ?
        ORDER BY t.tanggal_transaksi DESC
        LIMIT 10
    ");
    $stmt->execute([$id_stasiun]);
    $transaksi = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error Detail Stasiun: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'message' => 'Gagal memuat data stasiun: ' . $e->getMessage()
    ];
    header("Location: dashboard.php");
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Stasiun - <?= htmlspecialchars($stasiun['nama_stasiun']); ?></title>
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

<?php include '../components/navbar-mitra.php'; ?>

<div class="container mt-5 mb-5">
    <?php tampilkan_alert(); ?>

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-2">
                <i class="fas fa-charging-station me-2"></i>
                <?= htmlspecialchars($stasiun['nama_stasiun']); ?>
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="kelola_stasiun.php">Kelola Stasiun</a></li>
                    <li class="breadcrumb-item active">Detail</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="kelola_stasiun.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    <!-- STATUS BADGES -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Status Pengajuan</h6>
                    <span class="status-badge status-<?= $stasiun['status'] == 'disetujui' ? 'approved' : ($stasiun['status'] == 'ditolak' ? 'rejected' : 'pending'); ?> fs-5">
                        <?= ucfirst($stasiun['status']); ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Status Operasional</h6>
                    <span class="badge bg-<?= $stasiun['status_operasional'] == 'aktif' ? 'success' : ($stasiun['status_operasional'] == 'nonaktif' ? 'danger' : 'warning'); ?> fs-6">
                        <?= ucfirst($stasiun['status_operasional'] ?? 'Belum Diatur'); ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Stok Baterai</h6>
                    <h3 class="mb-0">
                        <i class="fas fa-battery-three-quarters text-success"></i>
                        <?= intval($stasiun['total_stok']); ?>
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <!-- INFORMASI STASIUN -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <h5 class="card-title"><i class="fas fa-info-circle me-2"></i>Informasi Stasiun</h5>
                <table class="table table-borderless">
                    <tr>
                        <td width="40%"><strong>Nama Stasiun</strong></td>
                        <td><?= htmlspecialchars($stasiun['nama_stasiun']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Alamat</strong></td>
                        <td><?= htmlspecialchars($stasiun['alamat']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Kapasitas</strong></td>
                        <td><?= $stasiun['kapasitas']; ?> kendaraan</td>
                    </tr>
                    <tr>
                        <td><strong>Jumlah Slot</strong></td>
                        <td><?= $stasiun['jumlah_slot'] ?? 'N/A'; ?> slot</td>
                    </tr>
                    <tr>
                        <td><strong>Tarif per kWh</strong></td>
                        <td>Rp <?= number_format($stasiun['tarif_per_kwh'], 0, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Jam Operasional</strong></td>
                        <td><?= htmlspecialchars($stasiun['jam_operasional'] ?? 'Belum diatur'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Fasilitas</strong></td>
                        <td><?= htmlspecialchars($stasiun['fasilitas'] ?? 'Tidak ada'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Koordinat</strong></td>
                        <td>
                            <?php if (!empty($stasiun['latitude']) && !empty($stasiun['longitude'])): ?>
                                <a href="https://www.google.com/maps?q=<?= $stasiun['latitude']; ?>,<?= $stasiun['longitude']; ?>" target="_blank">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= $stasiun['latitude']; ?>, <?= $stasiun['longitude']; ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">Belum diatur</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- STOK BATERAI -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <h5 class="card-title"><i class="fas fa-battery-full me-2"></i>Stok Baterai</h5>
                <?php if (!empty($stokBaterai)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Tipe Baterai</th>
                                    <th class="text-center">Jumlah</th>
                                    <th>Update Terakhir</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stokBaterai as $stok): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($stok['tipe_baterai']); ?></strong></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $stok['jumlah'] > 10 ? 'success' : ($stok['jumlah'] > 5 ? 'warning' : 'danger'); ?>">
                                            <?= $stok['jumlah']; ?>
                                        </span>
                                    </td>
                                    <td><small class="text-muted"><?= date('d/m/Y H:i', strtotime($stok['terakhir_update'])); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="stok_baterai.php?id_stasiun=<?= $id_stasiun; ?>" class="btn btn-sm btn-primary w-100">
                        <i class="fas fa-edit me-1"></i>Kelola Stok
                    </a>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-battery-empty fa-3x text-muted mb-3" style="opacity: 0.3;"></i>
                        <p class="text-muted mb-3">Belum ada stok baterai</p>
                        <a href="stok_baterai.php?id_stasiun=<?= $id_stasiun; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i>Tambah Stok
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ALASAN PENOLAKAN (Jika Ditolak) -->
    <?php if ($stasiun['status'] == 'ditolak' && !empty($stasiun['alasan_penolakan'])): ?>
    <div class="alert alert-danger">
        <h6 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Alasan Penolakan</h6>
        <p class="mb-0"><?= htmlspecialchars($stasiun['alasan_penolakan']); ?></p>
        <hr>
        <small>Silakan perbaiki data stasiun Anda sesuai catatan di atas dan ajukan kembali.</small>
    </div>
    <?php endif; ?>

    <!-- RIWAYAT TRANSAKSI -->
    <div class="card">
        <h5 class="card-title"><i class="fas fa-history me-2"></i>Riwayat Transaksi (10 Terakhir)</h5>
        <?php if (!empty($transaksi)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Pengguna</th>
                            <th class="text-end">kWh</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transaksi as $t): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($t['tanggal_transaksi'])); ?></td>
                            <td>
                                <strong><?= htmlspecialchars($t['nama_pengguna']); ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($t['email']); ?></small>
                            </td>
                            <td class="text-end"><?= number_format($t['jumlah_kwh'], 2, ',', '.'); ?></td>
                            <td class="text-end">Rp <?= number_format($t['total_harga'], 0, ',', '.'); ?></td>
                            <td class="text-center">
                                <span class="badge bg-<?= $t['status_pembayaran'] == 'selesai' ? 'success' : 'warning'; ?>">
                                    <?= ucfirst($t['status_pembayaran']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <a href="usage_history.php?id_stasiun=<?= $id_stasiun; ?>" class="btn btn-sm btn-outline-primary w-100">
                <i class="fas fa-list me-1"></i>Lihat Semua Riwayat
            </a>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-receipt fa-3x text-muted mb-3" style="opacity: 0.3;"></i>
                <p class="text-muted mb-0">Belum ada transaksi</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- AKSI -->
    <div class="card mt-4">
        <h5 class="card-title"><i class="fas fa-tools me-2"></i>Aksi</h5>
        <div class="d-grid gap-2">
            <?php if ($stasiun['status'] == 'disetujui'): ?>
                <a href="edit_stasiun.php?id=<?= $id_stasiun; ?>" class="btn btn-primary">
                    <i class="fas fa-edit me-2"></i>Edit Informasi Stasiun
                </a>
                <a href="stok_baterai.php?id_stasiun=<?= $id_stasiun; ?>" class="btn btn-success">
                    <i class="fas fa-battery-full me-2"></i>Kelola Stok Baterai
                </a>
            <?php elseif ($stasiun['status'] == 'ditolak'): ?>
                <a href="edit_stasiun.php?id=<?= $id_stasiun; ?>" class="btn btn-warning">
                    <i class="fas fa-redo me-2"></i>Perbaiki & Ajukan Ulang
                </a>
            <?php else: ?>
                <button class="btn btn-secondary" disabled>
                    <i class="fas fa-clock me-2"></i>Menunggu Persetujuan Admin
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../components/bottom-nav-mitra.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/clean-url.js"></script>
<script src="../js/mitra-dashboard.js?v=<?= time(); ?>"></script>
</body>
</html>