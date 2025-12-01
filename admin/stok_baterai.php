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
function getStokBateraiData($koneksi, $search = '', $status_filter = '', $stasiun_filter = '') {
    try {
        $checkTable = $koneksi->query("SHOW TABLES LIKE 'mitra'")->rowCount();
        
        if ($checkTable > 0) {
            $query = "SELECT sb.*, sp.nama_stasiun, sp.alamat, sp.status as status_stasiun,
                             m.nama_mitra, m.email as email_mitra
                      FROM stok_baterai sb
                      JOIN stasiun_pengisian sp ON sb.id_stasiun = sp.id_stasiun
                      LEFT JOIN mitra m ON sp.id_mitra = m.id_mitra
                      WHERE 1=1";
        } else {
            $query = "SELECT sb.*, sp.nama_stasiun, sp.alamat, sp.status as status_stasiun,
                             u.nama as nama_mitra, u.email as email_mitra
                      FROM stok_baterai sb
                      JOIN stasiun_pengisian sp ON sb.id_stasiun = sp.id_stasiun
                      LEFT JOIN users u ON sp.id_mitra = u.id
                      WHERE 1=1";
        }
        
        $params = [];
        
        if (!empty($search)) {
            $query .= " AND (sb.tipe_baterai LIKE :search1 OR sp.nama_stasiun LIKE :search2)";
            $search_param = "%$search%";
            $params['search1'] = $search_param;
            $params['search2'] = $search_param;
        }
        
        if (!empty($status_filter)) {
            if ($status_filter === 'habis') {
                $query .= " AND sb.jumlah = 0";
            } elseif ($status_filter === 'rendah') {
                $query .= " AND sb.jumlah > 0 AND sb.jumlah < 10";
            } elseif ($status_filter === 'aman') {
                $query .= " AND sb.jumlah >= 10";
            }
        }
        
        if (!empty($stasiun_filter)) {
            $query .= " AND sp.id_stasiun = :stasiun";
            $params['stasiun'] = $stasiun_filter;
        }
        
        $query .= " ORDER BY sp.nama_stasiun, sb.tipe_baterai";
        
        $stmt = $koneksi->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching stok baterai data: " . $e->getMessage());
        return [];
    }
}

function getStokStatistics($koneksi) {
    try {
        $stats = [
            'total_stok' => 0,
            'total_tipe' => 0,
            'stok_rendah' => 0,
            'stok_habis' => 0
        ];
        
        $stmt = $koneksi->query("SELECT SUM(jumlah) as total FROM stok_baterai");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_stok'] = $result['total'] ?? 0;
        
        $stmt = $koneksi->query("SELECT COUNT(*) as total FROM stok_baterai");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_tipe'] = $result['total'] ?? 0;
        
        $stmt = $koneksi->query("SELECT COUNT(*) as total FROM stok_baterai WHERE jumlah > 0 AND jumlah < 10");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['stok_rendah'] = $result['total'] ?? 0;
        
        $stmt = $koneksi->query("SELECT COUNT(*) as total FROM stok_baterai WHERE jumlah = 0");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['stok_habis'] = $result['total'] ?? 0;
        
        return $stats;
    } catch (PDOException $e) {
        error_log("Error fetching statistics: " . $e->getMessage());
        return ['total_stok' => 0, 'total_tipe' => 0, 'stok_rendah' => 0, 'stok_habis' => 0];
    }
}

