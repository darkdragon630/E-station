<?php
session_start();
require_once "../config/koneksi.php";
require_once "../pesan/alerts.php";

// Cek authentication admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php?error=unauthorized");
    exit();
}

// ==================== HANDLE FORM SUBMISSIONS ====================

// Update Profile
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    try {
        $nama_admin = trim($_POST['nama_admin']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        
        // Validasi
        if (empty($nama_admin) || empty($username)) {
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Nama admin dan username wajib diisi!'];
        } else {
            // Cek username sudah digunakan oleh admin lain
            $stmt = $koneksi->prepare("SELECT id_admin FROM admin WHERE username = ? AND id_admin != ?");
            $stmt->execute([$username, $_SESSION['user_id']]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'Username sudah digunakan oleh admin lain!'];
            } else {
                // Cek email jika diisi
                if (!empty($email)) {
                    $stmt = $koneksi->prepare("SELECT id_admin FROM admin WHERE email = ? AND id_admin != ?");
                    $stmt->execute([$email, $_SESSION['user_id']]);
                    
                    if ($stmt->rowCount() > 0) {
                        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Email sudah digunakan oleh admin lain!'];
                        header("Location: pengaturan.php");
                        exit();
                    }
                }
                
                // Update profile
                $stmt = $koneksi->prepare("UPDATE admin SET nama_admin = ?, username = ?, email = ? WHERE id_admin = ?");
                $stmt->execute([$nama_admin, $username, $email, $_SESSION['user_id']]);
                
                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Profile berhasil diperbarui!'];
                
                // Update session
                $_SESSION['user_name'] = $nama_admin;
                $_SESSION['user_email'] = $email;
            }
        }
    } catch (PDOException $e) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Gagal memperbarui profile: ' . $e->getMessage()];
    }
    
    header("Location: pengaturan.php");
    exit();
}

// Update Password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_password'])) {
    try {
        $password_lama = $_POST['password_lama'];
        $password_baru = $_POST['password_baru'];
        $konfirmasi_password = $_POST['konfirmasi_password'];
        
        // Validasi
        if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_password)) {
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Semua field password wajib diisi!'];
        } elseif ($password_baru !== $konfirmasi_password) {
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Password baru dan konfirmasi tidak cocok!'];
        } elseif (strlen($password_baru) < 6) {
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Password baru minimal 6 karakter!'];
        } else {
            // Cek password lama
            $stmt = $koneksi->prepare("SELECT password FROM admin WHERE id_admin = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($password_lama, $admin['password'])) {
                $_SESSION['alert'] = ['type' => 'error', 'message' => 'Password lama tidak sesuai!'];
            } else {
                // Update password
                $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
                $stmt = $koneksi->prepare("UPDATE admin SET password = ? WHERE id_admin = ?");
                $stmt->execute([$password_hash, $_SESSION['user_id']]);
                
                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Password berhasil diperbarui!'];
            }
        }
    } catch (PDOException $e) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Gagal memperbarui password: ' . $e->getMessage()];
    }
    
    header("Location: pengaturan.php");
    exit();
}

