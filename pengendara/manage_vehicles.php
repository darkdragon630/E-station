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

// Handle Delete Vehicle
if (isset($_GET['delete'])) {
    $id_kendaraan = $_GET['delete'];
    try {
        // Cek apakah kendaraan milik user
        $stmt = $koneksi->prepare("SELECT id_kendaraan FROM kendaraan WHERE id_kendaraan = ? AND id_pengendara = ?");
        $stmt->execute([$id_kendaraan, $id_pengendara]);
        
        if ($stmt->fetch()) {
            // Hapus kendaraan
            $stmt = $koneksi->prepare("DELETE FROM kendaraan WHERE id_kendaraan = ?");
            $stmt->execute([$id_kendaraan]);
            
            set_flash_message('success', 'Berhasil!', 'Kendaraan berhasil dihapus');
        } else {
            set_flash_message('danger', 'Gagal!', 'Kendaraan tidak ditemukan');
        }
    } catch (PDOException $e) {
        set_flash_message('danger', 'Gagal!', 'Gagal menghapus kendaraan: ' . $e->getMessage());
    }
    header('Location: manage_vehicles.php');
    exit;
}

// Handle Set Active Vehicle - Simpan di session saja
if (isset($_GET['set_active'])) {
    $id_kendaraan = $_GET['set_active'];
    try {
        // Cek apakah kendaraan milik user
        $stmt = $koneksi->prepare("SELECT id_kendaraan, merk, model, no_plat FROM kendaraan WHERE id_kendaraan = ? AND id_pengendara = ?");
        $stmt->execute([$id_kendaraan, $id_pengendara]);
        
        if ($kendaraan = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Simpan ID kendaraan aktif di session
            $_SESSION['kendaraan_aktif'] = $id_kendaraan;
            
            set_flash_message('success', 'Berhasil!', 'Kendaraan aktif: ' . $kendaraan['merk'] . ' ' . $kendaraan['model']);
        }
    } catch (PDOException $e) {
        set_flash_message('danger', 'Gagal!', 'Gagal mengubah status kendaraan');
    }
    header('Location: manage_vehicles.php');
    exit;
}

// Ambil semua kendaraan
try {
    $stmt = $koneksi->prepare("SELECT * FROM kendaraan WHERE id_pengendara = ? ORDER BY id_kendaraan DESC");
    $stmt->execute([$id_pengendara]);
    $kendaraan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tentukan kendaraan aktif dari session atau ambil yang pertama
    $kendaraan_aktif_id = $_SESSION['kendaraan_aktif'] ?? ($kendaraan_list[0]['id_kendaraan'] ?? null);
} catch (PDOException $e) {
    $kendaraan_list = [];
    $kendaraan_aktif_id = null;
}

