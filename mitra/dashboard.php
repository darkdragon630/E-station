<?php
session_start();
require_once '../config/koneksi.php';
require_once '../pesan/alerts.php';

// Cek authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mitra') {
    header("Location: ../auth/login.php");
    exit;
}


?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#667eea">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <title>Dashboard Mitra — E-Station</title>

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Link CSS eksternal -->
  <link rel="stylesheet" href="../css/mitra-style.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="../css/alert.css?v=<?= time(); ?>">
</head>
<body>

<!-- DESKTOP THEME TOGGLE -->
<div class="theme-toggle">
    <button id="toggleTheme">🌙</button>
</div>

<!-- DESKTOP NAVBAR - Reusable Component -->


<!-- MOBILE HEADER -->
<div class="mobile-header d-md-none">
    <div class="header-top">
        <div class="logo">
            <i class="fas fa-bolt"></i>
            E-Station Mitra
        </div>
        <div class="header-actions">
            <button id="mobileThemeToggle">🌙</button>
            <button onclick="window.location.href='notifications.php'">
                <i class="fas fa-bell"></i>
            </button>
        </div>
    </div>
    <div class="welcome-text">
        <h2>Hai, Mitra! 👋</h2>
        <p>Kelola stasiun pengisian Anda dengan mudah</p>
    </div>
</div>

