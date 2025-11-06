<?php
session_start();

// Jika user sudah login & ada role
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: admin/dashboard.php");
            exit;
        case 'pengendara':
            header("Location: pengendara/dashboard.php");
            exit;
        case 'mitra':
            header("Location: mitra/dashboard.php");
            exit;
        default:
            // Jika role tidak diketahui, paksa login ulang
            header("Location: /auth/login.php");
            exit;
    }
}

// Jika belum login → arahkan ke login
header("Location: auth/login.php");
exit;
?>