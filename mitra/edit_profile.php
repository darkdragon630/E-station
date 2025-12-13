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

// Ambil data mitra lengkap
try {
    $stmt = $koneksi->prepare("
        SELECT 
            nama_mitra, 
            email, 
            no_telepon, 
            alamat, 
            status, 
            email_terverifikasi,
            created_at
        FROM mitra 
        WHERE id_mitra = ?
    ");
    $stmt->execute([$id_mitra]);
    $dataMitra = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$dataMitra) {
        set_flash_message('error', 'Data mitra tidak ditemukan!');
        header('Location: dashboard.php');
        exit;
    }
} catch (PDOException $e) {
    set_flash_message('error', 'Gagal mengambil data: ' . $e->getMessage());
    header('Location: dashboard.php');
    exit;
}

// Format tanggal bergabung
$tanggalBergabung = '-';
if (!empty($dataMitra['created_at'])) {
    $date = new DateTime($dataMitra['created_at']);
    $tanggalBergabung = $date->format('d M Y');
}

// Status badge class
$statusClass = 'status-pending';
$statusText = 'Pending';
if ($dataMitra['status'] == 'disetujui') {
    $statusClass = 'status-approved';
    $statusText = 'Disetujui';
} elseif ($dataMitra['status'] == 'ditolak') {
    $statusClass = 'status-rejected';
    $statusText = 'Ditolak';
}

