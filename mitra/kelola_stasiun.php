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
    // Ambil data mitra
    $stmt = $koneksi->prepare("SELECT nama_mitra FROM mitra WHERE id_mitra = ?");
    $stmt->execute([$id_mitra]);
    $dataMitra = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ambil SEMUA stasiun milik mitra
    $stmt = $koneksi->prepare("
        SELECT 
            sp.id_stasiun, 
            sp.nama_stasiun, 
            sp.alamat, 
            sp.kapasitas,
            sp.jumlah_slot,
            sp.tarif_per_kwh,
            sp.status, 
            sp.status_operasional, 
            sp.created_at, 
            sp.alasan_penolakan,
            COALESCE(SUM(sb.jumlah), 0) as total_stok_stasiun,
            COUNT(DISTINCT t.id_transaksi) as total_transaksi
        FROM stasiun_pengisian sp
        LEFT JOIN stok_baterai sb ON sp.id_stasiun = sb.id_stasiun
        LEFT JOIN transaksi t ON sp.id_stasiun = t.id_stasiun 
            AND MONTH(t.tanggal_transaksi) = MONTH(CURRENT_DATE())
            AND YEAR(t.tanggal_transaksi) = YEAR(CURRENT_DATE())
        WHERE sp.id_mitra = ? 
        GROUP BY sp.id_stasiun
        ORDER BY sp.created_at DESC
    ");
    $stmt->execute([$id_mitra]);
    $daftarStasiun = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Hitung statistik
    $totalStasiun = count($daftarStasiun);
    $stasiunAktif = array_filter($daftarStasiun, fn($s) => $s['status'] == 'disetujui' && $s['status_operasional'] == 'aktif');
    $stasiunPending = array_filter($daftarStasiun, fn($s) => $s['status'] == 'pending');
    $stasiunDitolak = array_filter($daftarStasiun, fn($s) => $s['status'] == 'ditolak');

} catch (PDOException $e) {
    error_log("Error Kelola Stasiun: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'message' => 'Gagal memuat data stasiun: ' . $e->getMessage()
    ];
    $daftarStasiun = [];
    $totalStasiun = 0;
    $stasiunAktif = [];
    $stasiunPending = [];
    $stasiunDitolak = [];
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Stasiun - E-Station Mitra</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/mitra-style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="../css/alert.css?v=<?= time(); ?>">
    <style>
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-tabs button {
            padding: 10px 20px;
            border: 2px solid rgba(255,255,255,0.2);
            background: transparent;
            color: inherit;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .filter-tabs button.active {
            background: linear-gradient(135deg, #7b61ff, #ff6b9a);
            color: white;
            border-color: transparent;
        }
        .station-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        @media (max-width: 768px) {
            .station-grid {
                grid-template-columns: 1fr;
            }
        }
        .station-card {
            background: var(--card-bg);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .station-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .station-card h5 {
            margin-bottom: 15px;
            font-weight: 600;
        }
        .station-info {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 15px;
        }
        .station-info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
        }
        .station-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
    </style>
</head>
<body>

<!-- DESKTOP THEME TOGGLE -->
<div class="theme-toggle">
    <button id="toggleTheme">🌙</button>
</div>

<?php include '../components/navbar-mitra.php'; ?>

<!-- MOBILE HEADER -->
<div class="mobile-header d-md-none">
    <div class="header-top">
        <div class="logo">
            <i class="fas fa-charging-station"></i>
            Kelola Stasiun
        </div>
        <div class="header-actions">
            <button id="mobileThemeToggle">🌙</button>
        </div>
    </div>
</div>

<div class="container mt-5 mb-5">
    <?php tampilkan_alert(); ?>

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-2">
                <i class="fas fa-charging-station me-2"></i>
                Kelola Stasiun
            </h2>
            <p class="text-muted mb-0">Kelola semua stasiun pengisian Anda</p>
        </div>
        <a href="tambah_stasiun.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Tambah Stasiun
        </a>
    </div>

    <!-- STATISTIK QUICK VIEW -->
    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-3">
            <div class="card text-center" style="background: linear-gradient(135deg, #7b61ff, #ff6b9a); color: white;">
                <div class="card-body">
                    <h3 class="mb-0"><?= $totalStasiun; ?></h3>
                    <small>Total Stasiun</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card text-center" style="background: linear-gradient(135deg, #31d28a, #2bd6ff); color: white;">
                <div class="card-body">
                    <h3 class="mb-0"><?= count($stasiunAktif); ?></h3>
                    <small>Aktif</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card text-center" style="background: linear-gradient(135deg, #fbbf24, #f59e0b); color: white;">
                <div class="card-body">
                    <h3 class="mb-0"><?= count($stasiunPending); ?></h3>
                    <small>Pending</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card text-center" style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white;">
                <div class="card-body">
                    <h3 class="mb-0"><?= count($stasiunDitolak); ?></h3>
                    <small>Ditolak</small>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTER TABS -->
    <div class="filter-tabs">
        <button class="filter-btn active" data-filter="all">
            <i class="fas fa-list me-2"></i>Semua (<?= $totalStasiun; ?>)
        </button>
        <button class="filter-btn" data-filter="disetujui">
            <i class="fas fa-check-circle me-2"></i>Disetujui (<?= count($stasiunAktif); ?>)
        </button>
        <button class="filter-btn" data-filter="pending">
            <i class="fas fa-clock me-2"></i>Pending (<?= count($stasiunPending); ?>)
        </button>
        <button class="filter-btn" data-filter="ditolak">
            <i class="fas fa-times-circle me-2"></i>Ditolak (<?= count($stasiunDitolak); ?>)
        </button>
    </div>

    <!-- DAFTAR STASIUN -->
    <?php if (empty($daftarStasiun)): ?>
        <div class="card text-center py-5">
            <i class="fas fa-map-marker-alt fa-4x text-muted mb-3" style="opacity: 0.3;"></i>
            <h5 class="text-muted mb-3">Belum Ada Stasiun</h5>
            <p class="text-muted mb-4">Mulai tambahkan stasiun pengisian pertama Anda</p>
            <a href="tambah_stasiun.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Tambah Stasiun Pertama
            </a>
        </div>
    <?php else: ?>
        <div class="station-grid" id="stationGrid">
            <?php foreach ($daftarStasiun as $stasiun): ?>
            <div class="station-card" data-status="<?= $stasiun['status']; ?>">
                <!-- HEADER CARD -->
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h5><?= htmlspecialchars($stasiun['nama_stasiun']); ?></h5>
                    <span class="status-badge status-<?= $stasiun['status'] == 'disetujui' ? 'approved' : ($stasiun['status'] == 'ditolak' ? 'rejected' : 'pending'); ?>">
                        <?= ucfirst($stasiun['status']); ?>
                    </span>
                </div>

                <!-- INFO STASIUN -->
                <div class="station-info">
                    <div class="station-info-item">
                        <i class="fas fa-map-marker-alt text-primary"></i>
                        <small><?= htmlspecialchars(substr($stasiun['alamat'], 0, 50)); ?>...</small>
                    </div>
                    <div class="station-info-item">
                        <i class="fas fa-car text-info"></i>
                        <small>Kapasitas: <?= $stasiun['kapasitas']; ?> | Slot: <?= $stasiun['jumlah_slot'] ?? 'N/A'; ?></small>
                    </div>
                    <div class="station-info-item">
                        <i class="fas fa-battery-three-quarters text-success"></i>
                        <small>Stok Baterai: <?= intval($stasiun['total_stok_stasiun']); ?></small>
                    </div>
                    <div class="station-info-item">
                        <i class="fas fa-receipt text-warning"></i>
                        <small>Transaksi Bulan Ini: <?= intval($stasiun['total_transaksi']); ?></small>
                    </div>
                    <div class="station-info-item">
                        <i class="fas fa-money-bill-wave text-success"></i>
                        <small>Tarif: Rp <?= number_format($stasiun['tarif_per_kwh'], 0, ',', '.'); ?>/kWh</small>
                    </div>
                </div>

                <!-- STATUS OPERASIONAL -->
                <?php if ($stasiun['status'] == 'disetujui'): ?>
                <div class="mb-3">
                    <span class="badge bg-<?= $stasiun['status_operasional'] == 'aktif' ? 'success' : 'warning'; ?>">
                        <i class="fas fa-circle me-1"></i>
                        <?= ucfirst($stasiun['status_operasional']); ?>
                    </span>
                </div>
                <?php endif; ?>

                <!-- ALASAN PENOLAKAN -->
                <?php if ($stasiun['status'] == 'ditolak' && !empty($stasiun['alasan_penolakan'])): ?>
                <div class="alert alert-danger mb-3 p-2">
                    <small><strong>Ditolak:</strong> <?= htmlspecialchars($stasiun['alasan_penolakan']); ?></small>
                </div>
                <?php endif; ?>

                <!-- ACTIONS -->
                <div class="station-actions">
                    <a href="detail_stasiun.php?id=<?= $stasiun['id_stasiun']; ?>" class="btn btn-sm btn-outline-info flex-fill">
                        <i class="fas fa-eye"></i> Detail
                    </a>
                    <?php if ($stasiun['status'] == 'disetujui'): ?>
                        <a href="edit_stasiun.php?id=<?= $stasiun['id_stasiun']; ?>" class="btn btn-sm btn-outline-primary flex-fill">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    <?php elseif ($stasiun['status'] == 'ditolak'): ?>
                        <a href="edit_stasiun.php?id=<?= $stasiun['id_stasiun']; ?>" class="btn btn-sm btn-outline-warning flex-fill">
                            <i class="fas fa-redo"></i> Perbaiki
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../components/bottom-nav-mitra.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/clean-url.js"></script>
<script src="../js/mitra-dashboard.js?v=<?= time(); ?>"></script>
<script>
// Filter functionality
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // Update active button
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        // Filter cards
        const filter = this.dataset.filter;
        document.querySelectorAll('.station-card').forEach(card => {
            if (filter === 'all') {
                card.style.display = 'block';
            } else {
                card.style.display = card.dataset.status === filter ? 'block' : 'none';
            }
        });
    });
});
</script>
</body>
</html>