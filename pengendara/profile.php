<?php
session_start();
require_once '../config/koneksi.php';
require_once '../pesan/alerts.php';

// Cek authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pengendara') {
    header('Location: ../auth/login.php');
    exit;
}

$id_pengendara = $_SESSION['user_id'];

// Handle form submission untuk update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_profile'])) {
            $nama = trim($_POST['nama']);
            $no_hp = trim($_POST['no_hp']);
            $alamat = trim($_POST['alamat']);

            $stmt = $koneksi->prepare("UPDATE pengendara SET nama = ?, no_hp = ?, alamat = ? WHERE id_pengendara = ?");
            $stmt->execute([$nama, $no_hp, $alamat, $id_pengendara]);

            $_SESSION['nama'] = $nama;
            set_alert('success', 'Profil berhasil diperbarui!');
            header('Location: profile.php');
            exit;
        }

        if (isset($_POST['change_password'])) {
            $password_lama = $_POST['password_lama'];
            $password_baru = $_POST['password_baru'];
            $konfirmasi_password = $_POST['konfirmasi_password'];

            // Validasi password baru
            if ($password_baru !== $konfirmasi_password) {
                set_alert('error', 'Konfirmasi password tidak cocok!');
                header('Location: profile.php');
                exit;
            }

            // Cek password lama
            $stmt = $koneksi->prepare("SELECT password FROM pengendara WHERE id_pengendara = ?");
            $stmt->execute([$id_pengendara]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!password_verify($password_lama, $user['password'])) {
                set_alert('error', 'Password lama tidak sesuai!');
                header('Location: profile.php');
                exit;
            }

            // Update password
            $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
            $stmt = $koneksi->prepare("UPDATE pengendara SET password = ? WHERE id_pengendara = ?");
            $stmt->execute([$password_hash, $id_pengendara]);

            set_alert('success', 'Password berhasil diubah!');
            header('Location: profile.php');
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error: " . $e->getMessage());
        set_alert('error', 'Terjadi kesalahan. Silakan coba lagi.');
        header('Location: profile.php');
        exit;
    }
}

