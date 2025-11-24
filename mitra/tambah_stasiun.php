<?php
session_start();
require_once '../config/koneksi.php';
require_once '../pesan/alerts.php';

// Cek authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mitra') {
    header('Location: ../auth/login.php');
    exit;
}

$id_mitra = $_SESSION['user_id'];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#0f2746">
  <title>Tambah Stasiun — E-Station</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/mitra-style.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="../css/alert.css?v=<?= time(); ?>">

  <style>
    .page-header {
      margin-bottom: 30px;
    }

    .page-header h2 {
      background: linear-gradient(90deg, #b98cff, #ff6fa6);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      font-weight: 800;
      margin-bottom: 5px;
    }

    .page-header p {
      color: var(--muted);
    }

    .btn-back {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: #dff2ff;
      padding: 10px 20px;
      border-radius: 10px;
      font-weight: 600;
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
      margin-bottom: 20px;
    }

    .btn-back:hover {
      background: rgba(255, 255, 255, 0.15);
      color: white;
      transform: translateX(-3px);
    }

    body.light .btn-back {
      background: rgba(0, 0, 0, 0.05);
      border-color: rgba(0, 0, 0, 0.1);
      color: #1e293b;
    }

    .info-alert {
      background: rgba(68, 216, 255, 0.1);
      border: 1px solid rgba(68, 216, 255, 0.3);
      border-radius: 12px;
      padding: 15px 20px;
      margin-bottom: 25px;
      display: flex;
      align-items: flex-start;
      gap: 15px;
    }

    .info-alert i {
      color: #44d8ff;
      font-size: 1.5rem;
      margin-top: 2px;
    }

    .info-alert .content h6 {
      color: #44d8ff;
      font-weight: 700;
      margin-bottom: 5px;
    }

    .info-alert .content p {
      color: var(--muted);
      margin: 0;
      font-size: 0.9rem;
    }

    .form-container {
      max-width: 900px;
      margin: 0 auto;
    }

    @media (max-width: 768px) {
      .form-container {
        padding: 0;
      }

      .btn-back {
        margin-bottom: 15px;
      }
    }
  </style>
</head>
<body>

<!-- DESKTOP THEME TOGGLE -->
<div class="theme-toggle">
    <button id="toggleTheme">🌙</button>
</div>

<!-- DESKTOP NAVBAR -->
<?php include '../components/navbar-mitra.php'; ?>

<!-- MOBILE HEADER -->
<div class="mobile-header d-md-none">
    <div class="header-top">
        <a href="dashboard.php" class="btn-back" style="margin: 0; padding: 8px 15px;">
            <i class="fas fa-arrow-left"></i>
            Kembali
        </a>
        <div class="header-actions">
            <button id="mobileThemeToggle">🌙</button>
        </div>
    </div>
</div>

<!-- CONTENT -->
<div class="container mt-4 mb-5">
    <?php tampilkan_alert(); ?>

    <div class="form-container">

        <!-- Back Button Desktop -->
        <a href="dashboard.php" class="btn-back d-none d-md-inline-flex">
            <i class="fas fa-arrow-left"></i>
            Kembali ke Dashboard
        </a>

        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="fas fa-plus-circle me-2"></i>Tambah Stasiun Baru</h2>
            <p>Lengkapi formulir di bawah ini untuk mendaftarkan stasiun pengisian baru</p>
        </div>

        <!-- Info Alert -->
        <div class="info-alert">
            <i class="fas fa-info-circle"></i>
            <div class="content">
                <h6>Informasi Penting</h6>
                <p>Setelah pengajuan berhasil dikirim, status stasiun akan menjadi <span class="status-badge status-pending">Menunggu Verifikasi</span>. Admin akan mereview dan memverifikasi data stasiun Anda dalam 1-3 hari kerja.</p>
            </div>
        </div>

        <!-- FORMULIR PENDAFTARAN STASIUN -->
        <div class="station-form">
            <h4><i class="fas fa-clipboard-list me-2"></i>Formulir Pendaftaran Stasiun</h4>
            <p class="form-description">Pastikan semua data yang diisi sudah benar dan lengkap.</p>
            
            <form id="stationForm" action="proses_stasiun.php" method="POST" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="nama_stasiun">Nama Stasiun <span class="text-danger">*</span></label>
                    <input type="text" id="nama_stasiun" name="nama_stasiun" placeholder="Contoh: Stasiun E-Charge Jakarta Pusat" required>
                </div>

                <div class="form-group">
                    <label for="alamat">Alamat Lengkap <span class="text-danger">*</span></label>
                    <textarea id="alamat" name="alamat" rows="3" placeholder="Jl. Contoh No. 123, Kelurahan, Kecamatan, Kota, Provinsi" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="latitude">Latitude (Koordinat) <span class="text-danger">*</span></label>
                        <input type="text" id="latitude" name="latitude" placeholder="-6.200000" required>
                        <small class="form-text">Contoh: -6.200000 untuk Jakarta</small>
                    </div>

                    <div class="form-group">
                        <label for="longitude">Longitude (Koordinat) <span class="text-danger">*</span></label>
                        <input type="text" id="longitude" name="longitude" placeholder="106.816666" required>
                        <small class="form-text">Contoh: 106.816666 untuk Jakarta</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="kapasitas">Kapasitas Charger <span class="text-danger">*</span></label>
                        <input type="number" id="kapasitas" name="kapasitas" placeholder="10" min="1" required>
                        <small class="form-text">Jumlah total unit charger</small>
                    </div>

                    <div class="form-group">
                        <label for="jumlah_slot">Jumlah Slot <span class="text-danger">*</span></label>
                        <input type="number" id="jumlah_slot" name="jumlah_slot" placeholder="5" min="1" required>
                        <small class="form-text">Slot yang tersedia untuk pengisian</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="tarif">Tarif per kWh (Rp) <span class="text-danger">*</span></label>
                        <input type="number" id="tarif" name="tarif" placeholder="2500" min="0" required>
                        <small class="form-text">Harga dalam Rupiah</small>
                    </div>

                    <div class="form-group">
                        <label for="jam_operasional">Jam Operasional <span class="text-danger">*</span></label>
                        <input type="text" id="jam_operasional" name="jam_operasional" placeholder="08:00 - 22:00" required>
                        <small class="form-text">Format: 08:00 - 22:00</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="fasilitas">Fasilitas Tambahan</label>
                    <textarea id="fasilitas" name="fasilitas" rows="2" placeholder="WiFi gratis, Mushola, Toilet, Kantin, Parkir luas, dll"></textarea>
                    <small class="form-text">Opsional - pisahkan dengan koma</small>
                </div>

                <div class="form-group">
                    <label for="dokumen_izin">Upload Dokumen Izin Usaha <span class="text-danger">*</span></label>
                    <input type="file" id="dokumen_izin" name="dokumen_izin" accept=".pdf,.jpg,.jpeg,.png" required>
                    <small class="form-text">Format: PDF, JPG, PNG (Maks. 5MB)</small>
                </div>

                <div class="form-group">
                    <label for="foto_stasiun">Upload Foto Stasiun</label>
                    <input type="file" id="foto_stasiun" name="foto_stasiun" accept=".jpg,.jpeg,.png">
                    <small class="form-text">Opsional - Format: JPG, PNG (Maks. 5MB)</small>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane me-2"></i>Ajukan Pendaftaran Stasiun
                </button>
                
                <p class="form-note text-center">
                    <i class="fas fa-shield-alt me-1"></i>
                    Data Anda akan diverifikasi oleh admin sebelum stasiun aktif di aplikasi
                </p>
            </form>
        </div>

    </div>
</div>

<!-- BOTTOM NAVIGATION (MOBILE) -->
<?php include '../components/bottom-nav-mitra.php'; ?>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Theme Toggle
function initTheme(btnId) {
    const btn = document.getElementById(btnId);
    if (!btn) return;
    
    const saved = localStorage.getItem("theme");
    if (saved === "light") {
        document.body.classList.add("light");
        btn.textContent = "☀️";
    } else {
        btn.textContent = "🌙";
    }

    btn.addEventListener("click", () => {
        document.body.classList.toggle("light");
        const isLight = document.body.classList.contains("light");
        btn.textContent = isLight ? "☀️" : "🌙";
        localStorage.setItem("theme", isLight ? "light" : "dark");
        
        const other = btnId === "toggleTheme" ? "mobileThemeToggle" : "toggleTheme";
        const otherBtn = document.getElementById(other);
        if (otherBtn) otherBtn.textContent = isLight ? "☀️" : "🌙";
    });
}

initTheme("toggleTheme");
initTheme("mobileThemeToggle");

// Form Validation
document.getElementById('stationForm').addEventListener('submit', function(e) {
    const lat = parseFloat(document.getElementById('latitude').value);
    const lng = parseFloat(document.getElementById('longitude').value);
    
    // Validasi koordinat
    if (isNaN(lat) || lat < -90 || lat > 90) {
        e.preventDefault();
        alert('⚠️ Latitude harus antara -90 dan 90!');
        return;
    }
    
    if (isNaN(lng) || lng < -180 || lng > 180) {
        e.preventDefault();
        alert('⚠️ Longitude harus antara -180 dan 180!');
        return;
    }
    
    // Validasi file
    const dokumen = document.getElementById('dokumen_izin').files[0];
    if (dokumen && dokumen.size > 5 * 1024 * 1024) {
        e.preventDefault();
        alert('⚠️ Ukuran file dokumen maksimal 5MB!');
        return;
    }
    
    const foto = document.getElementById('foto_stasiun').files[0];
    if (foto && foto.size > 5 * 1024 * 1024) {
        e.preventDefault();
        alert('⚠️ Ukuran foto maksimal 5MB!');
        return;
    }
    
    if (!confirm('Ajukan pendaftaran stasiun ini?')) {
        e.preventDefault();
    }
});
</script>

</body>
</html>