// ==================== GET ADMIN DATA ====================
try {
    $stmt = $koneksi->prepare("SELECT * FROM admin WHERE id_admin = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $admin_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin_data) {
        session_destroy();
        header("Location: ../auth/login.php?error=session_expired");
        exit();
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Get system statistics
try {
    $stats_query = "SELECT 
                        (SELECT COUNT(*) FROM pengendara) as total_pengendara,
                        (SELECT COUNT(*) FROM mitra) as total_mitra,
                        (SELECT COUNT(*) FROM stasiun_pengisian) as total_stasiun,
                        (SELECT COUNT(*) FROM transaksi) as total_transaksi";
    $stats = $koneksi->query($stats_query)->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stats = ['total_pengendara' => 0, 'total_mitra' => 0, 'total_stasiun' => 0, 'total_transaksi' => 0];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin-style.css">
    <link rel="stylesheet" href="../css/alert.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #667eea;
            font-weight: bold;
            margin: 0 auto 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .settings-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .settings-card .card-header {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-bottom: 2px solid rgba(102, 126, 234, 0.3);
            padding: 1rem 1.5rem;
            font-weight: 600;
        }
        
        .info-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-weight: 600;
            color: #333;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .stat-box h4 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-box p {
            margin: 0;
            opacity: 0.9;
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <?php tampilkan_alert(); ?>
    
    <!-- Page Header -->
    <div class="top-bar">
        <h5><i class="fas fa-cog me-2"></i>Pengaturan Admin</h5>
    </div>
    
        <div class="profile-header">
        <div class="row align-items-center">
            <div class="col-md-2 text-center">
                <div class="profile-avatar">
                    <?= strtoupper(substr($admin_data['nama_admin'], 0, 1)) ?>
                </div>
            </div>
            <div class="col-md-10">
                <h3 class="mb-2"><?= htmlspecialchars($admin_data['nama_admin']) ?></h3>
                <p class="mb-1"><i class="fas fa-user me-2"></i>Username: <?= htmlspecialchars($admin_data['username']) ?></p>
                <?php if (!empty($admin_data['email'])): ?>
                <p class="mb-1"><i class="fas fa-envelope me-2"></i><?= htmlspecialchars($admin_data['email']) ?></p>
                <?php endif; ?>
                <p class="mb-0"><i class="fas fa-shield-alt me-2"></i>Administrator</p>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Update Profile Form -->
            <div class="settings-card card">
                <div class="card-header">
                    <i class="fas fa-user-edit me-2"></i>Edit Profile
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Nama Admin <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_admin" value="<?= htmlspecialchars($admin_data['nama_admin']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($admin_data['username']) ?>" required>
                            <small class="text-muted">Username digunakan untuk login</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($admin_data['email'] ?? '') ?>" placeholder="email@example.com">
                            <small class="text-muted">Opsional</small>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Change Password Form -->
            <div class="settings-card card">
                <div class="card-header">
                    <i class="fas fa-key me-2"></i>Ganti Password
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="passwordForm">
                        <div class="mb-3">
                            <label class="form-label">Password Lama <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password_lama" id="password_lama" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_lama')">
                                    <i class="fas fa-eye" id="icon_password_lama"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password Baru <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password_baru" id="password_baru" required minlength="6">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_baru')">
                                    <i class="fas fa-eye" id="icon_password_baru"></i>
                                </button>
                            </div>
                            <small class="text-muted">Minimal 8 karakter</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="konfirmasi_password" id="konfirmasi_password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('konfirmasi_password')">
                                    <i class="fas fa-eye" id="icon_konfirmasi_password"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" name="update_password" class="btn btn-warning">
                                <i class="fas fa-lock me-2"></i>Ganti Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Account Info -->
            <div class="settings-card card">
                <div class="card-header">
                    <i class="fas fa-info-circle me-2"></i>Informasi Akun
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <div class="info-label">ID Admin</div>
                        <div class="info-value">#<?= $admin_data['id_admin'] ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Status Akun</div>
                        <div class="info-value">
                            <span class="badge bg-success">Aktif</span>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Role</div>
                        <div class="info-value">Administrator</div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Tanggal Bergabung</div>
                        <div class="info-value"><?= date('d/m/Y', strtotime($admin_data['created_at'])) ?></div>
                    </div>
                </div>
            </div>
            
            <!-- System Statistics -->
            <div class="settings-card card">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-2"></i>Statistik Sistem
                </div>
                <div class="card-body">
                    <div class="stat-box mb-2" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h4><?= number_format($stats['total_pengendara']) ?></h4>
                        <p><i class="fas fa-users me-1"></i>Total Pengendara</p>
                    </div>
                    
                    <div class="stat-box mb-2" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h4><?= number_format($stats['total_mitra']) ?></h4>
                        <p><i class="fas fa-handshake me-1"></i>Total Mitra</p>
                    </div>
                    
                    <div class="stat-box mb-2" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <h4><?= number_format($stats['total_stasiun']) ?></h4>
                        <p><i class="fas fa-charging-station me-1"></i>Total Stasiun</p>
                    </div>
                    
                    <div class="stat-box" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <h4><?= number_format($stats['total_transaksi']) ?></h4>
                        <p><i class="fas fa-receipt me-1"></i>Total Transaksi</p>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="settings-card card">
                <div class="card-header">
                    <i class="fas fa-bolt me-2"></i>Aksi Cepat
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="dashboard.php" class="btn btn-outline-primary">
                            <i class="fas fa-home me-2"></i>Dashboard
                        </a>
                        <a href="laporan.php" class="btn btn-outline-success">
                            <i class="fas fa-file-alt me-2"></i>Lihat Laporan
                        </a>
                        <a href="keuangan.php" class="btn btn-outline-info">
                            <i class="fas fa-chart-line me-2"></i>Monitoring Keuangan
                        </a>
                        <a href="../auth/logout.php" class="btn btn-outline-danger" onclick="return confirm('Yakin ingin logout?')">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../js/clean-url.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById('icon_' + fieldId);
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Validate password match
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const passwordBaru = document.getElementById('password_baru').value;
    const konfirmasiPassword = document.getElementById('konfirmasi_password').value;
    
    if (passwordBaru !== konfirmasiPassword) {
        e.preventDefault();
        alert('Password baru dan konfirmasi password tidak cocok!');
        return false;
    }
    
    if (passwordBaru.length < 8) {
        e.preventDefault();
        alert('Password baru minimal 8 karakter!');
        return false;
    }
});
</script>

</body>
</html>