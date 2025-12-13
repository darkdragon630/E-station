<?php
session_start();
require_once '../config/koneksi.php';
require_once '../pesan/alerts.php';

// Cek authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mitra') {
    header('Location: ../auth/login.php');
    exit;
}

$id_mitra = $_SESSION['user_id'];

// ambil data mitra
try {
    $stmt = $koneksi->prepare("SELECT * FROM mitra WHERE id_mitra = :id_mitra LIMIT 1");
    $stmt->bindParam(':id_mitra', $id_mitra, PDO::PARAM_INT);
    $stmt->execute();
    $mitra = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$mitra) {
        header('Location: ../auth/auth.php?error=user_not_found');
        exit;
    }

    // Hitung total stasiun milik mitra
    $stmt = $koneksi->prepare("SELECT COUNT(*) as total_stasiun FROM stasiun_pengisian WHERE id_mitra = :id_mitra");
    $stmt->bindParam(':id_mitra', $id_mitra, PDO::PARAM_INT);
    $stmt->execute();
    $jumlahStasiun = $stmt->fetch(PDO::FETCH_ASSOC)['total_stasiun'] ?? 0;

    // Hitung total stok baterai milik mitra
   $stmt = $koneksi->prepare("
    SELECT SUM(sb.jumlah) as total_stok 
    FROM stok_baterai sb
    INNER JOIN stasiun_pengisian sp ON sb.id_stasiun = sp.id_stasiun
    WHERE sp.id_mitra = :id_mitra
");
    $stmt->bindParam(':id_mitra', $id_mitra, PDO::PARAM_INT);
    $stmt->execute();
    $totalStokBaterai = $stmt->fetch(PDO::FETCH_ASSOC)['total_stok'] ?? 0;

}  catch (PDOException $e) {
    error_log('Error fetching mitra data: ' . $e->getMessage());
    header('Location: ../auth/auth.php?error=database_error');
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
<link rel="stylesheet" href="../css/mitra-style.css">
<link rel="stylesheet" href="../css/alert.css">
<style>
    .mobile-header {
       border-radius: 0 0 15px 20px;
       height: 80px;
    }

    .header-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 15px;
        height: 100%;
    }

    /* Gaya khusus untuk header profile di mobile */
    .profile-header {
        background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
        padding: 20px 15px;
        text-align: center;
        color: #fff;
        position: relative;
        margin: 15px;
        border-radius: 20px;
        box-shadow: 0 8px 16px rgba(96, 165, 250, 0.2);
    }

    .profile-avatar {
        width: 80px;
        height: 80px;
        background-color: #fff;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 0 auto 10px auto;
        font-size: 1.8em;
        color: #60a5fa;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .profile-name {
        font-size: 1.3em;
        font-weight: 700;
        margin: 5px 0;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .profile-email {
        font-size: 0.9em;
        opacity: 0.95;
        margin-bottom: 15px;
    }

    .stats-mini {
        display: flex;
        justify-content: center;
        margin-top: 20px;
        gap: 40px;
        text-align: center;
    }

    .stat-mini {
        flex: 1;
        max-width: 120px;
    }

    .stat-mini-value {
        font-size: 1.8em;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .stat-mini-label {
        font-size: 0.85em;
        opacity: 0.9;
    }

    /* Menu Item Styles - FIXED */
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
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .menu-item:hover {
        transform: translateX(4px);
        background: rgba(96, 165, 250, 0.15);
        border-color: rgba(96, 165, 250, 0.3);
        color: #e2e8f0;
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
        width: 45px;
        height: 45px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        background: rgba(96, 165, 250, 0.15);
        color: #60a5fa;
        transition: all 0.3s ease;
    }

    .menu-item:hover .menu-icon {
        background: rgba(96, 165, 250, 0.25);
        transform: scale(1.05);
    }

    .menu-text h6 {
        margin: 0;
        font-size: 1rem;
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
        transition: all 0.3s ease;
    }

    .menu-item:hover .menu-arrow {
        color: #60a5fa;
        transform: translateX(4px);
    }

    /* Logout Button Specific Styles */
    .logout-btn {
        border: 1px solid rgba(239, 68, 68, 0.2) !important;
    }

    .logout-btn:hover {
        background: rgba(239, 68, 68, 0.1) !important;
        border-color: rgba(239, 68, 68, 0.3) !important;
    }

    .logout-btn .menu-icon {
        background: rgba(239, 68, 68, 0.15) !important;
        color: #ef4444 !important;
    }

    .logout-btn:hover .menu-icon {
        background: rgba(239, 68, 68, 0.25) !important;
    }

    .logout-btn .menu-arrow {
        color: #ef4444 !important;
    }
</style>
</head>
<body>
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

    <!-- MOBILE PROFILE HEADER -->
    <div class="profile-header d-md-none">
        <div class="profile-avatar">
            <i class="fas fa-user"></i>
        </div>
        <div class="profile-name"><?= htmlspecialchars($mitra['nama_mitra']); ?></div>
        <div class="profile-email"><?= htmlspecialchars($mitra['email']); ?></div>
        
        <div class="stats-mini">
            <div class="stat-mini">
                <div class="stat-mini-value"><?= $jumlahStasiun; ?></div>
                <div class="stat-mini-label">Total Stasiun</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-value"><?= $totalStokBaterai; ?></div>
                <div class="stat-mini-label">Total Stok Baterai</div>
            </div>
        </div>
    </div>

    <!-- MENU ITEMS MOBILE - FIXED -->
    <div class="d-md-none" style="padding: 0 15px;">
        <!-- Menu Edit Profil -->
        <a href="edit_profile.php" class="menu-item">
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

        <!-- Menu Keluar -->
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

    <!-- BOTTOM NAVIGATION (MOBILE) -->
    <?php include '../components/bottom-nav-mitra.php'; ?>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/clean-url.js"></script>
</body>
</html>