// Email verification status
$emailVerified = $dataMitra['email_terverifikasi'] == 1;
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#0f2746">
  <title>Edit Profil Mitra — E-Station</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/mitra-style.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="../css/alert.css?v=<?= time(); ?>">
  <link rel="icon" type="image/png" href="../images/Logo_1.png">

  <style>
    .profile-container {
      max-width: 800px;
      margin: 0 auto;
    }

    .profile-header {
      text-align: center;
      margin-bottom: 30px;
    }

    .profile-avatar {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      background: linear-gradient(135deg, #7b61ff, #ff6b9a);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      box-shadow: 0 8px 30px rgba(123, 97, 255, 0.4);
    }

    .profile-avatar i {
      font-size: 3.5rem;
      color: white;
    }

    .profile-header h2 {
      background: linear-gradient(90deg, #b98cff, #ff6fa6);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      font-weight: 800;
      margin-bottom: 5px;
    }

    .profile-header p {
      color: var(--muted);
    }

    .info-card {
      background: rgba(68, 216, 255, 0.08);
      border: 1px solid rgba(68, 216, 255, 0.2);
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 30px;
    }

    .info-card h5 {
      color: #44d8ff;
      font-weight: 700;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .info-list {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
    }

    .info-item {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .info-item .icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
    }

    .info-item .icon.status { background: rgba(255, 184, 107, 0.2); color: #ffb86b; }
    .info-item .icon.email { background: rgba(49, 210, 138, 0.2); color: #31d28a; }
    .info-item .icon.date { background: rgba(123, 97, 255, 0.2); color: #7b61ff; }

    .info-item .text {
      flex: 1;
    }

    .info-item .text small {
      display: block;
      color: var(--muted);
      font-size: 0.8rem;
    }

    .info-item .text span {
      font-weight: 600;
      color: #dff2ff;
    }

    body.light .info-item .text span {
      color: #1e293b;
    }

    .form-section {
      background: var(--panel-bg);
      border: var(--border);
      border-radius: 16px;
      padding: 30px;
      margin-bottom: 25px;
      box-shadow: var(--shadow);
    }

    .form-section h5 {
      color: #cfe6ff;
      font-weight: 700;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    body.light .form-section h5 {
      color: #1e293b;
      border-bottom-color: rgba(0, 0, 0, 0.1);
    }

    .form-label {
      color: #dff2ff;
      font-weight: 600;
      margin-bottom: 8px;
      font-size: 0.95rem;
    }

    body.light .form-label {
      color: #1e293b;
    }

    .form-control {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 10px;
      padding: 12px 15px;
      color: #eaf2ff;
      font-family: 'Poppins', sans-serif;
      transition: all 0.3s;
    }

    body.light .form-control {
      background: rgba(255, 255, 255, 0.9);
      border: 2px solid rgba(226, 232, 240, 0.8);
      color: #1e293b;
    }

    .form-control:focus {
      background: rgba(255, 255, 255, 0.08);
      border-color: #2d9cff;
      box-shadow: 0 0 0 3px rgba(45, 156, 255, 0.2);
      color: #eaf2ff;
    }

    body.light .form-control:focus {
      background: #fff;
      border-color: #1d4ed8;
    }

    .form-control::placeholder {
      color: rgba(255, 255, 255, 0.4);
    }

    body.light .form-control::placeholder {
      color: #94a3b8;
    }

    .form-control:disabled,
    .form-control[readonly] {
      background: rgba(255, 255, 255, 0.02);
      color: rgba(255, 255, 255, 0.6);
      cursor: not-allowed;
    }

    body.light .form-control:disabled,
    body.light .form-control[readonly] {
      background: #f1f5f9;
      color: #64748b;
    }

    .form-text {
      color: var(--muted);
      font-size: 0.8rem;
      margin-top: 5px;
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

    .btn-save {
      background: linear-gradient(90deg, #2d9cff, #6bd7ff);
      border: none;
      color: #041c2b;
      padding: 14px 40px;
      border-radius: 10px;
      font-weight: 700;
      font-size: 1rem;
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      box-shadow: 0 4px 15px rgba(45, 156, 255, 0.4);
      width: 100%;
      justify-content: center;
    }

    .btn-save:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(45, 156, 255, 0.6);
    }

    .password-toggle {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: var(--muted);
      cursor: pointer;
      padding: 5px;
    }

    .password-toggle:hover {
      color: #2d9cff;
    }

    .input-group-password {
      position: relative;
    }

    .input-group-password .form-control {
      padding-right: 50px;
    }

    @media (max-width: 768px) {
      .profile-container {
        padding: 0 15px;
      }

      .form-section {
        padding: 20px;
      }

      .info-list {
        grid-template-columns: 1fr;
      }

      .profile-avatar {
        width: 100px;
        height: 100px;
      }

      .profile-avatar i {
        font-size: 2.8rem;
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
        <a href="dashboard.php" class="btn-back">
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
    
    <!-- Back Button Desktop -->
    <a href="dashboard.php" class="btn-back mb-4 d-none d-md-inline-flex">
        <i class="fas fa-arrow-left"></i>
        Kembali ke Dashboard
    </a>

    <div class="profile-container">
        
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user-tie"></i>
            </div>
            <h2>Edit Profil Mitra</h2>
            <p>Lengkapi dan perbarui informasi akun Anda</p>
        </div>

        <!-- Info Card -->
        <div class="info-card">
            <h5><i class="fas fa-info-circle"></i> Informasi Akun</h5>
            <div class="info-list">
                <div class="info-item">
                    <div class="icon status">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="text">
                        <small>Status Akun</small>
                        <span class="status-badge <?= $statusClass; ?>"><?= $statusText; ?></span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="icon email">
                        <i class="fas fa-envelope-circle-check"></i>
                    </div>
                    <div class="text">
                        <small>Email Terverifikasi</small>
                        <span>
                            <?php if ($emailVerified): ?>
                                <i class="fas fa-check-circle text-success"></i> Sudah
                            <?php else: ?>
                                <i class="fas fa-times-circle text-danger"></i> Belum
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="icon date">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="text">
                        <small>Bergabung Sejak</small>
                        <span><?= htmlspecialchars($tanggalBergabung); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form -->
        <form id="profileForm" action="proses_profil_mitra.php" method="POST">
            
            <!-- Data Pribadi -->
            <div class="form-section">
                <h5><i class="fas fa-user"></i> Data Pribadi</h5>
                
                <div class="mb-3">
                    <label for="nama_mitra" class="form-label">Nama Mitra <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control" 
                           id="nama_mitra" 
                           name="nama_mitra" 
                           value="<?= htmlspecialchars($dataMitra['nama_mitra']); ?>"
                           placeholder="Masukkan nama lengkap" 
                           required>
                    <div class="form-text">Nama lengkap sesuai identitas</div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" 
                           class="form-control" 
                           id="email" 
                           name="email" 
                           value="<?= htmlspecialchars($dataMitra['email']); ?>"
                           readonly>
                    <div class="form-text"><i class="fas fa-lock me-1"></i> Email tidak dapat diubah</div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="no_telepon" class="form-label">Nomor Telepon</label>
                        <input type="tel" 
                               class="form-control" 
                               id="no_telepon" 
                               name="no_telepon" 
                               value="<?= htmlspecialchars($dataMitra['no_telepon'] ?? ''); ?>"
                               placeholder="08123456789">
                        <div class="form-text">Format: 08xxxxxxxxxx</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">Status Akun</label>
                        <input type="text" 
                               class="form-control" 
                               id="status" 
                               value="<?= $statusText; ?>" 
                               readonly>
                        <div class="form-text"><i class="fas fa-lock me-1"></i> Dikelola oleh admin</div>
                    </div>
                </div>

                <div class="mb-0">
                    <label for="alamat" class="form-label">Alamat Lengkap</label>
                    <textarea class="form-control" 
                              id="alamat" 
                              name="alamat" 
                              rows="3" 
                              placeholder="Jl. Contoh No. 123, Kelurahan, Kecamatan, Kota, Provinsi"><?= htmlspecialchars($dataMitra['alamat'] ?? ''); ?></textarea>
                    <div class="form-text">Alamat lengkap sesuai KTP atau domisili</div>
                </div>
            </div>

            <!-- Keamanan Akun -->
            <div class="form-section">
                <h5><i class="fas fa-lock"></i> Keamanan Akun</h5>
                <p class="form-text mb-4" style="margin-top: -10px;">Kosongkan jika tidak ingin mengubah password</p>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password_baru" class="form-label">Password Baru</label>
                        <div class="input-group-password">
                            <input type="password" class="form-control" id="password_baru" name="password_baru" placeholder="Masukkan password baru">
                            <button type="button" class="password-toggle" onclick="togglePassword('password_baru', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Minimal 8 karakter</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="konfirmasi_password" class="form-label">Konfirmasi Password</label>
                        <div class="input-group-password">
                            <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" placeholder="Ulangi password baru">
                            <button type="button" class="password-toggle" onclick="togglePassword('konfirmasi_password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Harus sama dengan password baru</div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-save">
                <i class="fas fa-save"></i>
                Simpan Perubahan
            </button>

            <p class="form-text text-center mt-3">
                <i class="fas fa-shield-alt me-1"></i>
                Semua perubahan akan tercatat dalam sistem
            </p>
        </form>

    </div>
</div>

<!-- BOTTOM NAVIGATION (MOBILE) -->
<?php include '../components/bottom-nav-mitra.php'; ?>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/clean-url.js"></script>
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
        
        // Sync both buttons
        const other = btnId === "toggleTheme" ? "mobileThemeToggle" : "toggleTheme";
        const otherBtn = document.getElementById(other);
        if (otherBtn) otherBtn.textContent = isLight ? "☀️" : "🌙";
    });
}

initTheme("toggleTheme");
initTheme("mobileThemeToggle");

// Toggle Password Visibility
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}

// Form Validation
document.getElementById('profileForm').addEventListener('submit', function(e) {
    const noTelepon = document.getElementById('no_telepon').value;
    const passwordBaru = document.getElementById('password_baru').value;
    const konfirmasi = document.getElementById('konfirmasi_password').value;
    
    // Validasi nomor telepon
    if (noTelepon) {
        const phoneRegex = /^(08|62|0)[0-9]{9,12}$/;
        if (!phoneRegex.test(noTelepon)) {
            e.preventDefault();
            alert('⚠️ Format nomor telepon tidak valid!');
            return;
        }
    }
    
    // Validasi password
    if (passwordBaru) {
        if (passwordBaru.length < 8) {
            e.preventDefault();
            alert('⚠️ Password minimal 8 karakter!');
            return;
        }
        if (passwordBaru !== konfirmasi) {
            e.preventDefault();
            alert('⚠️ Konfirmasi password tidak sama!');
            return;
        }
    }
    
    if (!confirm('Simpan perubahan profil?')) {
        e.preventDefault();
    }
});

// Auto-format nomor telepon
document.getElementById('no_telepon').addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/\D/g, '');
});
</script>

</body>
</html>