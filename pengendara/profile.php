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

// Handle upload foto profil
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['foto_profil'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    
    // Cek error upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        header('Location: profile.php?error=upload_error');
        exit;
    }
    
    // Validasi tipe file
    if (!in_array($file['type'], $allowed_types)) {
        header('Location: profile.php?error=invalid_file');
        exit;
    }
    
    // Validasi ukuran file
    if ($file['size'] > 2000000) {
        header('Location: profile.php?error=file_too_large');
        exit;
    }
    
    try {
        // Optimasi: resize & compress gambar
        $foto_optimized = optimizeImage($file['tmp_name'], $file['type']);
        
        if ($foto_optimized === false) {
            header('Location: profile.php?error=foto_error');
            exit;
        }
        
        $nama_file = 'profile_' . $id_pengendara . '_' . time() . '.jpg';
        
        // Cek apakah tabel foto_profil ada
        $check_table = $koneksi->query("SHOW TABLES LIKE 'foto_profil'");
        
        if ($check_table->rowCount() == 0) {
            header('Location: profile.php?error=table_error');
            exit;
        }
        
        // Hapus foto lama
        $stmt = $koneksi->prepare("DELETE FROM foto_profil WHERE id_pengendara = ?");
        $stmt->execute([$id_pengendara]);
        
        // Simpan foto baru (sudah terkompress)
        $stmt = $koneksi->prepare("INSERT INTO foto_profil (id_pengendara, nama_file, path_file) VALUES (?, ?, ?)");
        $result = $stmt->execute([$id_pengendara, $nama_file, $foto_optimized]);
        
        if ($result) {
            // Hapus cache foto
            unset($_SESSION['foto_cache']);
            header('Location: profile.php?success=foto_uploaded');
            exit;
        } else {
            header('Location: profile.php?error=save_error');
            exit;
        }
        
    } catch (PDOException $e) {
        header('Location: profile.php?error=database_error');
        exit;
    } catch (Exception $e) {
        header('Location: profile.php?error=upload_error');
        exit;
    }
}

// Handle update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $nama = trim($_POST['nama']);
        $no_telepon = trim($_POST['no_telepon']);
        $alamat = trim($_POST['alamat']);

        $stmt = $koneksi->prepare("UPDATE pengendara SET nama = ?, no_telepon = ?, alamat = ? WHERE id_pengendara = ?");
        $stmt->execute([$nama, $no_telepon, $alamat, $id_pengendara]);

        $_SESSION['nama'] = $nama;
        header('Location: profile.php?success=update_profile');
        exit;
    } catch (PDOException $e) {
        header('Location: profile.php?error=update_failed');
        exit;
    }
}

// Handle change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    try {
        $password_lama = $_POST['password_lama'];
        $password_baru = $_POST['password_baru'];
        $konfirmasi_password = $_POST['konfirmasi_password'];

        if ($password_baru !== $konfirmasi_password) {
            header('Location: profile.php?error=password_mismatch');
            exit;
        }

        $stmt = $koneksi->prepare("SELECT password FROM pengendara WHERE id_pengendara = ?");
        $stmt->execute([$id_pengendara]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($password_lama, $user['password'])) {
            header('Location: profile.php?error=invalid_credentials');
            exit;
        }

        $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $stmt = $koneksi->prepare("UPDATE pengendara SET password = ? WHERE id_pengendara = ?");
        $stmt->execute([$password_hash, $id_pengendara]);

        header('Location: profile.php?success=change_password');
        exit;
    } catch (PDOException $e) {
        header('Location: profile.php?error=update_failed');
        exit;
    }
}

