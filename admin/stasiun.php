<?php
session_start();
require_once "../config/koneksi.php";
require_once "../pesan/alerts.php";

// ==================== AUTHENTICATION ====================
function checkAdminAuth() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
        header("Location: ../auth/login.php?error=unauthorized");
        exit();
    }
}

// ==================== DATA RETRIEVAL ====================
function getStasiunData($koneksi, $search = '', $status_filter = '') {
    try {
        $query = "SELECT s.*, m.nama_mitra 
                  FROM stasiun_pengisian s
                  LEFT JOIN mitra m ON s.id_mitra = m.id_mitra
                  WHERE s.status = 'disetujui'";
        $params = [];
        
        if (!empty($search)) {
            $query .= " AND (s.nama_stasiun LIKE :search1 OR s.alamat LIKE :search2 OR m.nama_mitra LIKE :search3)";
            $search_param = "%$search%";
            $params['search1'] = $search_param;
            $params['search2'] = $search_param;
            $params['search3'] = $search_param;
        }
        
        if (!empty($status_filter)) {
            $query .= " AND s.status_operasional = :status";
            $params['status'] = $status_filter;
        }
        
        $query .= " ORDER BY s.created_at DESC";
        
        $stmt = $koneksi->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching stasiun data: " . $e->getMessage());
        return [];
    }
}

