<?php
session_start();
require_once "../config/koneksi.php";
require_once "../pesan/alerts.php";

// Cek authentication admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php?error=unauthorized");
    exit();
}

// ==================== DATA RETRIEVAL ====================
function getTransaksiData($koneksi, $search = '', $status_filter = '', $stasiun_filter = '', $bulan_filter = '') {
    try {
        $query = "SELECT 
                    t.id_transaksi,
                    t.tanggal_transaksi,
                    t.jumlah_kwh,
                    t.total_harga,
                    t.baterai_terpakai,
                    t.status_transaksi,
                    sp.nama_stasiun,
                    sp.alamat,
                    p.nama as nama_pengendara,
                    p.email as email_pengendara,
                    k.merk as merk_kendaraan,
                    k.model as model_kendaraan,
                    k.no_plat,
                    m.nama_mitra
                  FROM transaksi t
                  INNER JOIN stasiun_pengisian sp ON t.id_stasiun = sp.id_stasiun
                  LEFT JOIN pengendara p ON t.id_pengendara = p.id_pengendara
                  LEFT JOIN kendaraan k ON p.id_pengendara = k.id_pengendara
                  LEFT JOIN mitra m ON sp.id_mitra = m.id_mitra
                  WHERE 1=1";
        
        $params = [];
        
        if (!empty($search)) {
            $query .= " AND (p.nama LIKE :search1 OR sp.nama_stasiun LIKE :search2 OR k.no_plat LIKE :search3)";
            $search_param = "%$search%";
            $params['search1'] = $search_param;
            $params['search2'] = $search_param;
            $params['search3'] = $search_param;
        }
        
        if (!empty($status_filter)) {
            $query .= " AND t.status_transaksi = :status";
            $params['status'] = $status_filter;
        }
        
        if (!empty($stasiun_filter)) {
            $query .= " AND sp.id_stasiun = :stasiun";
            $params['stasiun'] = $stasiun_filter;
        }
        
        if (!empty($bulan_filter)) {
            $query .= " AND DATE_FORMAT(t.tanggal_transaksi, '%Y-%m') = :bulan";
            $params['bulan'] = $bulan_filter;
        }
        
        $query .= " ORDER BY t.tanggal_transaksi DESC LIMIT 100";
        
        $stmt = $koneksi->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching transaksi data: " . $e->getMessage());
        return [];
    }
}

