<!-- DESKTOP NAVBAR - Reusable Component -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">⚡ E-Station Mitra</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="navbar-nav ms-auto">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                    <i class="fas fa-home me-1"></i> Beranda
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'tambah_stasiun.php' ? 'active' : '' ?>" href="tambah_stasiun.php">
                    <i class="fas fa-charging-station me-1"></i> Stasiun
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'stok_baterai.php' ? 'active' : '' ?>" href="stok_baterai.php">
                    <i class="fas fa-battery-three-quarters me-1"></i> Stok Baterai
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'usage_history.php' ? 'active' : '' ?>" href="usage_history.php">
                    <i class="fas fa-history me-1"></i> Riwayat
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>" href="reports.php">
                    <i class="fas fa-file-invoice-dollar me-1"></i> Laporan
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'edit_profile.php' ? 'active' : '' ?>" href="edit_profile.php">
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
/* Navbar Styles */
.navbar {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding: 15px 0;
    margin-bottom: 30px;
}

body.light .navbar {
    background: rgba(255, 255, 255, 0.9);
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.navbar-brand {
    font-weight: 800;
    font-size: 1.5rem;
    background: linear-gradient(90deg, #7b61ff, #ff6b9a);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.navbar-toggler {
    border-color: rgba(255, 255, 255, 0.3);
}

.navbar-toggler-icon {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
}

body.light .navbar-toggler-icon {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%280, 0, 0, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
}

.navbar .nav-link {
    color: rgba(255, 255, 255, 0.7);
    padding: 10px 18px;
    border-radius: 10px;
    transition: all 0.3s;
    font-weight: 500;
    margin: 0 5px;
}

body.light .navbar .nav-link {
    color: rgba(0, 0, 0, 0.7);
}

.navbar .nav-link:hover {
    color: #fff;
    background: rgba(123, 97, 255, 0.2);
    transform: translateY(-2px);
}

body.light .navbar .nav-link:hover {
    color: #1e293b;
    background: rgba(123, 97, 255, 0.15);
}

/* Active state for navbar */
.navbar .nav-link.active {
    color: #7b61ff !important;
    background: rgba(123, 97, 255, 0.2);
    border-radius: 10px;
    font-weight: 700;
}

body.light .navbar .nav-link.active {
    color: #6d28d9 !important;
    background: rgba(109, 40, 217, 0.15);
}

/* Logout link style */
.navbar .nav-link[href*="logout"] {
    color: rgba(239, 68, 68, 0.8);
}

.navbar .nav-link[href*="logout"]:hover {
    color: #ef4444;
    background: rgba(239, 68, 68, 0.1);
}

body.light .navbar .nav-link[href*="logout"] {
    color: rgba(220, 38, 38, 0.9);
}

/* Responsive */
@media (max-width: 991px) {
    .navbar {
        display: none;
    }
}
</style>