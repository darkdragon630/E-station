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
function getPengendaraData($koneksi, $search = '', $status_filter = '') {
    try {
        $query = "SELECT * FROM pengendara WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $query .= " AND (nama LIKE :search1 OR email LIKE :search2 OR no_telepon LIKE :search3)";
            $search_param = "%$search%";
            $params['search1'] = $search_param;
            $params['search2'] = $search_param;
            $params['search3'] = $search_param;
        }
        
        if (!empty($status_filter)) {
            $query .= " AND status_akun = :status";
            $params['status'] = $status_filter;
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $koneksi->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching pengendara data: " . $e->getMessage());
        return [];
    }
}

function getPengendaraStatistics($koneksi) {
    try {
        $stats = [
            'total' => 0,
            'aktif' => 0,
            'nonaktif' => 0
        ];
        
        $stmt = $koneksi->query("SELECT COUNT(*) as total FROM pengendara");
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $koneksi->query("SELECT COUNT(*) as total FROM pengendara WHERE status_akun = 'aktif'");
        $stats['aktif'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $koneksi->query("SELECT COUNT(*) as total FROM pengendara WHERE status_akun = 'nonaktif'");
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
    return $status === 'aktif' ? 'success' : 'warning';
}

// ==================== MAIN EXECUTION ====================
checkAdminAuth();

$nama_admin = $_SESSION['nama'];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$pengendara_list = getPengendaraData($koneksi, $search, $status_filter);
$statistics = getPengendaraStatistics($koneksi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pengendara - E-Station</title>
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
        <h5><i class="fas fa-motorcycle me-2"></i>Data Pengendara</h5>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($statistics['total']); ?></h3>
                    <p>Total Pengendara</p>
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
                    <p>Akun Aktif</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($statistics['nonaktif']); ?></h3>
                    <p>Akun Nonaktif</p>
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
                        <option value="aktif" <?php echo $status_filter === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="nonaktif" <?php echo $status_filter === 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Cari
                    </button>
                    <a href="pengendara.php" class="btn btn-secondary">
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
                            <th>Nama</th>
                            <th>Email</th>
                            <th>No. Telepon</th>
                            <th>Alamat</th>
                            <th>Status</th>
                            <th>Tanggal Daftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pengendara_list)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Tidak ada data pengendara</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pengendara_list as $index => $pengendara): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($pengendara['nama']); ?></td>
                                    <td><?php echo htmlspecialchars($pengendara['email']); ?></td>
                                    <td><?php echo htmlspecialchars($pengendara['no_telepon'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars(truncateText($pengendara['alamat'] ?? '-', 30)); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo getStatusBadgeClass($pengendara['status_akun']); ?>">
                                            <?php echo ucfirst($pengendara['status_akun']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatTanggal($pengendara['created_at']); ?></td>
                                    <td>
                                        <button 
                                            class="btn btn-sm btn-info" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#detailModal<?php echo $pengendara['id_pengendara']; ?>"
                                            title="Lihat Detail"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($pengendara['status_akun'] === 'aktif'): ?>
                                            <button 
                                                class="btn btn-sm btn-warning" 
                                                onclick="toggleStatus(<?php echo $pengendara['id_pengendara']; ?>, 'nonaktif')"
                                                title="Nonaktifkan"
                                            >
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        <?php else: ?>
                                            <button 
                                                class="btn btn-sm btn-success" 
                                                onclick="toggleStatus(<?php echo $pengendara['id_pengendara']; ?>, 'aktif')"
                                                title="Aktifkan"
                                            >
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                
                                <!-- Detail Modal -->
                                <?php include 'includes/modal_detail_pengendara.php'; ?>
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
<script src="../js/pengendara-actions.js"></script>

</body>
</html>