// Ambil data pengendara
try {
    $stmt = $koneksi->prepare("SELECT * FROM pengendara WHERE id_pengendara = ?");
    $stmt->execute([$id_pengendara]);
    $pengendara = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ambil jumlah kendaraan
    $stmt = $koneksi->prepare("SELECT COUNT(*) as total FROM kendaraan WHERE id_pengendara = ?");
    $stmt->execute([$id_pengendara]);
    $total_kendaraan = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Ambil jumlah transaksi
    $stmt = $koneksi->prepare("SELECT COUNT(*) as total FROM transaksi WHERE id_pengendara = ?");
    $stmt->execute([$id_pengendara]);
    $total_transaksi = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    set_alert('error', 'Gagal memuat data profil.');
    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="theme-color" content="#0a192f">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<title>Profil Saya - E-Station</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../css/pengendara-style.css">
<style>
/* Additional styles for profile page */
@media (max-width: 768px) {
    .profile-header {
        background: linear-gradient(135deg, #1e40af, #6366f1);
        border-radius: 0 0 30px 30px;
        padding: 30px 20px;
        margin: -20px -16px 20px;
        text-align: center;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .profile-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: linear-gradient(135deg, #60a5fa, #a855f7);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 3rem;
        color: white;
        box-shadow: 0 4px 15px rgba(96, 165, 250, 0.4);
    }

    .profile-name {
        font-size: 1.4rem;
        font-weight: 700;
        color: white;
        margin-bottom: 5px;
    }

    .profile-email {
        font-size: 0.9rem;
        color: rgba(255, 255, 255, 0.8);
    }

    .stats-mini {
        display: flex;
        justify-content: center;
        gap: 30px;
        margin-top: 20px;
    }

    .stat-mini {
        text-align: center;
    }

    .stat-mini-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: white;
    }

    .stat-mini-label {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.8);
    }

    .section-title {
        font-size: 1rem;
        font-weight: 700;
        color: #60a5fa;
        margin: 25px 0 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        font-size: 0.85rem;
        color: #94a3b8;
        margin-bottom: 8px;
        font-weight: 600;
    }

    .form-control {
        background: rgba(30, 41, 59, 0.8);
        border: 1px solid rgba(96, 165, 250, 0.2);
        border-radius: 12px;
        padding: 12px 16px;
        color: #e2e8f0;
        font-size: 0.95rem;
    }

    .form-control:focus {
        background: rgba(30, 41, 59, 0.9);
        border-color: #60a5fa;
        box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.1);
        color: #e2e8f0;
    }

    .form-control::placeholder {
        color: #64748b;
    }

    .btn-save {
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        color: white;
        border: none;
        padding: 14px 24px;
        border-radius: 12px;
        font-weight: 600;
        width: 100%;
        margin-top: 10px;
        font-size: 1rem;
    }

    .btn-save:active {
        transform: scale(0.98);
    }

    .btn-danger {
        background: linear-gradient(135deg, #dc2626, #ef4444);
        color: white;
        border: none;
        padding: 14px 24px;
        border-radius: 12px;
        font-weight: 600;
        width: 100%;
        margin-top: 10px;
        font-size: 1rem;
    }

    .menu-item {
        background: rgba(30, 41, 59, 0.8);
        border: 1px solid rgba(96, 165, 250, 0.15);
        border-radius: 16px;
        padding: 16px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        text-decoration: none;
        color: #e2e8f0;
        transition: all 0.3s ease;
    }

    .menu-item:active {
        transform: scale(0.98);
        background: rgba(96, 165, 250, 0.1);
    }

    .menu-item-left {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .menu-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        background: rgba(96, 165, 250, 0.15);
        color: #60a5fa;
    }

    .menu-text h6 {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 600;
        color: #e2e8f0;
    }

    .menu-text p {
        margin: 0;
        font-size: 0.8rem;
        color: #94a3b8;
    }

    .menu-arrow {
        color: #64748b;
        font-size: 1rem;
    }

    .logout-btn {
        background: rgba(239, 68, 68, 0.15);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #ef4444;
    }

    .logout-btn .menu-icon {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
    }

    /* Modal styles */
    .modal-content {
        background: #1e293b;
        border: 1px solid rgba(96, 165, 250, 0.2);
        border-radius: 20px;
    }

    .modal-header {
        border-bottom: 1px solid rgba(96, 165, 250, 0.2);
        padding: 20px;
    }

    .modal-title {
        color: #60a5fa;
        font-weight: 700;
    }

    .modal-body {
        padding: 20px;
    }

    .btn-close {
        filter: invert(1);
    }
}

@media (min-width: 769px) {
    .profile-container {
        max-width: 1000px;
        margin: 0 auto;
    }

    .profile-card {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
        backdrop-filter: blur(20px);
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.18);
        padding: 40px;
        margin-bottom: 30px;
    }

    .profile-header-desktop {
        display: flex;
        align-items: center;
        gap: 30px;
        margin-bottom: 30px;
    }

    .profile-avatar-desktop {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, #60a5fa, #a855f7);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 4rem;
        color: white;
        flex-shrink: 0;
    }

    .profile-info-desktop h2 {
        font-size: 2rem;
        font-weight: 700;
        color: #60a5fa;
        margin-bottom: 10px;
    }

    .profile-info-desktop p {
        color: #94a3b8;
        margin-bottom: 5px;
    }
}
</style>
</head>

<body>

<!-- DESKTOP THEME TOGGLE -->
<div class="theme-toggle">
    <button id="toggleTheme">🌙</button>
</div>

<!-- DESKTOP NAVBAR -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">⚡ E-Station</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php"><i class="fas fa-home me-1"></i> Dashboard</a>
                <a class="nav-link" href="search_location.php"><i class="fas fa-map-marked-alt me-1"></i> Cari Lokasi</a>
                <a class="nav-link" href="transaction_history.php"><i class="fas fa-history me-1"></i> Riwayat</a>
                <a class="nav-link" href="profile.php"><i class="fas fa-user me-1"></i> Profil</a>
                <a class="nav-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
            </div>
        </div>
    </div>