function getTransaksiStatistics($koneksi) {
    try {
        $stats = [
            'total_transaksi' => 0,
            'total_pendapatan' => 0,
            'total_kwh' => 0,
            'total_baterai' => 0,
            'transaksi_berhasil' => 0,
            'transaksi_pending' => 0,
            'transaksi_gagal' => 0
        ];
        
        // Total transaksi
        $stmt = $koneksi->query("SELECT COUNT(*) as total FROM transaksi");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_transaksi'] = $result['total'] ?? 0;
        
        // Total pendapatan & kWh
        $stmt = $koneksi->query("SELECT 
                                    SUM(total_harga) as total_pendapatan,
                                    SUM(jumlah_kwh) as total_kwh,
                                    SUM(CASE WHEN baterai_terpakai > 0 THEN CEIL(baterai_terpakai / 100) ELSE 0 END) as total_baterai
                                 FROM transaksi 
                                 WHERE status_transaksi = 'berhasil'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_pendapatan'] = $result['total_pendapatan'] ?? 0;
        $stats['total_kwh'] = $result['total_kwh'] ?? 0;
        $stats['total_baterai'] = $result['total_baterai'] ?? 0;
        
        // Status breakdown
        $stmt = $koneksi->query("SELECT 
                                    SUM(CASE WHEN status_transaksi = 'berhasil' THEN 1 ELSE 0 END) as berhasil,
                                    SUM(CASE WHEN status_transaksi = 'pending' THEN 1 ELSE 0 END) as pending,
                                    SUM(CASE WHEN status_transaksi = 'gagal' THEN 1 ELSE 0 END) as gagal
                                 FROM transaksi");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['transaksi_berhasil'] = $result['berhasil'] ?? 0;
        $stats['transaksi_pending'] = $result['pending'] ?? 0;
        $stats['transaksi_gagal'] = $result['gagal'] ?? 0;
        
        return $stats;
    } catch (PDOException $e) {
        error_log("Error fetching statistics: " . $e->getMessage());
        return [
            'total_transaksi' => 0,
            'total_pendapatan' => 0,
            'total_kwh' => 0,
            'total_baterai' => 0,
            'transaksi_berhasil' => 0,
            'transaksi_pending' => 0,
            'transaksi_gagal' => 0
        ];
    }
}

function getStasiunList($koneksi) {
    try {
        $query = "SELECT id_stasiun, nama_stasiun FROM stasiun_pengisian ORDER BY nama_stasiun";
        $stmt = $koneksi->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching stasiun list: " . $e->getMessage());
        return [];
    }
}

// ==================== HELPER FUNCTIONS ====================
function formatTanggal($datetime) {
    return date('d M Y H:i', strtotime($datetime));
}

function getStatusBadge($status) {
    switch(strtolower($status)) {
        case 'berhasil':
            return '<span class="badge bg-success">Berhasil</span>';
        case 'pending':
            return '<span class="badge bg-warning text-dark">Pending</span>';
        case 'gagal':
            return '<span class="badge bg-danger">Gagal</span>';
        default:
            return '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }
}

// ==================== MAIN EXECUTION ====================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$stasiun_filter = isset($_GET['stasiun']) ? $_GET['stasiun'] : '';
$bulan_filter = isset($_GET['bulan']) ? $_GET['bulan'] : '';

$transaksi_list = getTransaksiData($koneksi, $search, $status_filter, $stasiun_filter, $bulan_filter);
$statistics = getTransaksiStatistics($koneksi);
$stasiun_list = getStasiunList($koneksi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Transaksi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin-style.css">
    <link rel="stylesheet" href="../css/alert.css">
    <link rel="icon" type="image/png" href="../images/Logo_1.png">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <?php tampilkan_alert(); ?>
    
    <!-- Page Header -->
    <div class="top-bar">
        <h5><i class="fas fa-receipt me-2"></i>Monitoring Transaksi</h5>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($statistics['total_transaksi']); ?></h3>
                    <p>Total Transaksi</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-info">
                    <h3>Rp <?php echo number_format($statistics['total_pendapatan'], 0, ',', '.'); ?></h3>
                    <p>Total Pendapatan</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($statistics['total_kwh'], 2); ?> kWh</h3>
                    <p>Total Energi</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-battery-three-quarters"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($statistics['total_baterai']); ?></h3>
                    <p>Baterai Terpakai</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Status Breakdown -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                    <h4><?php echo number_format($statistics['transaksi_berhasil']); ?></h4>
                    <p class="mb-0 text-muted">Transaksi Berhasil</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                    <h4><?php echo number_format($statistics['transaksi_pending']); ?></h4>
                    <p class="mb-0 text-muted">Transaksi Pending</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                    <h4><?php echo number_format($statistics['transaksi_gagal']); ?></h4>
                    <p class="mb-0 text-muted">Transaksi Gagal</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter & Search -->
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="mb-3">Filter & Pencarian</h6>
            
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input 
                        type="text" 
                        name="search" 
                        class="form-control" 
                        placeholder="Cari pengendara/stasiun/plat..." 
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                </div>
                <div class="col-md-2">
                    <select name="stasiun" class="form-select">
                        <option value="">Semua Stasiun</option>
                        <?php foreach ($stasiun_list as $stasiun): ?>
                            <option value="<?php echo $stasiun['id_stasiun']; ?>" <?php echo $stasiun_filter == $stasiun['id_stasiun'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($stasiun['nama_stasiun']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="berhasil" <?php echo $status_filter === 'berhasil' ? 'selected' : ''; ?>>Berhasil</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="gagal" <?php echo $status_filter === 'gagal' ? 'selected' : ''; ?>>Gagal</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="month" name="bulan" class="form-control" value="<?php echo htmlspecialchars($bulan_filter); ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Cari
                    </button>
                    <a href="transaksi.php" class="btn btn-secondary">
                        <i class="fas fa-redo me-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Data Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tanggal</th>
                            <th>Stasiun</th>
                            <th>Pengendara</th>
                            <th>Kendaraan</th>
                            <th class="text-center">kWh</th>
                            <th class="text-center">Baterai</th>
                            <th class="text-right">Total</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transaksi_list)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">
                                        <?php if (!empty($search) || !empty($status_filter) || !empty($stasiun_filter) || !empty($bulan_filter)): ?>
                                            Tidak ada transaksi sesuai filter
                                        <?php else: ?>
                                            Belum ada transaksi
                                        <?php endif; ?>
                                    </p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transaksi_list as $tr): ?>
                                <tr>
                                    <td><strong>#<?php echo $tr['id_transaksi']; ?></strong></td>
                                    <td>
                                        <small><?php echo formatTanggal($tr['tanggal_transaksi']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($tr['nama_stasiun']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($tr['nama_mitra'] ?? '-'); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($tr['nama_pengendara'] ?? '-'); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($tr['email_pengendara'] ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($tr['merk_kendaraan']): ?>
                                            <?php echo htmlspecialchars($tr['merk_kendaraan'] . ' ' . $tr['model_kendaraan']); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($tr['no_plat']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <strong><?php echo number_format($tr['jumlah_kwh'], 2); ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($tr['baterai_terpakai'] > 0): ?>
                                            <div style="display: inline-block; background: rgba(56, 239, 125, 0.1); border: 1px solid rgba(56, 239, 125, 0.3); border-radius: 15px; padding: 5px 10px;">
                                                <i class="fas fa-battery-three-quarters" style="color: #38ef7d;"></i>
                                                <strong><?php echo number_format($tr['baterai_terpakai'], 1); ?>%</strong><br>
                                                <small style="font-size: 0.75rem;"><?php echo ceil($tr['baterai_terpakai'] / 100); ?> unit</small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right">
                                        <strong>Rp <?php echo number_format($tr['total_harga'], 0, ',', '.'); ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <?php echo getStatusBadge($tr['status_transaksi']); ?>
                                    </td>
                                    <td class="text-center">
                                        <button 
                                            class="btn btn-sm btn-info" 
                                            onclick='viewDetail(<?php echo json_encode($tr); ?>)'
                                            title="Lihat Detail"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (!empty($transaksi_list) && count($transaksi_list) >= 100): ?>
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    Menampilkan 100 transaksi terbaru. Gunakan filter untuk melihat transaksi lainnya.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>Detail Transaksi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Informasi Transaksi</h6>
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th width="40%">ID Transaksi:</th>
                                <td id="detail_id">-</td>
                            </tr>
                            <tr>
                                <th>Tanggal:</th>
                                <td id="detail_tanggal">-</td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td id="detail_status">-</td>
                            </tr>
                        </table>
                        
                        <h6 class="text-muted mb-3 mt-4">Stasiun & Mitra</h6>
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th width="40%">Stasiun:</th>
                                <td id="detail_stasiun">-</td>
                            </tr>
                            <tr>
                                <th>Mitra:</th>
                                <td id="detail_mitra">-</td>
                            </tr>
                            <tr>
                                <th>Alamat:</th>
                                <td id="detail_alamat">-</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Pengendara & Kendaraan</h6>
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th width="40%">Nama:</th>
                                <td id="detail_pengendara">-</td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td id="detail_email">-</td>
                            </tr>
                            <tr>
                                <th>Kendaraan:</th>
                                <td id="detail_kendaraan">-</td>
                            </tr>
                            <tr>
                                <th>No. Plat:</th>
                                <td id="detail_plat">-</td>
                            </tr>
                        </table>
                        
                        <h6 class="text-muted mb-3 mt-4">Detail Pengisian</h6>
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th width="40%">Energi:</th>
                                <td id="detail_kwh">-</td>
                            </tr>
                            <tr>
                                <th>Baterai:</th>
                                <td id="detail_baterai">-</td>
                            </tr>
                            <tr>
                                <th>Total Harga:</th>
                                <td><h5 class="text-primary mb-0" id="detail_harga">-</h5></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="../js/clean-url.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function viewDetail(data) {
    document.getElementById('detail_id').innerHTML = '<strong>#' + data.id_transaksi + '</strong>';
    document.getElementById('detail_tanggal').textContent = data.tanggal_transaksi;
    document.getElementById('detail_status').innerHTML = getStatusBadge(data.status_transaksi);
    document.getElementById('detail_stasiun').innerHTML = '<strong>' + data.nama_stasiun + '</strong>';
    document.getElementById('detail_mitra').textContent = data.nama_mitra || '-';
    document.getElementById('detail_alamat').textContent = data.alamat;
    document.getElementById('detail_pengendara').textContent = data.nama_pengendara || '-';
    document.getElementById('detail_email').textContent = data.email_pengendara || '-';
    
    if (data.merk_kendaraan) {
        document.getElementById('detail_kendaraan').textContent = data.merk_kendaraan + ' ' + data.model_kendaraan;
        document.getElementById('detail_plat').textContent = data.no_plat;
    } else {
        document.getElementById('detail_kendaraan').textContent = '-';
        document.getElementById('detail_plat').textContent = '-';
    }
    
    document.getElementById('detail_kwh').innerHTML = '<strong>' + parseFloat(data.jumlah_kwh).toFixed(2) + ' kWh</strong>';
    
    if (data.baterai_terpakai > 0) {
        const unit = Math.ceil(data.baterai_terpakai / 100);
        document.getElementById('detail_baterai').innerHTML = 
            '<i class="fas fa-battery-three-quarters text-success me-2"></i>' +
            '<strong>' + parseFloat(data.baterai_terpakai).toFixed(1) + '%</strong> ' +
            '<small class="text-muted">(' + unit + ' unit)</small>';
    } else {
        document.getElementById('detail_baterai').textContent = '-';
    }
    
    document.getElementById('detail_harga').textContent = 'Rp ' + parseInt(data.total_harga).toLocaleString('id-ID');
    
    new bootstrap.Modal(document.getElementById('detailModal')).show();
}

function getStatusBadge(status) {
    switch(status.toLowerCase()) {
        case 'berhasil':
            return '<span class="badge bg-success">Berhasil</span>';
        case 'pending':
            return '<span class="badge bg-warning text-dark">Pending</span>';
        case 'gagal':
            return '<span class="badge bg-danger">Gagal</span>';
        default:
            return '<span class="badge bg-secondary">' + status + '</span>';
    }
}
</script>

</body>
</html>