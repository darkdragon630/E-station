<?php
// memulai sesi
session_start();
// menghubungkan ke database
require_once '../config/koneksi.php';
// menghubungkan fungsi pesan
require_once "../pesan/alerts.php";


// pesan error dan sukses
$error = isset($_GET['error']) ? $_GET['error'] : '';
$success = isset($_GET['success']) ? $_GET['success'] : '';


// Redirect jika sudah login
if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/dashboard.php");
    } elseif ($_SESSION['role'] === 'pengendara') {
        header("Location: ../pengendara/dashboard.php");
    } elseif ($_SESSION['role'] === 'mitra') {
        header("Location: ../mitra/dashboard.php");
    } else {
        session_destroy(); // role tidak dikenal, paksa login ulang
        header("Location: login.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scalable=1.0, user-scalable=no">
  <title>E-Station | Login</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/alert.css">
  <link rel="icon" type="image/png" href="../images/Logo_1.jpeg">
</head>
<body>
  <!-- Loading screen -->
  <div id="loading-screen">
    <div class="loader">
      <div class="electric-circle"></div>  
      <img src="../images/Logo_1.jpeg" alt="Logo E-Station">
      <h2>E-STATION</h2>
    </div>
  </div>

  <!-- Tombol toggle tema -->
  <div class="theme-toggle">
    <button id="toggleTheme" aria-label="Ganti Tema">🌙</button>
  </div>

  <!-- Kontainer login -->
  <div class="container">
    <div class="login-card">
      <h1 class="title">E-STATION</h1>
      <p class="subtitle">Layanan Pengisian Kendaraan Listrik</p>

      <div class="illustration">
        <img src="../images/Logo_1.jpeg" alt="Logo E-Station">
      </div>
      
      <?php tampilkan_alert(); ?>
      
      <form action="login_process.php" method="POST" class="login-form">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Masukkan email" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Masukkan kata sandi" required>

        <div class="options">
          <label class="remember">
            <input type="checkbox" name="remember"> Ingat saya
          </label>
          <a href="lupa_password.php" class="forgot-link">Lupa kata sandi?</a>
        </div>

        <button type="submit" class="btn-login">Masuk</button>
      </form>

      <div class="register">
        <p>Belum punya akun? <a href="auth.php">Daftar di sini</a></p>
      </div>
    </div>
  </div>

  <script src="../js/script.js"></script>
  <script src="../js/clean-url.js"></script>
</body>
</html>