</nav>

<!-- MOBILE HEADER -->
<div class="mobile-header d-md-none">
    <div class="header-top">
        <a href="dashboard.php" style="color: #60a5fa; text-decoration: none;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div style="font-size: 1.1rem; font-weight: 700; color: #fff;">Profil Saya</div>
        <div style="width: 24px;"></div>
    </div>
</div>

<!-- CONTENT -->
<div class="container mt-md-5 mb-5">
    <?php tampilkan_alert(); ?>

    <!-- MOBILE PROFILE HEADER -->
    <div class="profile-header d-md-none">
        <div class="profile-avatar">
            <i class="fas fa-user"></i>
        </div>
        <div class="profile-name"><?= htmlspecialchars($pengendara['nama']); ?></div>
        <div class="profile-email"><?= htmlspecialchars($pengendara['email']); ?></div>
        
        <div class="stats-mini">
            <div class="stat-mini">
                <div class="stat-mini-value"><?= $total_kendaraan; ?></div>
                <div class="stat-mini-label">Kendaraan</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-value"><?= $total_transaksi; ?></div>
                <div class="stat-mini-label">Transaksi</div>
            </div>
        </div>
    </div>

    <!-- DESKTOP PROFILE -->
    <div class="profile-card d-none d-md-block">
        <div class="profile-header-desktop">
            <div class="profile-avatar-desktop">
                <i class="fas fa-user"></i>
            </div>
            <div class="profile-info-desktop">
                <h2><?= htmlspecialchars($pengendara['nama']); ?></h2>
                <p><i class="fas fa-envelope me-2"></i><?= htmlspecialchars($pengendara['email']); ?></p>
                <p><i class="fas fa-phone me-2"></i><?= htmlspecialchars($pengendara['no_hp'] ?? 'Belum diisi'); ?></p>
                <p><i class="fas fa-map-marker-alt me-2"></i><?= htmlspecialchars($pengendara['alamat'] ?? 'Belum diisi'); ?></p>
            </div>
        </div>
    </div>

    <!-- MENU ITEMS MOBILE -->
    <div class="d-md-none">
        <a href="#" class="menu-item" data-bs-toggle="modal" data-bs-target="#editProfileModal">
            <div class="menu-item-left">
                <div class="menu-icon">
                    <i class="fas fa-user-edit"></i>
                </div>
                <div class="menu-text">
                    <h6>Edit Profil</h6>
                    <p>Perbarui informasi profil Anda</p>
                </div>
            </div>
            <div class="menu-arrow">
                <i class="fas fa-chevron-right"></i>
            </div>
        </a>

        <a href="#" class="menu-item" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
            <div class="menu-item-left">
                <div class="menu-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <div class="menu-text">
                    <h6>Ubah Password</h6>
                    <p>Keamanan akun Anda</p>
                </div>
            </div>
            <div class="menu-arrow">
                <i class="fas fa-chevron-right"></i>
            </div>
        </a>

        <a href="manage_vehicles.php" class="menu-item">
            <div class="menu-item-left">
                <div class="menu-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                    <i class="fas fa-car"></i>
                </div>
                <div class="menu-text">
                    <h6>Kelola Kendaraan</h6>
                    <p><?= $total_kendaraan; ?> kendaraan terdaftar</p>
                </div>
            </div>
            <div class="menu-arrow">
                <i class="fas fa-chevron-right"></i>
            </div>
        </a>

        <a href="transaction_history.php" class="menu-item">
            <div class="menu-item-left">
                <div class="menu-icon" style="background: rgba(168, 85, 247, 0.15); color: #a855f7;">
                    <i class="fas fa-history"></i>
                </div>
                <div class="menu-text">
                    <h6>Riwayat Transaksi</h6>
                    <p><?= $total_transaksi; ?> transaksi</p>
                </div>
            </div>
            <div class="menu-arrow">
                <i class="fas fa-chevron-right"></i>
            </div>
        </a>

        <a href="../auth/logout.php" class="menu-item logout-btn">
            <div class="menu-item-left">
                <div class="menu-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <div class="menu-text">
                    <h6>Keluar</h6>
                    <p>Logout dari akun Anda</p>
                </div>
            </div>
            <div class="menu-arrow">
                <i class="fas fa-chevron-right"></i>
            </div>
        </a>
    </div>

    <!-- DESKTOP FORMS -->
    <div class="row d-none d-md-flex">
        <div class="col-md-6">
            <div class="card">
                <h5 class="card-title"><i class="fas fa-user-edit me-2"></i>Edit Profil</h5>
                <form method="POST">
                    <div class="form-group mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($pengendara['nama']); ?>" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($pengendara['email']); ?>" disabled>
                        <small class="text-muted">Email tidak dapat diubah</small>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">No. HP</label>
                        <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($pengendara['no_hp'] ?? ''); ?>">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="3"><?= htmlspecialchars($pengendara['alamat'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary w-100">
                        <i class="fas fa-save me-2"></i>Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <h5 class="card-title"><i class="fas fa-lock me-2"></i>Ubah Password</h5>
                <form method="POST">
                    <div class="form-group mb-3">
                        <label class="form-label">Password Lama</label>
                        <input type="password" name="password_lama" class="form-control" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="password_baru" class="form-control" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" name="konfirmasi_password" class="form-control" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-danger w-100">
                        <i class="fas fa-key me-2"></i>Ubah Password
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

