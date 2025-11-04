<?php
session_start();

// Cek apakah sudah login sebagai admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - E-Station</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .welcome {
            color: #666;
            margin-bottom: 30px;
            padding: 15px;
            background: #f0f9ff;
            border-left: 4px solid #0284c7;
            border-radius: 5px;
        }
        .menu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .menu-item {
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            text-align: center;
            transition: transform 0.3s;
            font-weight: bold;
        }
        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .logout {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 30px;
            background: #ef4444;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .logout:hover {
            background: #dc2626;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎉 Dashboard Admin E-Station</h1>
        <div class="welcome">
            <strong>Selamat datang, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</strong><br>
            <small>Role: <?php echo ucfirst($_SESSION['role']); ?> | User ID: <?php echo $_SESSION['user_id']; ?></small>
        </div>

        <h2>Menu Utama</h2>
        <div class="menu">
            <a href="kelola_pengendara.php" class="menu-item">
                👥<br>Kelola Pengendara
            </a>
            <a href="kelola_mitra.php" class="menu-item">
                🤝<br>Kelola Mitra
            </a>
            <a href="kelola_stasiun.php" class="menu-item">
                ⚡<br>Kelola Stasiun Charging
            </a>
            <a href="laporan.php" class="menu-item">
                📊<br>Laporan & Statistik
            </a>
        </div>

        <a href="../logout.php" class="logout">🚪 Logout</a>
    </div>
</body>
</html>