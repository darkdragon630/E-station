<!-- DESKTOP NAVBAR - Reusable Component -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">⚡ E-Station</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="navbar-nav ms-auto">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                    <i class="fas fa-home me-1"></i> Beranda
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'search_location.php' ? 'active' : '' ?>" href="search_location.php">
                    <i class="fas fa-map-marked-alt me-1"></i> Cari Lokasi
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'station_list.php' ? 'active' : '' ?>" href="station_list.php">
                    <i class="fas fa-charging-station me-1"></i> Stasiun
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'battery_stock.php' ? 'active' : '' ?>" href="battery_stock.php">
                    <i class="fas fa-battery-full me-1"></i> Stok Baterai
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'transaction_history.php' ? 'active' : '' ?>" href="transaction_history.php">
                    <i class="fas fa-history me-1"></i> Riwayat
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>" href="profile.php">
                    <i class="fas fa-user me-1"></i> Profil
                </a>
                <a class="nav-link" href="../auth/logout.php" onclick="return confirm('Apakah anda yakin ingin keluar ?')">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>
    </div>
</nav>

<style>
/* Active state for navbar */
.navbar .nav-link.active {
    color: #60a5fa !important;
    background: rgba(96, 165, 250, 0.2);
    border-radius: 10px;
}

body.light .navbar .nav-link.active {
    color: #2563eb !important;
    background: rgba(37, 99, 235, 0.15);
}
</style>