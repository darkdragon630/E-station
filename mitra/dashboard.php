<?php
//memulai sesi
session_start();

// menghubungkan ke database
require_once "../config/koneksi.php";

// Cek apakah sudah login sebagai admin
 if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pengendara') {
        redirect_with_alert('../auth/login.php', 'error', 'unauthorized');
}

// kode dashboard pengendara di sini

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    
</body>
</html>