<!-- MODAL EDIT PROFILE (MOBILE) -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Profil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($pengendara['nama']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($pengendara['email']); ?>" disabled>
                        <small class="text-muted" style="font-size: 0.75rem;">Email tidak dapat diubah</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">No. HP</label>
                        <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($pengendara['no_hp'] ?? ''); ?>" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="3" placeholder="Masukkan alamat lengkap"><?= htmlspecialchars($pengendara['alamat'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" name="update_profile" class="btn-save">
                        <i class="fas fa-save me-2"></i>Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CHANGE PASSWORD (MOBILE) -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ubah Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Password Lama</label>
                        <input type="password" name="password_lama" class="form-control" placeholder="Masukkan password lama" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="password_baru" class="form-control" placeholder="Masukkan password baru" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" name="konfirmasi_password" class="form-control" placeholder="Konfirmasi password baru" required>
                    </div>
                    <button type="submit" name="change_password" class="btn-danger">
                        <i class="fas fa-key me-2"></i>Ubah Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- BOTTOM NAVIGATION (MOBILE) -->
<div class="bottom-nav d-md-none">
    <a href="dashboard.php">
        <i class="fas fa-home"></i>
        <span>Beranda</span>
    </a>
    <a href="search_location.php">
        <i class="fas fa-map-marked-alt"></i>
        <span>Lokasi</span>
    </a>
    <a href="battery_stock.php">
        <i class="fas fa-battery-full"></i>
        <span>Stok</span>
    </a>
    <a href="transaction_history.php">
        <i class="fas fa-history"></i>
        <span>Riwayat</span>
    </a>
    <a href="profile.php" class="active">
        <i class="fas fa-user"></i>
        <span>Profil</span>
    </a>
</div>

<!-- SCRIPT -->
<script>
// Theme Toggle
const toggleButton = document.getElementById("toggleTheme");
if (toggleButton) {
    const savedTheme = localStorage.getItem("theme");
    if (savedTheme === "light") {
        document.body.classList.add("light");
        toggleButton.textContent = "☀️";
    }
    toggleButton.addEventListener("click", () => {
        document.body.classList.toggle("light");
        const isLight = document.body.classList.contains("light");
        toggleButton.textContent = isLight ? "☀️" : "🌙";
        localStorage.setItem("theme", isLight ? "light" : "dark");
    });
}

// Active bottom nav
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.bottom-nav a');
    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/clean-url.js"></script>

</body>
</html>