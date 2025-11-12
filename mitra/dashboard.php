<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once "../config/koneksi.php";
require_once "../pesan/alerts.php";

// Pastikan sudah login sebagai mitra
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mitra') {
    redirect_with_alert('../auth/login.php', 'error', 'Akses ditolak! Silakan login sebagai Mitra.');
    exit;
}

// Ambil ID Mitra dari session
$id_mitra = $_SESSION['user_id'];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard Mitra — E-Station</title>

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
  <!-- Link CSS eksternal -->
  <link rel="stylesheet" href="../css/mitra-style.css">
</head>
<body>

<div class="container">

  <!-- Header -->
  <header class="topbar">
    <div class="brand">
      <div class="logo">⚡</div>
      <div>
        <h1>E-Station</h1>
        <div class="subtitle">Kelola Mitra & Charging</div>
      </div>
    </div>

    <nav class="topnav">
      <a href="#">Cari Lokasi</a>
      <a href="#">Riwayat</a>
      <a href="#">Stok Baterai</a>
      <a href="../auth/logout.php">Logout</a>
      <div class="moon" title="Mode Gelap">🌙</div>
    </nav>
  </header>

  <!-- Hero -->
  <section class="hero">
    <div class="greeting">
      <h2>Selamat Datang </h2>
      <p>Kelola perjalanan charging Anda dengan mudah</p>
    </div>

    <div class="stats">
      <div class="card c1">
        <div class="value">0</div>
        <div class="label">Transaksi Terbaru</div>
      </div>
      <div class="card c2">
        <div class="value">0</div>
        <div class="label">Kendaraan Terdaftar</div>
      </div>
      <div class="card c3">
        <div class="value">0</div>
        <div class="label">Notifikasi Baru</div>
      </div>
      <div class="card c4">
        <div class="value">Aktif</div>
        <div class="label">Status Akun</div>
      </div>
    </div>
  </section>

  <!-- Panel -->
  <section class="panel">
    <h3>Aksi Cepat</h3>

    <div class="quick-grid">
      <div class="quick small1">
        <div>Cari Stasiun</div>
        <small>Temukan stasiun terdekat</small>
      </div>
      <div class="quick small2">
        <div>Cek Stok Baterai</div>
        <small>Real-time availability</small>
      </div>
      <div class="quick small3">
        <div>Riwayat</div>
        <small>Lihat transaksi Anda</small>
      </div>
      <div class="quick small4">
        <div>Profil</div>
        <small>Kelola akun Anda</small>
      </div>
    </div>

    <div class="three-cards">
      <div class="glass-card">
        <h3>Kendaraan Aktif</h3>
        <p>Belum ada kendaraan terdaftar</p>
        <a href="#" class="btn-add">+ Tambah Kendaraan</a>
      </div>

      <div class="glass-card">
        <h3>Transaksi Terbaru</h3>
        <p>Belum ada transaksi</p>
        <p>Mulai charging untuk melihat riwayat</p>
      </div>

      <div class="glass-card">
        <h3>Notifikasi</h3>
        <p>Tidak ada notifikasi</p>
        <p>Anda akan menerima update di sini</p>
      </div>
    </div>

    <div class="tips">
      <div class="title">
        <h4>💡 Tips Penggunaan</h4>
      </div>
      <div class="list">
        <div class="tip">
          <h5>📍 Cari Stasiun Terdekat</h5>
          <p>Gunakan fitur "Cari Lokasi" untuk menemukan stasiun pengisian terdekat dari posisi Anda.</p>
        </div>
        <div class="tip">
          <h5>🔋 Cek Ketersediaan</h5>
          <p>Periksa stok baterai real-time sebelum berkunjung untuk menghindari kehabisan stok.</p>
        </div>
        <div class="tip">
          <h5>🧭 Estimasi Perjalanan</h5>
          <p>Lihat estimasi waktu dan jarak untuk merencanakan perjalanan Anda.</p>
        </div>
      </div>
    </div>

  </section>
</div>

</body>
</html>
