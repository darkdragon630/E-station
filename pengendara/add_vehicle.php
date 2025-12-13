<?php
session_start();
require_once '../config/koneksi.php';
require_once '../pesan/alerts.php';

// Cek authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pengendara') {
    header('Location: ../auth/login.php');
    exit;
}

$id_pengendara = $_SESSION['user_id'];

// Ambil data pengendara
try {
    $stmt = $koneksi->prepare("SELECT nama FROM pengendara WHERE id_pengendara = ?");
    $stmt->execute([$id_pengendara]);
    $dataPengendara = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dataPengendara = ['nama' => $_SESSION['nama'] ?? 'Pengendara'];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $merk = trim($_POST['merk'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $no_plat = strtoupper(trim($_POST['no_plat'] ?? ''));
    $tahun = trim($_POST['tahun'] ?? '');
    
    $errors = [];
    
    // Validasi input
    if (empty($merk)) {
        $errors[] = "Merk kendaraan wajib diisi";
    }
    
    if (empty($model)) {
        $errors[] = "Model kendaraan wajib diisi";
    }
    
    if (empty($no_plat)) {
        $errors[] = "Nomor plat wajib diisi";
    } elseif (!preg_match('/^[A-Z]{1,2}\s?\d{1,4}\s?[A-Z]{1,3}$/i', $no_plat)) {
        $errors[] = "Format nomor plat tidak valid (contoh: B 1234 ABC)";
    }
    
    if (empty($tahun)) {
        $errors[] = "Tahun kendaraan wajib diisi";
    } elseif (!is_numeric($tahun) || $tahun < 1900 || $tahun > date('Y') + 1) {
        $errors[] = "Tahun kendaraan tidak valid";
    }
    
    // Cek apakah nomor plat sudah terdaftar
    if (empty($errors)) {
        try {
            $stmt = $koneksi->prepare("SELECT id_kendaraan FROM kendaraan WHERE no_plat = ?");
            $stmt->execute([$no_plat]);
            if ($stmt->fetch()) {
                $errors[] = "Nomor plat sudah terdaftar dalam sistem";
            }
        } catch (PDOException $e) {
            $errors[] = "Gagal memeriksa nomor plat: " . $e->getMessage();
        }
    }
    
    // Insert data jika tidak ada error
    if (empty($errors)) {
        try {
            $stmt = $koneksi->prepare("
                INSERT INTO kendaraan (id_pengendara, merk, model, no_plat, tahun) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$id_pengendara, $merk, $model, $no_plat, $tahun]);
            
            set_flash_message('success', 'Berhasil!', 'Kendaraan berhasil ditambahkan');
            header('Location: manage_vehicles.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = "Gagal menambahkan kendaraan: " . $e->getMessage();
        }
    }
    
    // Simpan error ke session
    if (!empty($errors)) {
        set_error_handler('danger', 'Gagal!', implode('<br>', $errors));
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a192f">
    <title>Tambah Kendaraan - E-Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/pengendara-style.css">
    <link rel="stylesheet" href="../css/alert.css">
    <link rel="icon" type="image/png" href="../images/Logo_1.png">
    <style>
        .form-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        
        .form-label {
            font-weight: 600;
            color: #e2e8f0;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.08);
            border: 1.5px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 12px 16px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.12);
            border-color: #60a5fa;
            color: white;
            box-shadow: 0 0 0 0.2rem rgba(96, 165, 250, 0.25);
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .form-select option {
            background: #1e293b;
            color: white;
        }
        
        .input-group-text {
            background: rgba(96, 165, 250, 0.2);
            border: 1.5px solid rgba(96, 165, 250, 0.3);
            color: #60a5fa;
            border-radius: 12px 0 0 12px;
        }
        
        .input-group .form-control {
            border-radius: 0 12px 12px 0;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2563eb, #3b82f6, #60a5fa);
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.5);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1.5px solid rgba(255, 255, 255, 0.2);
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            color: #e2e8f0;
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        /* Responsive Button Styles */
        @media (max-width: 576px) {
            .btn {
                padding: 10px 16px;
                font-size: 0.9rem;
            }
            
            .btn i {
                font-size: 0.85rem;
            }
            
            .action-buttons {
                display: flex;
                flex-direction: column;
                gap: 10px;
                width: 100%;
            }
            
            .action-buttons .btn {
                width: 100%;
            }
        }
        
        .info-box {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
        }
        
        .info-box i {
            color: #60a5fa;
        }
        
        .form-text {
            color: #94a3b8;
            font-size: 0.875rem;
        }
        
        .required {
            color: #ef4444;
        }
        
        @media (max-width: 768px) {
            .form-card {
                padding: 20px;
            }
            
            .info-box {
                padding: 12px;
                margin-bottom: 20px;
            }
            
            .info-box ul {
                padding-left: 20px;
            }
            
            h2 {
                font-size: 1.5rem;
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
<?php include '../components/navbar-pengendara.php'; ?>

<!-- MOBILE HEADER -->
<div class="mobile-header d-md-none">
    <div class="header-top">
        <div class="logo">
            <i class="fas fa-bolt"></i> E-Station
        </div>
        <div class="header-actions">
            <button id="mobileThemeToggle">🌙</button>
        </div>
    </div>
    <div class="welcome-text">
        <h2>🚗 Tambah Kendaraan</h2>
        <p>Daftarkan kendaraan listrik Anda</p>
    </div>
</div>

<!-- CONTENT -->
<div class="container mt-4 mb-5">
    <?php tampilkan_alert(); ?>
    
    <h2 class="fw-bold mb-4 d-none d-md-block">
        <i class="fas fa-car-side me-2"></i>Tambah Kendaraan Baru
    </h2>
    
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <!-- Info Box -->
            <div class="info-box">
                <h6 class="mb-2">
                    <i class="fas fa-info-circle me-2"></i>Informasi Penting
                </h6>
                <ul class="mb-0 small" style="line-height: 1.8;">
                    <li>Pastikan data kendaraan yang Anda masukkan sesuai dengan STNK</li>
                    <li>Nomor plat harus unik dan belum terdaftar di sistem</li>
                    <li>Format nomor plat: <strong>B 1234 ABC</strong> (huruf-angka-huruf)</li>
                    <li>Kendaraan yang didaftarkan harus kendaraan listrik</li>
                </ul>
            </div>
            
            <!-- Form Card -->
            <div class="form-card">
                <form method="POST" action="" id="addVehicleForm">
                    
                    <!-- Merk Kendaraan -->
                    <div class="mb-4">
                        <label for="merk" class="form-label">
                            <i class="fas fa-trademark me-2"></i>Merk Kendaraan <span class="required">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-car"></i>
                            </span>
                            <input type="text" 
                                   class="form-control" 
                                   id="merk" 
                                   name="merk" 
                                   placeholder="Contoh: Tesla, Hyundai, Wuling" 
                                   value="<?= htmlspecialchars($_POST['merk'] ?? '') ?>"
                                   required>
                        </div>
                        <small class="form-text">Masukkan merk kendaraan listrik Anda</small>
                    </div>
                    
                    <!-- Model Kendaraan -->
                    <div class="mb-4">
                        <label for="model" class="form-label">
                            <i class="fas fa-car-side me-2"></i>Model Kendaraan <span class="required">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-tag"></i>
                            </span>
                            <input type="text" 
                                   class="form-control" 
                                   id="model" 
                                   name="model" 
                                   placeholder="Contoh: Model 3, Ioniq 5, Air ev" 
                                   value="<?= htmlspecialchars($_POST['model'] ?? '') ?>"
                                   required>
                        </div>
                        <small class="form-text">Masukkan model/tipe kendaraan</small>
                    </div>
                    
                    <!-- Nomor Plat -->
                    <div class="mb-4">
                        <label for="no_plat" class="form-label">
                            <i class="fas fa-id-card me-2"></i>Nomor Plat <span class="required">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-hashtag"></i>
                            </span>
                            <input type="text" 
                                   class="form-control text-uppercase" 
                                   id="no_plat" 
                                   name="no_plat" 
                                   placeholder="B 1234 ABC" 
                                   value="<?= htmlspecialchars($_POST['no_plat'] ?? '') ?>"
                                   maxlength="20"
                                   style="text-transform: uppercase;"
                                   required>
                        </div>
                        <small class="form-text">
                            Format: Huruf-Angka-Huruf (contoh: B 1234 ABC, DK 567 XY)
                        </small>
                    </div>
                    
                    <!-- Tahun Kendaraan -->
                    <div class="mb-4">
                        <label for="tahun" class="form-label">
                            <i class="fas fa-calendar-alt me-2"></i>Tahun Kendaraan <span class="required">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-calendar"></i>
                            </span>
                            <input type="number" 
                                   class="form-control" 
                                   id="tahun" 
                                   name="tahun" 
                                   placeholder="<?= date('Y') ?>" 
                                   value="<?= htmlspecialchars($_POST['tahun'] ?? '') ?>"
                                   min="2000" 
                                   max="<?= date('Y') + 1 ?>"
                                   required>
                        </div>
                        <small class="form-text">Tahun pembuatan kendaraan (<?= date('Y') - 24 ?> - <?= date('Y') + 1 ?>)</small>
                    </div>
                    
                    <!-- Preview Data -->
                    <div class="info-box" id="previewBox" style="display: none;">
                        <h6 class="mb-2">
                            <i class="fas fa-eye me-2"></i>Preview Data Kendaraan
                        </h6>
                        <div id="previewContent" class="small"></div>
                    </div>
                    
                    <!-- Buttons -->
                    <div class="action-buttons d-flex gap-2 justify-content-end mt-4">
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>Batal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Simpan
                        </button>
                    </div>
                    
                </form>
            </div>
            
            <!-- Tips Section -->
            <div class="mt-4 p-3" style="background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.3); border-radius: 12px;">
                <h6 class="mb-2" style="color: #fbbf24;">
                    <i class="fas fa-lightbulb me-2"></i>Tips:
                </h6>
                <ul class="mb-0 small" style="color: #fef3c7; line-height: 1.6;">
                    <li>Periksa kembali data sebelum menyimpan</li>
                    <li>Nomor plat yang sudah terdaftar tidak bisa didaftarkan lagi</li>
                    <li>Anda bisa mengelola kendaraan di menu "Kelola Kendaraan"</li>
                </ul>
            </div>
            
        </div>
    </div>
    
</div>

<!-- BOTTOM NAVIGATION (MOBILE) -->
<?php include '../components/bottom-nav.php'; ?>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/clean-url.js"></script>
<script>
// ========== THEME TOGGLE ==========
function initThemeToggle() {
    const toggleButtons = [
        document.getElementById("toggleTheme"),
        document.getElementById("mobileThemeToggle")
    ];
    
    const savedTheme = localStorage.getItem("theme");
    if (savedTheme === "light") {
        document.body.classList.add("light");
        toggleButtons.forEach(btn => {
            if (btn) btn.textContent = "☀️";
        });
    }
    
    toggleButtons.forEach(btn => {
        if (btn) {
            btn.addEventListener("click", () => {
                document.body.classList.toggle("light");
                const isLight = document.body.classList.contains("light");
                toggleButtons.forEach(b => {
                    if (b) b.textContent = isLight ? "☀️" : "🌙";
                });
                localStorage.setItem("theme", isLight ? "light" : "dark");
            });
        }
    });
}

// ========== AUTO UPPERCASE NOMOR PLAT ==========
document.getElementById('no_plat').addEventListener('input', function(e) {
    this.value = this.value.toUpperCase();
    updatePreview();
});

// ========== FORMAT NOMOR PLAT ==========
document.getElementById('no_plat').addEventListener('blur', function() {
    let value = this.value.replace(/\s+/g, ' ').trim();
    // Auto format: B1234ABC -> B 1234 ABC
    value = value.replace(/([A-Z]+)(\d+)([A-Z]+)/g, '$1 $2 $3');
    this.value = value;
    updatePreview();
});

// ========== VALIDASI TAHUN ==========
document.getElementById('tahun').addEventListener('input', function() {
    const currentYear = new Date().getFullYear();
    const year = parseInt(this.value);
    
    if (year < 2000) {
        this.value = 2000;
    } else if (year > currentYear + 1) {
        this.value = currentYear + 1;
    }
    updatePreview();
});

// ========== LIVE PREVIEW ==========
function updatePreview() {
    const merk = document.getElementById('merk').value;
    const model = document.getElementById('model').value;
    const no_plat = document.getElementById('no_plat').value;
    const tahun = document.getElementById('tahun').value;
    
    if (merk && model && no_plat && tahun) {
        document.getElementById('previewBox').style.display = 'block';
        document.getElementById('previewContent').innerHTML = `
            <div style="line-height: 1.8;">
                <strong style="color: #60a5fa;">📝 Ringkasan:</strong><br>
                <i class="fas fa-car me-2"></i><strong>Kendaraan:</strong> ${merk} ${model} (${tahun})<br>
                <i class="fas fa-id-card me-2"></i><strong>No. Plat:</strong> <span style="color: #fbbf24; font-weight: 600;">${no_plat}</span>
            </div>
        `;
    } else {
        document.getElementById('previewBox').style.display = 'none';
    }
}

// Add event listeners for all inputs
['merk', 'model', 'no_plat', 'tahun'].forEach(id => {
    document.getElementById(id).addEventListener('input', updatePreview);
});

// ========== FORM VALIDATION ==========
document.getElementById('addVehicleForm').addEventListener('submit', function(e) {
    const no_plat = document.getElementById('no_plat').value.trim();
    const regex = /^[A-Z]{1,2}\s?\d{1,4}\s?[A-Z]{1,3}$/i;
    
    if (!regex.test(no_plat)) {
        e.preventDefault();
        alert('⚠️ Format nomor plat tidak valid!\n\nGunakan format: Huruf-Angka-Huruf\nContoh: B 1234 ABC atau DK 567 XY');
        document.getElementById('no_plat').focus();
        return false;
    }
    
    const tahun = parseInt(document.getElementById('tahun').value);
    const currentYear = new Date().getFullYear();
    
    if (tahun < 2000 || tahun > currentYear + 1) {
        e.preventDefault();
        alert(`⚠️ Tahun kendaraan tidak valid!\n\nTahun harus antara 2000 - ${currentYear + 1}`);
        document.getElementById('tahun').focus();
        return false;
    }
    
    return true;
});

// ========== PREVENT DOUBLE TAP ZOOM ==========
let lastTouchEnd = 0;
document.addEventListener('touchend', function(event) {
    const now = (new Date()).getTime();
    if (now - lastTouchEnd <= 300) {
        event.preventDefault();
    }
    lastTouchEnd = now;
}, false);

// ========== INIT ==========
initThemeToggle();
</script>

</body>
</html>