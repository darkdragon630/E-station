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

// ==================== MAINTENANCE FUNCTIONS ====================
function createMaintenance($koneksi, $data) {
    try {
        $query = "INSERT INTO jadwal_maintenance 
                  (id_admin, id_stasiun, tanggal_mulai, tanggal_selesai, keterangan, status) 
                  VALUES (:id_admin, :id_stasiun, :tanggal_mulai, :tanggal_selesai, :keterangan, :status)";
        
        $stmt = $koneksi->prepare($query);
        $result = $stmt->execute([
            ':id_admin' => $data['id_admin'],
            ':id_stasiun' => $data['id_stasiun'],
            ':tanggal_mulai' => $data['tanggal_mulai'],
            ':tanggal_selesai' => $data['tanggal_selesai'],
            ':keterangan' => $data['keterangan'],
            ':status' => $data['status']
        ]);
        
        // Update status operasional stasiun jika maintenance dimulai
        if ($result && $data['status'] === 'berlangsung') {
            $updateStmt = $koneksi->prepare("UPDATE stasiun_pengisian SET status_operasional = 'maintenance' WHERE id_stasiun = :id");
            $updateStmt->execute([':id' => $data['id_stasiun']]);
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Create maintenance error: " . $e->getMessage());
        return false;
    }
}

function updateMaintenanceStatus($koneksi, $id_maintenance, $status) {
    try {
        // Get stasiun ID first
        $stmt = $koneksi->prepare("SELECT id_stasiun FROM jadwal_maintenance WHERE id_maintenance = :id");
        $stmt->execute([':id' => $id_maintenance]);
        $maintenance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Update maintenance status
        $query = "UPDATE jadwal_maintenance SET status = :status WHERE id_maintenance = :id";
        $stmt = $koneksi->prepare($query);
        $result = $stmt->execute([
            ':status' => $status,
            ':id' => $id_maintenance
        ]);
        
        // Update status operasional stasiun
        if ($result && $maintenance) {
            $newStatus = 'aktif'; // Default ke aktif
            
            if ($status === 'berlangsung') {
                $newStatus = 'maintenance';
            } elseif ($status === 'selesai') {
                $newStatus = 'aktif';
            }
            
            $updateStmt = $koneksi->prepare("UPDATE stasiun_pengisian SET status_operasional = :status WHERE id_stasiun = :id");
            $updateStmt->execute([
                ':status' => $newStatus,
                ':id' => $maintenance['id_stasiun']
            ]);
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Update maintenance status error: " . $e->getMessage());
        return false;
    }
}

function getStasiunList($koneksi) {
    try {
        $stmt = $koneksi->query("SELECT id_stasiun, nama_stasiun, alamat FROM stasiun_pengisian WHERE status = 'disetujui' ORDER BY nama_stasiun");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get stasiun list error: " . $e->getMessage());
        return [];
    }
}

function getMaintenanceList($koneksi, $limit = 10) {
    try {
        $query = "SELECT m.*, s.nama_stasiun, s.alamat, u.nama as admin_nama
                  FROM jadwal_maintenance m
                  LEFT JOIN stasiun_pengisian s ON m.id_stasiun = s.id_stasiun
                  LEFT JOIN users u ON m.id_admin = u.id AND u.role = 'admin'
                  ORDER BY m.tanggal_mulai DESC
                  LIMIT :limit";
        
        $stmt = $koneksi->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get maintenance list error: " . $e->getMessage());
        return [];
    }
}

function getMaintenanceStats($koneksi) {
    try {
        $stats = [];
        
        $stmt = $koneksi->query("SELECT COUNT(*) as total FROM jadwal_maintenance");
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $koneksi->query("SELECT COUNT(*) as total FROM jadwal_maintenance WHERE status = 'terjadwal'");
        $stats['terjadwal'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $koneksi->query("SELECT COUNT(*) as total FROM jadwal_maintenance WHERE status = 'berlangsung'");
        $stats['berlangsung'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $koneksi->query("SELECT COUNT(*) as total FROM jadwal_maintenance WHERE status = 'selesai'");
        $stats['selesai'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return $stats;
    } catch (PDOException $e) {
        error_log("Get maintenance stats error: " . $e->getMessage());
        return ['total' => 0, 'terjadwal' => 0, 'berlangsung' => 0, 'selesai' => 0];
    }
}

// ==================== MAIN EXECUTION ====================
checkAdminAuth();

$nama_admin = $_SESSION['nama'];
$id_admin = $_SESSION['user_id'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $data = [
                    'id_admin' => $id_admin,
                    'id_stasiun' => $_POST['id_stasiun'] ?? null,
                    'tanggal_mulai' => $_POST['tanggal_mulai'] ?? '',
                    'tanggal_selesai' => $_POST['tanggal_selesai'] ?? '',
                    'keterangan' => $_POST['keterangan'] ?? '',
                    'status' => 'terjadwal'
                ];
                
                if (createMaintenance($koneksi, $data)) {
                    $_SESSION['alert_success'] = "Jadwal maintenance berhasil ditambahkan!";
                } else {
                    $_SESSION['alert_danger'] = "Gagal menambahkan jadwal maintenance";
                }
                
                header("Location: maintenance.php");
                exit();
                break;
                
            case 'update_status':
                $id_maintenance = $_POST['id_maintenance'] ?? 0;
                $new_status = $_POST['new_status'] ?? '';
                
                if (updateMaintenanceStatus($koneksi, $id_maintenance, $new_status)) {
                    $_SESSION['alert_success'] = "Status maintenance berhasil diperbarui!";
                } else {
                    $_SESSION['alert_danger'] = "Gagal memperbarui status maintenance";
                }
                
                header("Location: maintenance.php");
                exit();
                break;
        }
    }
}

$stasiun_list = getStasiunList($koneksi);
$maintenance_list = getMaintenanceList($koneksi, 20);
$stats = getMaintenanceStats($koneksi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - E-Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin-style.css">
    <link rel="stylesheet" href="../css/alert.css">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <?php tampilkan_alert(); ?>
    
    <!-- Top Bar -->
    <div class="top-bar">
        <h5><i class="fas fa-tools me-2"></i>Jadwal Maintenance</h5>
    </div>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-list"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total']); ?></h3>
                    <p>Total Maintenance</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['terjadwal']); ?></h3>
                    <p>Terjadwal</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-cog fa-spin"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['berlangsung']); ?></h3>
                    <p>Berlangsung</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['selesai']); ?></h3>
                    <p>Selesai</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Maintenance Form -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-plus-circle me-2"></i>Tambah Jadwal Maintenance
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3" onsubmit="return validateForm()">
                <input type="hidden" name="action" value="create">
                
                <div class="col-md-6">
                    <label class="form-label">Stasiun Pengisian <span class="text-danger">*</span></label>
                    <select name="id_stasiun" class="form-select" required>
                        <option value="">-- Pilih Stasiun --</option>
                        <?php foreach ($stasiun_list as $stasiun): ?>
                            <option value="<?php echo $stasiun['id_stasiun']; ?>">
                                <?php echo htmlspecialchars($stasiun['nama_stasiun']); ?>
                                <?php if (!empty($stasiun['alamat'])): ?>
                                    - <?php echo htmlspecialchars(substr($stasiun['alamat'], 0, 40)); ?><?php echo strlen($stasiun['alamat']) > 40 ? '...' : ''; ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Pilih stasiun yang akan di-maintenance</small>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="tanggal_mulai" id="tanggal_mulai" class="form-control" required>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="tanggal_selesai" id="tanggal_selesai" class="form-control" required>
                </div>
                
                <div class="col-12">
                    <label class="form-label">Keterangan <span class="text-danger">*</span></label>
                    <textarea name="keterangan" class="form-control" rows="3" required placeholder="Deskripsi detail maintenance yang akan dilakukan..."></textarea>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Simpan Jadwal
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-redo me-2"></i>Reset
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Maintenance List -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-history me-2"></i>Riwayat Maintenance
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Stasiun</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                            <th>Keterangan</th>
                            <th>Admin</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($maintenance_list)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Belum ada jadwal maintenance</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($maintenance_list as $index => $maintenance): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($maintenance['nama_stasiun'] ?? '-'); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($maintenance['alamat'] ?? '-'); ?></small>
                                    </td>
                                    <td><?php echo date('d M Y H:i', strtotime($maintenance['tanggal_mulai'])); ?></td>
                                    <td><?php echo date('d M Y H:i', strtotime($maintenance['tanggal_selesai'])); ?></td>
                                    <td>
                                        <?php 
                                        $keterangan = htmlspecialchars($maintenance['keterangan']);
                                        echo strlen($keterangan) > 50 ? substr($keterangan, 0, 50) . '...' : $keterangan;
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($maintenance['admin_nama'] ?? '-'); ?></td>
                                    <td>
                                        <?php
                                        $status = $maintenance['status'];
                                        $badge_class = '';
                                        $icon = '';
                                        
                                        switch ($status) {
                                            case 'terjadwal':
                                                $badge_class = 'warning';
                                                $icon = 'fa-clock';
                                                break;
                                            case 'berlangsung':
                                                $badge_class = 'info';
                                                $icon = 'fa-cog fa-spin';
                                                break;
                                            case 'selesai':
                                                $badge_class = 'success';
                                                $icon = 'fa-check-circle';
                                                break;
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $badge_class; ?>">
                                            <i class="fas <?php echo $icon; ?> me-1"></i>
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailModal<?php echo $maintenance['id_maintenance']; ?>" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <?php if ($maintenance['status'] === 'terjadwal'): ?>
                                            <button class="btn btn-sm btn-primary" onclick="updateStatus(<?php echo $maintenance['id_maintenance']; ?>, 'berlangsung')" title="Mulai">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        <?php elseif ($maintenance['status'] === 'berlangsung'): ?>
                                            <button class="btn btn-sm btn-success" onclick="updateStatus(<?php echo $maintenance['id_maintenance']; ?>, 'selesai')" title="Selesai">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                
                                <!-- Detail Modal -->
                                <div class="modal fade" id="detailModal<?php echo $maintenance['id_maintenance']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Detail Maintenance</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <strong>Stasiun:</strong><br>
                                                    <?php echo htmlspecialchars($maintenance['nama_stasiun'] ?? '-'); ?>
                                                </div>
                                                <div class="mb-3">
                                                    <strong>Lokasi:</strong><br>
                                                    <?php echo htmlspecialchars($maintenance['alamat'] ?? '-'); ?>
                                                </div>
                                                <div class="mb-3">
                                                    <strong>Tanggal Mulai:</strong><br>
                                                    <?php echo date('d F Y, H:i', strtotime($maintenance['tanggal_mulai'])); ?>
                                                </div>
                                                <div class="mb-3">
                                                    <strong>Tanggal Selesai:</strong><br>
                                                    <?php echo date('d F Y, H:i', strtotime($maintenance['tanggal_selesai'])); ?>
                                                </div>
                                                <div class="mb-3">
                                                    <strong>Keterangan:</strong><br>
                                                    <?php echo nl2br(htmlspecialchars($maintenance['keterangan'])); ?>
                                                </div>
                                                <div class="mb-3">
                                                    <strong>Admin:</strong><br>
                                                    <?php echo htmlspecialchars($maintenance['admin_nama'] ?? '-'); ?>
                                                </div>
                                                <div class="mb-3">
                                                    <strong>Status:</strong><br>
                                                    <span class="badge bg-<?php echo $badge_class; ?>">
                                                        <i class="fas <?php echo $icon; ?> me-1"></i>
                                                        <?php echo ucfirst($maintenance['status']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    <i class="fas fa-times me-2"></i>Tutup
                                                </button>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/clean-url.js"></script>

<script>
function updateStatus(id, status) {
    const action = status === 'berlangsung' ? 'memulai' : 'menyelesaikan';
    const message = status === 'berlangsung' 
        ? 'Apakah Anda yakin ingin memulai maintenance ini?\n\nStatus stasiun akan diubah menjadi Maintenance.' 
        : 'Apakah Anda yakin ingin menyelesaikan maintenance ini?\n\nStatus stasiun akan diubah menjadi Aktif kembali.';
    
    if (confirm(message)) {
        const form = document.createElement('form');
        form.method = 'POST';
        
        const inputAction = document.createElement('input');
        inputAction.type = 'hidden';
        inputAction.name = 'action';
        inputAction.value = 'update_status';
        
        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'id_maintenance';
        inputId.value = id;
        
        const inputStatus = document.createElement('input');
        inputStatus.type = 'hidden';
        inputStatus.name = 'new_status';
        inputStatus.value = status;
        
        form.appendChild(inputAction);
        form.appendChild(inputId);
        form.appendChild(inputStatus);
        document.body.appendChild(form);
        form.submit();
    }
}

// Validate form
function validateForm() {
    const tanggalMulai = document.getElementById('tanggal_mulai').value;
    const tanggalSelesai = document.getElementById('tanggal_selesai').value;
    
    if (!tanggalMulai || !tanggalSelesai) {
        alert('Tanggal mulai dan selesai harus diisi!');
        return false;
    }
    
    if (tanggalSelesai <= tanggalMulai) {
        alert('Tanggal selesai harus setelah tanggal mulai!');
        return false;
    }
    
    return true;
}

// Set min date for datetime inputs
document.addEventListener('DOMContentLoaded', function() {
    const tanggalMulai = document.getElementById('tanggal_mulai');
    const tanggalSelesai = document.getElementById('tanggal_selesai');
    
    if (tanggalMulai && tanggalSelesai) {
        // Set min date to now
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        const minDate = now.toISOString().slice(0, 16);
        tanggalMulai.min = minDate;
        
        tanggalMulai.addEventListener('change', function() {
            tanggalSelesai.min = this.value;
            if (tanggalSelesai.value && tanggalSelesai.value < this.value) {
                tanggalSelesai.value = '';
            }
        });
    }
});
</script>

</body>
</html>