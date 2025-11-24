<?php
// Hitung pending stasiun untuk notifikasi
try {
    $stmt = $koneksi->query("SELECT COUNT(*) as total FROM stasiun_pengisian WHERE status = 'pending'");
    $total_pending = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    $total_pending = 0;
}

// Hitung stok baterai rendah (misalnya < 10)
try {
    $stmt = $koneksi->query("SELECT COUNT(*) as total FROM stok_baterai WHERE jumlah < 10");
    $stok_rendah = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    $stok_rendah = 0;
}

// Tentukan halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <div class="sidebar-header">
        <h4>
            <i class="fas fa-charging-station"></i>
            E-Station
        </h4>
        <div class="admin-info">
            <i class="fas fa-user-shield"></i> Administrator
        </div>
    </div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="pengendara.php" class="<?= $current_page == 'pengendara.php' ? 'active' : ''; ?>">
                <i class="fas fa-motorcycle"></i> Data Pengendara
            </a>
        </li>
        <li>
            <a href="mitra.php" class="<?= $current_page == 'mitra.php' ? 'active' : ''; ?>">
                <i class="fas fa-store"></i> Data Mitra
            </a>
        </li>
        <li>
            <a href="approval_stasiun.php" class="<?= $current_page == 'approval_stasiun.php' ? 'active' : ''; ?>">
                <i class="fas fa-check-circle"></i> Approval Stasiun
                <?php if ($total_pending > 0): ?>
                    <span class="badge-notif"><?= $total_pending; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="stasiun.php" class="<?= $current_page == 'stasiun.php' ? 'active' : ''; ?>">
                <i class="fas fa-charging-station"></i> Data Stasiun
            </a>
        </li>
        <li>
            <a href="stok_baterai.php" class="<?= $current_page == 'stok_baterai.php' ? 'active' : ''; ?>">
                <i class="fas fa-battery-three-quarters"></i> Stok Baterai
                <?php if ($stok_rendah > 0): ?>
                    <span class="badge-notif badge-warning"><?= $stok_rendah; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="transaksi.php" class="<?= $current_page == 'transaksi.php' ? 'active' : ''; ?>">
                <i class="fas fa-exchange-alt"></i> Transaksi
            </a>
        </li>
        <li>
            <a href="laporan.php" class="<?= $current_page == 'laporan.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> Laporan
            </a>
        </li>
        <li>
            <a href="keuangan.php" class="<?= $current_page == 'keuangan.php' ? 'active' : ''; ?>">
                <i class="fas fa-money-bill-wave"></i> Keuangan
            </a>
        </li>
        <li>
            <a href="promo.php" class="<?= $current_page == 'promo.php' ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i> Promo & Insentif
            </a>
        </li>
        <li>
            <a href="maintenance.php" class="<?= $current_page == 'maintenance.php' ? 'active' : ''; ?>">
                <i class="fas fa-tools"></i> Maintenance
            </a>
        </li>
        <li>
            <a href="pengaturan.php" class="<?= $current_page == 'pengaturan.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Pengaturan
            </a>
        </li>
        <li>
            <a href="../auth/logout.php" class="logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</div>