// Ambil data pengendara
try {
    $stmt = $koneksi->prepare("SELECT nama FROM pengendara WHERE id_pengendara = ?");
    $stmt->execute([$id_pengendara]);
    $dataPengendara = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dataPengendara = ['nama' => $_SESSION['nama'] ?? 'Pengendara'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a192f">
    <title>Kelola Kendaraan - E-Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/pengendara-style.css">
    <link rel="stylesheet" href="../css/alert.css">
    <link rel="icon" type="image/png" href="../images/Logo_1.png">
    <style>
        .vehicle-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 16px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .vehicle-card:hover {
            transform: translateY(-5px);
            border-color: rgba(96, 165, 250, 0.5);
            box-shadow: 0 10px 30px rgba(96, 165, 250, 0.2);
        }
        
        .vehicle-card.active {
            border: 2px solid #60a5fa;
            background: rgba(96, 165, 250, 0.1);
        }
        
        .vehicle-card.active::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #60a5fa, #3b82f6);
        }
        
        .active-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .vehicle-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            margin-bottom: 15px;
        }
        
        .vehicle-info h5 {
            color: #60a5fa;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .vehicle-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin: 15px 0;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #e2e8f0;
            font-size: 0.9rem;
        }
        
        .detail-item i {
            color: #60a5fa;
            width: 20px;
        }
        
        .plate-number {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: #1e293b;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1.1rem;
            display: inline-block;
            letter-spacing: 2px;
        }
        
        .vehicle-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            flex: 1;
            min-width: 120px;
            padding: 10px 16px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-set-active {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .btn-set-active:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
        }
        
        .btn-add-vehicle {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            padding: 14px 24px;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-add-vehicle:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.5);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 5rem;
            color: #64748b;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state h4 {
            color: #94a3b8;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #64748b;
            margin-bottom: 30px;
        }
        
        @media (max-width: 576px) {
            .vehicle-actions {
                flex-direction: column;
            }
            
            .btn-action {
                width: 100%;
                min-width: auto;
            }
            
            .active-badge {
                font-size: 0.7rem;
                padding: 4px 10px;
                top: 12px;
                right: 12px;
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
        <a href="dashboard.php" style="color: #60a5fa; text-decoration: none;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div style="font-size: 1.1rem; font-weight: 700; color: #fff;">Kelola Kendaraan</div>
        <div style="width: 24px;"></div>
    </div>
    <div class="welcome-text">
        <h2>🚗 Kendaraan Anda</h2>
        <p>Kelola semua kendaraan listrik Anda</p>
    </div>
</div>

<!-- CONTENT -->
<div class="container mt-4 mb-5">
    <?php tampilkan_alert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4 d-none d-md-flex">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="fas fa-car me-2"></i>Kelola Kendaraan
            </h2>
            <p class="text-muted mb-0">Total: <?= count($kendaraan_list); ?> kendaraan</p>
        </div>
        <a href="add_vehicle.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Tambah Kendaraan
        </a>
    </div>
    
    <?php if (empty($kendaraan_list)): ?>
        <!-- Empty State -->
        <div class="empty-state">
            <i class="fas fa-car-side"></i>
            <h4>Belum Ada Kendaraan</h4>
            <p>Tambahkan kendaraan listrik Anda untuk mulai menggunakan layanan charging</p>
            <a href="add_vehicle.php" class="btn-add-vehicle" style="max-width: 300px; margin: 0 auto;">
                <i class="fas fa-plus"></i>
                <span>Tambah Kendaraan Pertama</span>
            </a>
        </div>
    <?php else: ?>
        <!-- Vehicle List -->
        <div class="row">
            <div class="col-lg-10 col-xl-8 mx-auto">
                <?php foreach ($kendaraan_list as $kendaraan): 
                    $is_active = ($kendaraan['id_kendaraan'] == $kendaraan_aktif_id);
                ?>
                    <div class="vehicle-card <?= $is_active ? 'active' : ''; ?>">
                        <?php if ($is_active): ?>
                            <div class="active-badge">
                                <i class="fas fa-check-circle"></i>
                                <span>Aktif</span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="vehicle-icon">
                            <i class="fas fa-car"></i>
                        </div>
                        
                        <div class="vehicle-info">
                            <h5><?= htmlspecialchars($kendaraan['merk'] . ' ' . $kendaraan['model']); ?></h5>
                            
                            <div class="vehicle-details">
                                <div class="detail-item">
                                    <i class="fas fa-id-card"></i>
                                    <div>
                                        <strong>Nomor Plat:</strong><br>
                                        <span class="plate-number"><?= htmlspecialchars($kendaraan['no_plat']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><strong>Tahun:</strong> <?= htmlspecialchars($kendaraan['tahun']); ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <i class="fas fa-car"></i>
                                    <span><strong>ID Kendaraan:</strong> #<?= $kendaraan['id_kendaraan']; ?></span>
                                </div>
                            </div>
                            
                            <div class="vehicle-actions">
                                <?php if (!$is_active): ?>
                                    <a href="?set_active=<?= $kendaraan['id_kendaraan']; ?>" 
                                       class="btn-action btn-set-active"
                                       onclick="return confirm('Jadikan kendaraan ini sebagai kendaraan aktif?');">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Jadikan Aktif</span>
                                    </a>
                                <?php else: ?>
                                    <button class="btn-action btn-set-active" disabled style="opacity: 0.5; cursor: not-allowed;">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Sedang Aktif</span>
                                    </button>
                                <?php endif; ?>
                                
                                <a href="?delete=<?= $kendaraan['id_kendaraan']; ?>" 
                                   class="btn-action btn-delete"
                                   onclick="return confirm('Yakin ingin menghapus kendaraan ini?\n\n<?= htmlspecialchars($kendaraan['merk'] . ' ' . $kendaraan['model']); ?>\n<?= htmlspecialchars($kendaraan['no_plat']); ?>');">
                                    <i class="fas fa-trash-alt"></i>
                                    <span>Hapus</span>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Add More Button -->
                <a href="add_vehicle.php" class="btn-add-vehicle">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Kendaraan Baru</span>
                </a>
            </div>
        </div>
    <?php endif; ?>
    
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