function getStasiunStatistics($koneksi) {
    try {
        $stats = [
            'total' => 0,
            'aktif' => 0,
            'nonaktif' => 0
        ];
        
        $stmt = $koneksi->query("SELECT COUNT(*) as total FROM stasiun_pengisian WHERE status = 'disetujui'");
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $koneksi->query("SELECT COUNT(*) as total FROM stasiun_pengisian WHERE status = 'disetujui' AND status_operasional = 'aktif'");
        $stats['aktif'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $koneksi->query("SELECT COUNT(*) as total FROM stasiun_pengisian WHERE status = 'disetujui' AND status_operasional = 'nonaktif'");
        $stats['nonaktif'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return $stats;
    } catch (PDOException $e) {
        error_log("Error fetching statistics: " . $e->getMessage());
        return ['total' => 0, 'aktif' => 0, 'nonaktif' => 0];
    }
}

// ==================== HELPER FUNCTIONS ====================
function formatTanggal($datetime, $format = 'd M Y') {
    return date($format, strtotime($datetime));
}

function truncateText($text, $length = 30) {
    $text = $text ?? '-';
    return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
}

function getStatusBadgeClass($status) {
    switch($status) {
        case 'aktif':
            return 'success';
        case 'nonaktif':
            return 'secondary';
        case 'maintenance':
            return 'warning';
        default:
            return 'secondary';
    }
}

// ==================== MAIN EXECUTION ====================
checkAdminAuth();

$nama_admin = $_SESSION['nama'];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$stasiun_list = getStasiunData($koneksi, $search, $status_filter);
$statistics = getStasiunStatistics($koneksi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Stasiun - E-Station</title>
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
        <h5><i class="fas fa-charging-station me-2"></i>Data Stasiun Pengisian</h5>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-charging-station"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($statistics['total']); ?></h3>
                    <p>Total Stasiun</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($statistics['aktif']); ?></h3>
                    <p>Aktif</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon secondary">
                    <i class="fas fa-power-off"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($statistics['nonaktif']); ?></h3>
                    <p>Nonaktif</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter & Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <input 
                        type="text" 
                        name="search" 
                        class="form-control" 
                        placeholder="Cari nama stasiun, alamat, atau mitra..." 
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="aktif" <?php echo $status_filter === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="nonaktif" <?php echo $status_filter === 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Cari
                    </button>
                    <a href="stasiun.php" class="btn btn-secondary">
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
                            <th>Nama Stasiun</th>
                            <th>Mitra</th>
                            <th>Alamat</th>
                            <th>Kapasitas</th>
                            <th>Tarif/kWh</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stasiun_list)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Tidak ada data stasiun</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stasiun_list as $index => $stasiun): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($stasiun['nama_stasiun']); ?></td>
                                    <td><?php echo htmlspecialchars($stasiun['nama_mitra'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars(truncateText($stasiun['alamat'], 30)); ?></td>
                                    <td><?php echo htmlspecialchars($stasiun['kapasitas'] ?? '-'); ?> slot</td>
                                    <td>Rp <?php echo number_format($stasiun['tarif_per_kwh'] ?? 0, 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo getStatusBadgeClass($stasiun['status_operasional']); ?>">
                                            <?php echo ucfirst($stasiun['status_operasional'] ?? 'nonaktif'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button 
                                            class="btn btn-sm btn-info" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#detailModal<?php echo $stasiun['id_stasiun']; ?>"
                                            title="Lihat Detail"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <!-- Toggle Status Button -->
                                        <?php if ($stasiun['status_operasional'] === 'aktif'): ?>
                                            <button 
                                                class="btn btn-sm btn-warning" 
                                                onclick="updateStatusOperasional(<?php echo $stasiun['id_stasiun']; ?>, 'nonaktif')"
                                                title="Nonaktifkan"
                                            >
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                        <?php else: ?>
                                            <button 
                                                class="btn btn-sm btn-success" 
                                                onclick="updateStatusOperasional(<?php echo $stasiun['id_stasiun']; ?>, 'aktif')"
                                                title="Aktifkan"
                                            >
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                
                                <!-- Detail Modal -->
                                <div class="modal fade" id="detailModal<?php echo $stasiun['id_stasiun']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Detail Stasiun Pengisian</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p><strong>Nama Stasiun:</strong><br><?php echo htmlspecialchars($stasiun['nama_stasiun']); ?></p>
                                                        <p><strong>Mitra:</strong><br><?php echo htmlspecialchars($stasiun['nama_mitra'] ?? '-'); ?></p>
                                                        <p><strong>Alamat:</strong><br><?php echo htmlspecialchars($stasiun['alamat']); ?></p>
                                                        <p><strong>Kapasitas:</strong><br><?php echo htmlspecialchars($stasiun['kapasitas'] ?? '-'); ?> slot</p>
                                                        <p><strong>Jumlah Slot:</strong><br><?php echo htmlspecialchars($stasiun['jumlah_slot'] ?? '-'); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>Tarif per kWh:</strong><br>Rp <?php echo number_format($stasiun['tarif_per_kwh'] ?? 0, 0, ',', '.'); ?></p>
                                                        <p><strong>Jam Operasional:</strong><br><?php echo htmlspecialchars($stasiun['jam_operasional'] ?? '-'); ?></p>
                                                        <p><strong>Fasilitas:</strong><br><?php echo htmlspecialchars($stasiun['fasilitas'] ?? '-'); ?></p>
                                                        <p><strong>Status:</strong><br>
                                                            <span class="badge bg-<?php echo getStatusBadgeClass($stasiun['status_operasional']); ?>">
                                                                <?php echo ucfirst($stasiun['status_operasional'] ?? 'nonaktif'); ?>
                                                            </span>
                                                        </p>
                                                    </div>
                                                </div>
                                                <?php if ($stasiun['latitude'] && $stasiun['longitude']): ?>
                                                    <hr>
                                                    <p><strong>Koordinat:</strong><br>
                                                        Latitude: <?php echo htmlspecialchars($stasiun['latitude']); ?>, 
                                                        Longitude: <?php echo htmlspecialchars($stasiun['longitude']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <hr>
                                                <p class="text-muted mb-0">
                                                    <small>Dibuat: <?php echo formatTanggal($stasiun['created_at'], 'd M Y H:i'); ?></small>
                                                </p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="../js/clean-url.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/stasiun-actions.js"></script>

</body>
</html>