function getStasiunList($koneksi) {
    try {
        $checkTable = $koneksi->query("SHOW TABLES LIKE 'mitra'")->rowCount();
        
        if ($checkTable > 0) {
            $query = "SELECT sp.id_stasiun, sp.nama_stasiun, m.nama_mitra
                      FROM stasiun_pengisian sp
                      LEFT JOIN mitra m ON sp.id_mitra = m.id_mitra
                      WHERE sp.status = 'disetujui'
                      ORDER BY sp.nama_stasiun";
        } else {
            $query = "SELECT sp.id_stasiun, sp.nama_stasiun, u.nama as nama_mitra
                      FROM stasiun_pengisian sp
                      LEFT JOIN users u ON sp.id_mitra = u.id
                      WHERE sp.status = 'disetujui'
                      ORDER BY sp.nama_stasiun";
        }
        
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

function getStatusBadge($jumlah) {
    if ($jumlah == 0) {
        return '<span class="badge bg-danger">Habis</span>';
    } elseif ($jumlah < 10) {
        return '<span class="badge bg-warning text-dark">Rendah</span>';
    } else {
        return '<span class="badge bg-success">Aman</span>';
    }
}

// ==================== MAIN EXECUTION ====================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$stasiun_filter = isset($_GET['stasiun']) ? $_GET['stasiun'] : '';

$stok_list = getStokBateraiData($koneksi, $search, $status_filter, $stasiun_filter);
$statistics = getStokStatistics($koneksi);
$stasiun_list = getStasiunList($koneksi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Stok Baterai</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin-style.css">
    <link rel="stylesheet" href="../css/alert.css">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <?php tampilkan_alert(); ?>
    
    <!-- Page Header -->
    <div class="top-bar">
        <h5><i class="fas fa-battery-three-quarters me-2"></i>Monitoring Stok Baterai</h5>
    </div>
    
    <!-- Info Alert -->
    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Info:</strong> Stok baterai dikelola oleh mitra. Anda dapat memantau dan menghapus data stok yang tidak valid.
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-battery-full"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($statistics['total_stok']); ?></h3>
                    <p>Total Stok</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-list"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($statistics['total_tipe']); ?></h3>
                    <p>Jenis Baterai</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($statistics['stok_rendah']); ?></h3>
                    <p>Stok Rendah</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-battery-empty"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($statistics['stok_habis']); ?></h3>
                    <p>Stok Habis</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter & Search -->
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="mb-3">Filter & Pencarian</h6>
            
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input 
                        type="text" 
                        name="search" 
                        class="form-control" 
                        placeholder="Cari tipe baterai atau nama stasiun..." 
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                </div>
                <div class="col-md-3">
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
                        <option value="aman" <?php echo $status_filter === 'aman' ? 'selected' : ''; ?>>Stok Aman</option>
                        <option value="rendah" <?php echo $status_filter === 'rendah' ? 'selected' : ''; ?>>Stok Rendah</option>
                        <option value="habis" <?php echo $status_filter === 'habis' ? 'selected' : ''; ?>>Stok Habis</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Cari
                    </button>
                    <a href="stok_baterai.php" class="btn btn-secondary">
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
                            <th>No</th>
                            <th>Stasiun</th>
                            <th>Mitra</th>
                            <th>Tipe Baterai</th>
                            <th class="text-center">Jumlah</th>
                            <th class="text-center">Status</th>
                            <th>Terakhir Update</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stok_list)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-battery-empty fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">
                                        <?php if (!empty($search) || !empty($status_filter) || !empty($stasiun_filter)): ?>
                                            Tidak ada data stok baterai sesuai filter
                                        <?php else: ?>
                                            Belum ada data stok baterai
                                        <?php endif; ?>
                                    </p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stok_list as $index => $stok): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($stok['nama_stasiun']); ?></strong><br>
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars(substr($stok['alamat'], 0, 40)); ?>...
                                        </small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($stok['nama_mitra'] ?? '-'); ?><br>
                                        <?php if (!empty($stok['email_mitra'])): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-envelope me-1"></i>
                                                <?php echo htmlspecialchars($stok['email_mitra']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($stok['tipe_baterai']); ?></strong></td>
                                    <td class="text-center">
                                        <h5 class="mb-0"><?php echo $stok['jumlah']; ?></h5>
                                    </td>
                                    <td class="text-center">
                                        <?php echo getStatusBadge($stok['jumlah']); ?>
                                    </td>
                                    <td>
                                        <?php echo formatTanggal($stok['terakhir_update']); ?>
                                    </td>
                                    <td class="text-center">
                                        <button 
                                            class="btn btn-sm btn-info" 
                                            onclick="viewDetail(<?php echo $stok['id_stok']; ?>, '<?php echo htmlspecialchars($stok['tipe_baterai']); ?>', <?php echo $stok['jumlah']; ?>, '<?php echo htmlspecialchars($stok['nama_stasiun']); ?>', '<?php echo htmlspecialchars($stok['nama_mitra'] ?? '-'); ?>')"
                                            title="Lihat Detail"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button 
                                            class="btn btn-sm btn-danger" 
                                            onclick="hapusStok(<?php echo $stok['id_stok']; ?>, '<?php echo htmlspecialchars($stok['tipe_baterai']); ?>', '<?php echo htmlspecialchars($stok['nama_stasiun']); ?>')"
                                            title="Hapus"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>Detail Stok Baterai
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body">
                <table class="table table-borderless">
                    <tr>
                        <th width="40%">Stasiun:</th>
                        <td id="detail_stasiun">-</td>
                    </tr>
                    <tr>
                        <th>Mitra:</th>
                        <td id="detail_mitra">-</td>
                    </tr>
                    <tr>
                        <th>Tipe Baterai:</th>
                        <td id="detail_tipe">-</td>
                    </tr>
                    <tr>
                        <th>Jumlah:</th>
                        <td><h4 class="mb-0 text-primary" id="detail_jumlah">-</h4></td>
                    </tr>
                </table>
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
function viewDetail(id, tipe, jumlah, namaStasiun, namaMitra) {
    document.getElementById('detail_stasiun').textContent = namaStasiun;
    document.getElementById('detail_mitra').textContent = namaMitra;
    document.getElementById('detail_tipe').textContent = tipe;
    document.getElementById('detail_jumlah').textContent = jumlah + ' Unit';
    new bootstrap.Modal(document.getElementById('detailModal')).show();
}

function hapusStok(id, tipe, stasiun) {
    if (confirm(`⚠️ Hapus stok baterai "${tipe}" di ${stasiun}?\n\nData yang dihapus tidak dapat dikembalikan.\n\nCatatan: Hapus hanya jika data tidak valid atau ada kesalahan input dari mitra.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'process_stok_baterai.php';
        
        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'id_stok';
        inputId.value = id;
        
        const inputAction = document.createElement('input');
        inputAction.type = 'hidden';
        inputAction.name = 'action';
        inputAction.value = 'hapus';
        
        form.appendChild(inputId);
        form.appendChild(inputAction);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

</body>
</html>