// Function: Optimize Image
function optimizeImage($source_path, $mime_type) {
    // Cek apakah GD extension tersedia
    if (!extension_loaded('gd')) {
        // Fallback: Simpan gambar tanpa resize jika GD tidak tersedia
        $image_data = file_get_contents($source_path);
        return $image_data;
    }
    
    $target_width = 400;
    $target_height = 400;
    $quality = 75;
    
    try {
        switch ($mime_type) {
            case 'image/jpeg':
            case 'image/jpg':
                $source = @imagecreatefromjpeg($source_path);
                break;
            case 'image/png':
                $source = @imagecreatefrompng($source_path);
                break;
            case 'image/gif':
                $source = @imagecreatefromgif($source_path);
                break;
            default:
                return false;
        }
        
        if (!$source) {
            // Jika gagal create image, gunakan file asli
            return file_get_contents($source_path);
        }
        
        $orig_width = imagesx($source);
        $orig_height = imagesy($source);
        
        $ratio = min($orig_width, $orig_height);
        $crop_x = ($orig_width - $ratio) / 2;
        $crop_y = ($orig_height - $ratio) / 2;
        
        $cropped = @imagecrop($source, [
            'x' => $crop_x,
            'y' => $crop_y,
            'width' => $ratio,
            'height' => $ratio
        ]);
        
        if (!$cropped) $cropped = $source;
        
        $resized = imagecreatetruecolor($target_width, $target_height);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        
        imagecopyresampled(
            $resized, $cropped,
            0, 0, 0, 0,
            $target_width, $target_height,
            imagesx($cropped), imagesy($cropped)
        );
        
        ob_start();
        imagejpeg($resized, null, $quality);
        $image_data = ob_get_clean();
        
        imagedestroy($source);
        if ($cropped !== $source) imagedestroy($cropped);
        imagedestroy($resized);
        
        return $image_data;
    } catch (Exception $e) {
        // Jika terjadi error, gunakan file asli
        return file_get_contents($source_path);
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

    // Ambil foto profil - dengan caching
    $foto = null;
    $foto_src = null;

    // Optimasi: Hanya ambil jika belum ada di session
    if (!isset($_SESSION['foto_cache']) || isset($_GET['refresh_foto'])) {
        $stmt = $koneksi->prepare("SELECT id_foto, nama_file, path_file FROM foto_profil WHERE id_pengendara = ? ORDER BY uploaded_at DESC LIMIT 1");
        $stmt->execute([$id_pengendara]);
        $foto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($foto && !empty($foto['path_file'])) {
            $foto_base64 = base64_encode($foto['path_file']);
            $foto_src = "data:image/jpeg;base64,$foto_base64";
            
            // Optimasi: Cache di session (expire setelah 1 jam)
            $_SESSION['foto_cache'] = [
                'src' => $foto_src,
                'timestamp' => time()
            ];
        }
    } else {
        // Gunakan cache jika masih valid (< 1 jam)
        $cache = $_SESSION['foto_cache'];
        if (time() - $cache['timestamp'] < 3600) {
            $foto_src = $cache['src'];
        } else {
            unset($_SESSION['foto_cache']);
            header('Location: profile.php?refresh_foto=1');
            exit;
        }
    }
} catch (PDOException $e) {
    $foto = null;
    $foto_src = null;
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
<link rel="stylesheet" href="../css/alert.css">
<style>
@media (max-width: 768px) {
    .profile-header {
        background: linear-gradient(135deg, #1e40af, #6366f1);
        border-radius: 0 0 30px 30px;
        padding: 30px 20px;
        margin: -20px -16px 20px;
        text-align: center;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        position: relative;
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
        overflow: hidden;
        position: relative;
    }

    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .upload-overlay-mobile {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 35px;
        height: 35px;
        background: linear-gradient(135deg, #60a5fa, #3b82f6);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border: 3px solid #1e40af;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    .upload-overlay-mobile i {
        color: white;
        font-size: 0.9rem;
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

    #file-input-mobile {
        display: none;
    }
}

@media (min-width: 769px) {
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
        overflow: hidden;
        position: relative;
    }

    .profile-avatar-desktop img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .upload-overlay-desktop {
        position: absolute;
        bottom: 5px;
        right: 5px;
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #60a5fa, #3b82f6);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 5px 20px rgba(96, 165, 250, 0.5);
    }

    .upload-overlay-desktop:hover {
        transform: scale(1.1);
        background: linear-gradient(135deg, #3b82f6, #2563eb);
    }

    .upload-overlay-desktop i {
        color: white;
        font-size: 1rem;
    }

    #file-input-desktop {
        display: none;
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
                <a class="nav-link" href="../auth/logout.php" onclick="return confirm('Apakah anda yakin ingin keluar ?')"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
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
            <?php if ($foto_src): ?>
                <img src="<?= $foto_src; ?>" alt="Profile" loading="lazy">
            <?php else: ?>
                <i class="fas fa-user"></i>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" id="uploadFormMobile">
                <label for="file-input-mobile" class="upload-overlay-mobile">
                    <i class="fas fa-camera"></i>
                </label>
                <input type="file" id="file-input-mobile" name="foto_profil" accept="image/*" onchange="previewAndSubmit(this, 'uploadFormMobile')">
            </form>
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
                <?php if ($foto_src): ?>
                    <img src="<?= $foto_src; ?>" alt="Profile" loading="lazy">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" id="uploadFormDesktop">
                    <label for="file-input-desktop" class="upload-overlay-desktop">
                        <i class="fas fa-camera"></i>
                    </label>
                    <input type="file" id="file-input-desktop" name="foto_profil" accept="image/*" onchange="previewAndSubmit(this, 'uploadFormDesktop')">
                </form>
            </div>
            <div class="profile-info-desktop">
                <h2><?= htmlspecialchars($pengendara['nama']); ?></h2>
                <p><i class="fas fa-envelope me-2"></i><?= htmlspecialchars($pengendara['email']); ?></p>
                <p><i class="fas fa-phone me-2"></i><?= htmlspecialchars($pengendara['no_telepon'] ?? 'Belum diisi'); ?></p>
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

        <a href="../auth/logout.php" onclick="return confirm('Apakah anda yakin ingin keluar ?')" class="menu-item logout-btn">
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
                        <input type="text" name="no_telepon" class="form-control" value="<?= htmlspecialchars($pengendara['no_telepon'] ?? ''); ?>">
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
                        <input type="text" name="no_telepon" class="form-control" value="<?= htmlspecialchars($pengendara['no_telepon'] ?? ''); ?>" placeholder="08xxxxxxxxxx">
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
<?php include '../components/bottom-nav.php'; ?>

<!-- JavaScript: Client-side validation & compression -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/clean-url.js"></script>
<script>
// Function: Preview and submit with validation
function previewAndSubmit(input, formId) {
    const file = input.files[0];
    
    if (!file) return;
    
    // Validasi ukuran file
    if (file.size > 2000000) {
        alert('File terlalu besar! Maksimal 2MB');
        input.value = '';
        return;
    }
    
    // Validasi tipe file
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        alert('Format file tidak valid! Gunakan JPG, PNG, atau GIF');
        input.value = '';
        return;
    }
    
    // Optimasi: Preview sebelum upload (optional)
    const reader = new FileReader();
    reader.onload = function(e) {
        // Show loading indicator
        const overlay = document.querySelector(`#${formId} .upload-overlay-mobile, #${formId} .upload-overlay-desktop`);
        if (overlay) {
            overlay.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }
        
        // Submit form
        document.getElementById(formId).submit();
    };
    reader.readAsDataURL(file);
}

// Desktop theme toggle
const toggleButton = document.getElementById("toggleTheme");
if (toggleButton) {
    const savedTheme = localStorage.getItem("theme");
    if (savedTheme === "light") {
        document.body.classList.add("light");
        toggleButton.textContent = "☀️";
    } else {
        toggleButton.textContent = "🌙";
    }

    toggleButton.addEventListener("click", () => {
        document.body.classList.toggle("light");
        const isLight = document.body.classList.contains("light");
        toggleButton.textContent = isLight ? "☀️" : "🌙";
        localStorage.setItem("theme", isLight ? "light" : "dark");
    });
}

// Prevent zoom on double tap (iOS)
let lastTouchEnd = 0;
document.addEventListener('touchend', function (event) {
    const now = (new Date()).getTime();
    if (now - lastTouchEnd <= 300) {
        event.preventDefault();
    }
    lastTouchEnd = now;
}, false);
</script>

</body>
</html>