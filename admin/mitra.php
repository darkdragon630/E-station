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
function getMitraData($koneksi, $search = '', $status_filter = '') {
    try {
        $query = "SELECT * FROM mitra WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $query .= " AND (nama_mitra LIKE :search1 OR email LIKE :search2 OR no_telepon LIKE :search3)";
            $search_param = "%$search%";
            $params['search1'] = $search_param;
            $params['search2'] = $search_param;
            $params['search3'] = $search_param;
        }
        
        if (!empty($status_filter)) {
            $query .= " AND status = :status";
            $params['status'] = $status_filter;
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $koneksi->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching mitra data: " . $e->getMessage());
        return [];
    }
}

function getMitraStatistics($koneksi) {
    try {
        $stats = [
            'total' => 0,
            'pending' => 0,
            'disetujui' => 0,
            'ditolak' => 0
        ];
        
        $stmt = $koneksi->query("SELECT COUNT(*) as total FROM mitra");
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $koneksi->query("SELECT COUNT(*) as total FROM mitra WHERE status = 'pending'");
        $stats['pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $koneksi->query("SELECT COUNT(*) as total FROM mitra WHERE status = 'disetujui'");
        $stats['disetujui'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $koneksi->query("SELECT COUNT(*) as total FROM mitra WHERE status = 'ditolak'");
        $stats['ditolak'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return $stats;
    } catch (PDOException $e) {
        error_log("Error fetching statistics: " . $e->getMessage());
        return ['total' => 0, 'pending' => 0, 'disetujui' => 0, 'ditolak' => 0];
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
    return match($status) {
        'pending' => 'warning',
        'disetujui' => 'success',
        'ditolak' => 'danger',
        default => 'secondary'
    };
}

function getStatusLabel($status) {
    return match($status) {
        'pending' => 'Menunggu',
        'disetujui' => 'Disetujui',
        'ditolak' => 'Ditolak',
        default => ucfirst($status)
    };
}

// ==================== MAIN EXECUTION ====================
checkAdminAuth();

$nama_admin = $_SESSION['nama'];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$mitra_list = getMitraData($koneksi, $search, $status_filter);
$statistics = getMitraStatistics($koneksi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mitra</title>
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
        <h5><i class="fas fa-store me-2"></i>Data Mitra</h5>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($statistics['total']); ?></h3>
                    <p>Total Mitra</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($statistics['pending']); ?></h3>
                    <p>Menunggu Verifikasi</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($statistics['disetujui']); ?></h3>
                    <p>Disetujui</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($statistics['ditolak']); ?></h3>
                    <p>Ditolak</p>
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
                        placeholder="Cari nama, email, atau telepon..." 
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Menunggu</option>
                        <option value="disetujui" <?php echo $status_filter === 'disetujui' ? 'selected' : ''; ?>>Disetujui</option>
                        <option value="ditolak" <?php echo $status_filter === 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Cari
                    </button>
                    <a href="mitra.php" class="btn btn-secondary">
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
                            <th>Nama Mitra</th>
                            <th>Email</th>
                            <th>No. Telepon</th>
                            <th>Alamat</th>
                            <th>Status</th>
                            <th>Tanggal Daftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mitra_list)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Tidak ada data mitra</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($mitra_list as $index => $mitra): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($mitra['nama_mitra']); ?></td>
                                    <td><?php echo htmlspecialchars($mitra['email']); ?></td>
                                    <td><?php echo htmlspecialchars($mitra['no_telepon'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars(truncateText($mitra['alamat'] ?? '-', 30)); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo getStatusBadgeClass($mitra['status']); ?>">
                                            <?php echo getStatusLabel($mitra['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatTanggal($mitra['created_at']); ?></td>
                                    <td>
                                        <button 
                                            class="btn btn-sm btn-info" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#detailModal<?php echo $mitra['id_mitra']; ?>"
                                            title="Lihat Detail"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <?php if ($mitra['status'] === 'pending'): ?>
                                            <button 
                                                class="btn btn-sm btn-success" 
                                                onclick="toggleStatus(<?php echo $mitra['id_mitra']; ?>, 'disetujui', 'pending')"
                                                title="Setujui"
                                            >
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button 
                                                class="btn btn-sm btn-danger" 
                                                onclick="toggleStatus(<?php echo $mitra['id_mitra']; ?>, 'ditolak', 'pending')"
                                                title="Tolak"
                                            >
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php elseif ($mitra['status'] === 'disetujui'): ?>
                                            <button 
                                                class="btn btn-sm btn-warning" 
                                                onclick="toggleStatus(<?php echo $mitra['id_mitra']; ?>, 'pending', 'disetujui')"
                                                title="Batalkan Persetujuan"
                                            >
                                                <i class="fas fa-undo"></i>
                                            </button>
                                            <button 
                                                class="btn btn-sm btn-danger" 
                                                onclick="toggleStatus(<?php echo $mitra['id_mitra']; ?>, 'ditolak', 'disetujui')"
                                                title="Tolak"
                                            >
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php else: ?>
                                            <button 
                                                class="btn btn-sm btn-success" 
                                                onclick="toggleStatus(<?php echo $mitra['id_mitra']; ?>, 'disetujui', 'ditolak')"
                                                title="Aktifkan"
                                            >
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                
                                <!-- Detail Modal -->
                                <?php include 'includes/modal_detail_mitra.php'; ?>
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
<script src="../js/mitra-actions.js"></script>

</body>
</html>