<!-- CONTENT -->
<div class="container mt-md-5 mb-5">
    
    
    <!-- DESKTOP WELCOME -->
    <h2 class="fw-bold mb-3 d-none d-md-block">👋 Selamat Datang, Mitra E-Station!</h2>
    <p class="mb-4 d-none d-md-block">Kelola stasiun pengisian dan pantau performa usaha Anda dengan mudah</p>

    <!-- MOBILE QUICK STATS -->
    <div class="stats-grid d-md-none">
        <div class="stat-card" style="background: linear-gradient(135deg, rgba(124, 58, 237, 0.2), rgba(168, 85, 247, 0.15)); border: 1px solid rgba(168, 85, 247, 0.3);">
            <i class="fas fa-charging-station" style="color: #a855f7;"></i>
            <h4>0</h4>
            <small>Stasiun Aktif</small>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(220, 38, 38, 0.15)); border: 1px solid rgba(239, 68, 68, 0.3);">
            <i class="fas fa-receipt" style="color: #ef4444;"></i>
            <h4>0</h4>
            <small>Transaksi</small>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, rgba(6, 182, 212, 0.2), rgba(8, 145, 178, 0.15)); border: 1px solid rgba(6, 182, 212, 0.3);">
            <i class="fas fa-money-bill-wave" style="color: #06b6d4;"></i>
            <h4>Rp 0</h4>
            <small>Pendapatan</small>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(5, 150, 105, 0.15)); border: 1px solid rgba(16, 185, 129, 0.3);">
            <i class="fas fa-check-circle" style="color: #10b981;"></i>
            <h4>Aktif</h4>
            <small>Status</small>
        </div>
    </div>

    <!-- Quick Stats Desktop (2x2 Grid) -->
    <div class="row mb-4 d-none d-md-flex">
        <div class="col-md-6 mb-3">
            <div class="card text-center" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
                <div class="card-body">
                    <i class="fas fa-charging-station fa-2x mb-2"></i>
                    <h4 class="mb-0">0</h4>
                    <small>Stasiun Aktif</small>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card text-center" style="background: linear-gradient(135deg, #f093fb, #f5576c); color: white;">
                <div class="card-body">
                    <i class="fas fa-receipt fa-2x mb-2"></i>
                    <h4 class="mb-0">0</h4>
                    <small>Transaksi Bulan Ini</small>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card text-center" style="background: linear-gradient(135deg, #4facfe, #00f2fe); color: white;">
                <div class="card-body">
                    <i class="fas fa-money-bill-wave fa-2x mb-2"></i>
                    <h4 class="mb-0">Rp 0</h4>
                    <small>Pendapatan Total</small>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card text-center" style="background: linear-gradient(135deg, #43e97b, #38f9d7); color: white;">
                <div class="card-body">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <h4 class="mb-0">Terverifikasi</h4>
                    <small>Status Akun</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">

        <!-- KELOLA INFORMASI STASIUN -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <h5 class="card-title"><i class="fas fa-charging-station me-2"></i>Kelola Informasi Stasiun</h5>
                <p class="card-subtitle">Tambah, ubah, dan perbarui informasi stasiun pengisian Anda</p>
                <div class="text-center py-3">
                    <i class="fas fa-map-marker-alt fa-3x text-muted mb-3" style="opacity: 0.3;"></i>
                    <p class="text-muted mb-3">Belum ada stasiun terdaftar</p>
                    <a href="#station-form" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Tambah Stasiun
                    </a>
                </div>
            </div>
        </div>

        <!-- NOTIFIKASI RESERVASI -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <h5 class="card-title"><i class="fas fa-bell me-2"></i>Notifikasi Reservasi</h5>
                <p class="card-subtitle">Terima notifikasi ketika ada reservasi baru dari pengguna</p>
                <div class="text-center py-3">
                    <i class="fas fa-bell-slash fa-3x text-muted mb-3" style="opacity: 0.3;"></i>
                    <p class="text-muted">Tidak ada notifikasi baru</p>
                    <small class="text-muted">Anda akan menerima update di sini</small>
                </div>
            </div>
        </div>

        <!-- RIWAYAT PENGGUNAAN -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <h5 class="card-title"><i class="fas fa-chart-line me-2"></i>Riwayat Penggunaan Stasiun</h5>
                <p class="card-subtitle">Lihat detail penggunaan dan analisis tingkat penggunaan</p>
                <div class="text-center py-3">
                    <i class="fas fa-chart-bar fa-3x text-muted mb-3" style="opacity: 0.3;"></i>
                    <p class="text-muted mb-3">Belum ada data penggunaan</p>
                    <a href="usage_history.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-history me-1"></i>Lihat Riwayat
                    </a>
                </div>
            </div>
        </div>

        <!-- LAPORAN PENDAPATAN -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <h5 class="card-title"><i class="fas fa-file-invoice-dollar me-2"></i>Laporan Pendapatan</h5>
                <p class="card-subtitle">Pantau pendapatan dan download laporan bulanan</p>
                <div class="text-center py-3">
                    <i class="fas fa-file-pdf fa-3x text-muted mb-3" style="opacity: 0.3;"></i>
                    <p class="text-muted mb-3">Belum ada laporan tersedia</p>
                    <a href="reports.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-download me-1"></i>Download Laporan
                    </a>
                </div>
            </div>
        </div>

        <!-- PROFIL MITRA -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <h5 class="card-title"><i class="fas fa-user-circle me-2"></i>Profil Mitra</h5>
                <p class="card-subtitle">Kelola informasi akun dan data pribadi Anda</p>
                <div class="text-center py-3">
                    <i class="fas fa-user-tie fa-3x text-muted mb-3" style="opacity: 0.3;"></i>
                    <p class="text-muted mb-3">Lengkapi profil Anda</p>
                    <a href="edit_profile.php" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i>Edit Profil
                    </a>
                </div>
            </div>
        </div>

        <!-- STATUS PENGAJUAN -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <h5 class="card-title"><i class="fas fa-tasks me-2"></i>Status Pengajuan</h5>
                <p class="card-subtitle">Pantau status pengajuan stasiun Anda</p>
                <div class="text-center py-3">
                    <i class="fas fa-clipboard-check fa-3x text-muted mb-3" style="opacity: 0.3;"></i>
                    <p class="text-muted mb-3">Belum ada pengajuan</p>
                    <a href="#status-table" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-eye me-1"></i>Lihat Status
                    </a>
                </div>
            </div>
        </div>

    </div>

    <!-- FORMULIR PENDAFTARAN STASIUN -->
    <div class="station-form" id="station-form">
        <h4><i class="fas fa-clipboard-list me-2"></i>Ajukan Pendaftaran Stasiun Baru</h4>
        <p class="form-description">Lengkapi formulir di bawah ini untuk mendaftarkan stasiun pengisian baru. Status akan berubah menjadi <span class="status-badge status-pending">Menunggu Verifikasi</span> setelah diajukan.</p>
        
        <form id="stationForm" action="proses_stasiun.php" method="POST" enctype="multipart/form-data">
            
            <div class="form-group">
                <label for="nama_stasiun">Nama Stasiun *</label>
                <input type="text" id="nama_stasiun" name="nama_stasiun" placeholder="Contoh: Stasiun E-Charge Jakarta Pusat" required>
            </div>

            <div class="form-group">
                <label for="alamat_stasiun">Alamat Lengkap *</label>
                <textarea id="alamat_stasiun" name="alamat_stasiun" rows="3" placeholder="Jl. Contoh No. 123, Kelurahan, Kecamatan, Kota" required></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="latitude">Latitude (Koordinat) *</label>
                    <input type="text" id="latitude" name="latitude" placeholder="-6.200000" required>
                </div>

                <div class="form-group">
                    <label for="longitude">Longitude (Koordinat) *</label>
                    <input type="text" id="longitude" name="longitude" placeholder="106.816666" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="kapasitas">Kapasitas Charger *</label>
                    <input type="number" id="kapasitas" name="kapasitas" placeholder="10" min="1" required>
                </div>

                <div class="form-group">
                    <label for="jumlah_slot">Jumlah Slot *</label>
                    <input type="number" id="jumlah_slot" name="jumlah_slot" placeholder="5" min="1" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="tarif">Tarif per kWh (Rp) *</label>
                    <input type="number" id="tarif" name="tarif" placeholder="2500" min="0" required>
                </div>

                <div class="form-group">
                    <label for="jam_operasional">Jam Operasional *</label>
                    <input type="text" id="jam_operasional" name="jam_operasional" placeholder="08:00 - 22:00" required>
                </div>
            </div>

            <div class="form-group">
                <label for="fasilitas">Fasilitas Tambahan</label>
                <textarea id="fasilitas" name="fasilitas" rows="2" placeholder="WiFi gratis, Mushola, Toilet, Kantin, dll"></textarea>
            </div>

            <div class="form-group">
                <label for="dokumen_izin">Upload Dokumen Izin Usaha (PDF/JPG) *</label>
                <input type="file" id="dokumen_izin" name="dokumen_izin" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>

            <div class="form-group">
                <label for="foto_stasiun">Upload Foto Stasiun (Opsional)</label>
                <input type="file" id="foto_stasiun" name="foto_stasiun" accept=".jpg,.jpeg,.png">
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane me-2"></i>Ajukan Pendaftaran Stasiun
            </button>
            
            <p class="form-note">
                * Wajib diisi. Setelah pengajuan berhasil, status akan menjadi <span class="status-badge status-pending">Menunggu Verifikasi</span>
            </p>
        </form>
    </div>

    <!-- STATUS PENGAJUAN STASIUN -->
    <div class="station-form" id="status-table">
        <h4><i class="fas fa-list-check me-2"></i>Status Pengajuan Stasiun</h4>
        <p class="form-description">Pantau status pengajuan stasiun Anda. Anda akan menerima notifikasi jika ada perubahan status.</p>
        
        <div class="table-responsive">
            <table class="status-table">
                <thead>
                    <tr>
                        <th>Nama Stasiun</th>
                        <th>Tanggal Ajuan</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2" style="opacity: 0.3;"></i>
                            <p class="mb-0">Belum ada pengajuan stasiun</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <p class="form-note">
            <i class="fas fa-info-circle me-1"></i>
            Jika pengajuan ditolak, Anda dapat melihat alasan penolakan dan mengajukan ulang setelah memperbaiki data.
        </p>
    </div>

    <!-- TIPS SECTION -->
    <div class="card" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(168, 85, 247, 0.1)); border: 1px solid rgba(102, 126, 234, 0.3);">
        <div class="card-body">
            <h5 class="card-title"><i class="fas fa-lightbulb me-2" style="color: #fbbf24;"></i>Tips Pengelolaan Stasiun</h5>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h6><i class="fas fa-map-marker-alt me-2 text-primary"></i>Lengkapi Informasi Stasiun</h6>
                    <p class="small mb-0">Pastikan semua informasi stasiun Anda lengkap dan akurat termasuk lokasi koordinat, kapasitas, tarif, dan jam operasional</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h6><i class="fas fa-bell me-2 text-warning"></i>Pantau Notifikasi</h6>
                    <p class="small mb-0">Selalu periksa notifikasi reservasi dari pengguna untuk memastikan slot pengisian tersedia dan meningkatkan kepuasan pelanggan</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h6><i class="fas fa-chart-line me-2 text-success"></i>Analisis Penggunaan</h6>
                    <p class="small mb-0">Gunakan data riwayat penggunaan untuk mengidentifikasi jam sibuk dan mengoptimalkan operasional stasiun Anda</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h6><i class="fas fa-file-download me-2 text-info"></i>Download Laporan Rutin</h6>
                    <p class="small mb-0">Download laporan pendapatan bulanan dalam format PDF atau Excel untuk memantau performa finansial usaha Anda</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h6><i class="fas fa-check-circle me-2 text-success"></i>Verifikasi Dokumen</h6>
                    <p class="small mb-0">Pastikan dokumen izin usaha Anda valid dan terupdate agar pengajuan stasiun dapat disetujui dengan cepat oleh Admin</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h6><i class="fas fa-sync-alt me-2 text-primary"></i>Update Data Berkala</h6>
                    <p class="small mb-0">Perbarui informasi stasiun secara berkala jika ada perubahan tarif, jam operasional, atau fasilitas untuk menjaga akurasi data</p>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- BOTTOM NAVIGATION (MOBILE) - Reusable Component -->
<?php include '../components/bottom-nav-mitra.php'; ?>

<!-- SCRIPT -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/clean-url.js"></script>
<script src="../js/mitra-dashboard.js"></script>

</body>
</html>
