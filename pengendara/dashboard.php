<?php
//memulai sesi
session_start();

// menghubungkan ke database
require_once "../config/koneksi.php";

// Cek apakah sudah login sebagai admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pengendara') {
    header("Location: ../auth/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengendara</title>
    <style>
        .header {
            background-color: #4CAF50;
            padding: 15px 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logout-btn {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        .logout-btn:hover {
            background-color: #d32f2f;
        }
        .container {
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Dashboard Pengendara</h1>
        <a href="../auth/logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="container">
        <h2>Selamat datang, <?php echo $_SESSION['nama'] ?? 'Pengendara'; ?>!</h2>
        <!-- Konten dashboard lainnya -->
    </div>
</body>
</html>