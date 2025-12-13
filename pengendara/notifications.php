<?php
session_start();
require_once '../config/koneksi.php';
require_once '../pesan/alerts.php';

// Cek authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pengendara') {
    header("Location: ../auth/login.php");
    exit;
}

$id_pengendara = $_SESSION['user_id'];

// Handle mark as read
if (isset($_GET['mark_read']) && isset($_GET['id'])) {
    $id_notif = intval($_GET['id']);
    try {
        $stmt = $koneksi->prepare("
            UPDATE notifikasi 
            SET dibaca = 1 
            WHERE id_notifikasi = ? AND id_penerima = ? AND tipe_penerima = 'pengendara'
        ");
        $stmt->execute([$id_notif, $id_pengendara]);
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'Notifikasi ditandai sudah dibaca'
        ];
        header("Location: notifications.php");
        exit;
    } catch (PDOException $e) {
        error_log("Error mark read: " . $e->getMessage());
    }
}

// Handle mark all as read
if (isset($_GET['mark_all_read'])) {
    try {
        $stmt = $koneksi->prepare("
            UPDATE notifikasi 
            SET dibaca = 1 
            WHERE id_penerima = ? AND tipe_penerima = 'pengendara' AND dibaca = 0
        ");
        $stmt->execute([$id_pengendara]);
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'Semua notifikasi ditandai sudah dibaca'
        ];
        header("Location: notifications.php");
        exit;
    } catch (PDOException $e) {
        error_log("Error mark all read: " . $e->getMessage());
    }
}

// Handle delete notification
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id_notif = intval($_GET['id']);
    try {
        $stmt = $koneksi->prepare("
            DELETE FROM notifikasi 
            WHERE id_notifikasi = ? AND id_penerima = ? AND tipe_penerima = 'pengendara'
        ");
        $stmt->execute([$id_notif, $id_pengendara]);
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'Notifikasi berhasil dihapus'
        ];
        header("Location: notifications.php");
        exit;
    } catch (PDOException $e) {
        error_log("Error delete notif: " . $e->getMessage());
    }
}

try {
    // Ambil semua notifikasi
    $stmt = $koneksi->prepare("
        SELECT * FROM notifikasi 
        WHERE id_penerima = ? AND tipe_penerima = 'pengendara'
        ORDER BY dikirim_pada DESC
    ");
    $stmt->execute([$id_pengendara]);
    $notifikasi = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Hitung notifikasi belum dibaca
    $stmt = $koneksi->prepare("
        SELECT COUNT(*) FROM notifikasi 
        WHERE id_penerima = ? AND tipe_penerima = 'pengendara' AND dibaca = 0
    ");
    $stmt->execute([$id_pengendara]);
    $notifBelumDibaca = $stmt->fetchColumn();

    // Ambil data pengendara untuk nama
    $stmt = $koneksi->prepare("SELECT nama FROM pengendara WHERE id_pengendara = ?");
    $stmt->execute([$id_pengendara]);
    $dataPengendara = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error load notifications: " . $e->getMessage());
    $notifikasi = [];
    $notifBelumDibaca = 0;
    $dataPengendara = ['nama' => 'Pengendara'];
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - E-Station</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/pengendara-style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="../css/alert.css?v=<?= time(); ?>">
    <link rel="icon" type="image/png" href="../images/Logo_1.png">
    <style>
        .notification-item {
            background: var(--card-bg);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
            position: relative;
        }
        .notification-item:hover {
            transform: translateX(5px);
            border-color: rgba(102, 126, 234, 0.3);
        }
        .notification-item.unread {
            border-left: 4px solid #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.05));
        }
        .notification-item.read {
            opacity: 0.7;
        }
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        .notification-title {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--text-color);
            margin-bottom: 5px;
        }
        .notification-message {
            color: var(--text-muted);
            margin-bottom: 10px;
            line-height: 1.6;
        }
        .notification-time {
            font-size: 0.85rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .notification-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .badge-unread {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state i {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 20px;
        }
        .info-box {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }
        @media (max-width: 768px) {
            .notification-item {
                padding: 15px;
            }
            .notification-header {
                flex-direction: column;
                gap: 10px;
            }
            .notification-actions {
                flex-direction: column;
            }
            .notification-actions a,
            .notification-actions button {
                width: 100%;
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
            <i class="fas fa-bell"></i>
            Notifikasi
        </div>
        <div class="header-actions">
            <button id="mobileThemeToggle">🌙</button>
            <button onclick="window.location.href='dashboard.php'">
                <i class="fas fa-home"></i>
            </button>
        </div>
    </div>
</div>

<div class="container mt-5 mb-5">
    <?php tampilkan_alert(); ?>

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h2 class="fw-bold mb-2">
                <i class="fas fa-bell me-2"></i>
                Notifikasi
                <?php if ($notifBelumDibaca > 0): ?>
                <span class="badge-unread"><?= $notifBelumDibaca; ?> Baru</span>
                <?php endif; ?>
            </h2>
            <p class="text-muted mb-0">Semua notifikasi dan update terkait akun Anda</p>
        </div>
        <div class="d-none d-md-block">
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    <!-- ACTION BUTTONS -->
    <?php if (!empty($notifikasi)): ?>
    <div class="d-flex gap-2 mb-4 flex-wrap">
        <?php if ($notifBelumDibaca > 0): ?>
        <a href="?mark_all_read=1" class="btn btn-sm btn-primary">
            <i class="fas fa-check-double me-2"></i>Tandai Semua Sudah Dibaca
        </a>
        <?php endif; ?>
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-filter me-2"></i>Filter
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="?filter=all">Semua</a></li>
                <li><a class="dropdown-item" href="?filter=unread">Belum Dibaca</a></li>
                <li><a class="dropdown-item" href="?filter=read">Sudah Dibaca</a></li>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <!-- NOTIFIKASI LIST -->
    <?php if (empty($notifikasi)): ?>
        <div class="empty-state">
            <i class="fas fa-bell-slash text-muted"></i>
            <h4 class="text-muted mb-3">Belum Ada Notifikasi</h4>
            <p class="text-muted mb-4">Anda akan menerima notifikasi terkait transaksi dan update akun di sini</p>
            <a href="dashboard.php" class="btn btn-primary">
                <i class="fas fa-home me-2"></i>Kembali ke Dashboard
            </a>
        </div>
    <?php else: ?>
        <div class="notifications-list">
            <?php 
            $filter = $_GET['filter'] ?? 'all';
            foreach ($notifikasi as $notif): 
                $isUnread = $notif['dibaca'] == 0;
                
                // Filter logic
                if ($filter === 'unread' && !$isUnread) continue;
                if ($filter === 'read' && $isUnread) continue;
                
                // Icon berdasarkan jenis notifikasi
                $icon = 'fa-info-circle';
                $iconColor = 'text-primary';
                
                if (stripos($notif['judul'], 'berhasil') !== false || stripos($notif['judul'], 'sukses') !== false) {
                    $icon = 'fa-check-circle';
                    $iconColor = 'text-success';
                } elseif (stripos($notif['judul'], 'gagal') !== false || stripos($notif['judul'], 'ditolak') !== false) {
                    $icon = 'fa-times-circle';
                    $iconColor = 'text-danger';
                } elseif (stripos($notif['judul'], 'transaksi') !== false || stripos($notif['judul'], 'charging') !== false) {
                    $icon = 'fa-bolt';
                    $iconColor = 'text-warning';
                } elseif (stripos($notif['judul'], 'pembayaran') !== false || stripos($notif['judul'], 'payment') !== false) {
                    $icon = 'fa-money-bill-wave';
                    $iconColor = 'text-success';
                } elseif (stripos($notif['judul'], 'saldo') !== false || stripos($notif['judul'], 'topup') !== false) {
                    $icon = 'fa-wallet';
                    $iconColor = 'text-info';
                } elseif (stripos($notif['judul'], 'promo') !== false || stripos($notif['judul'], 'diskon') !== false) {
                    $icon = 'fa-gift';
                    $iconColor = 'text-danger';
                }
                
                // Format waktu
                $waktu = strtotime($notif['dikirim_pada']);
                $sekarang = time();
                $selisih = $sekarang - $waktu;
                
                if ($selisih < 60) {
                    $waktuText = 'Baru saja';
                } elseif ($selisih < 3600) {
                    $waktuText = floor($selisih / 60) . ' menit yang lalu';
                } elseif ($selisih < 86400) {
                    $waktuText = floor($selisih / 3600) . ' jam yang lalu';
                } elseif ($selisih < 604800) {
                    $waktuText = floor($selisih / 86400) . ' hari yang lalu';
                } else {
                    $waktuText = date('d/m/Y H:i', $waktu);
                }
            ?>
            <div class="notification-item <?= $isUnread ? 'unread' : 'read'; ?>">
                <div class="notification-header">
                    <div style="flex: 1;">
                        <div class="notification-title">
                            <i class="fas <?= $icon; ?> <?= $iconColor; ?> me-2"></i>
                            <?= htmlspecialchars($notif['judul']); ?>
                            <?php if ($isUnread): ?>
                            <span class="badge bg-primary ms-2">Baru</span>
                            <?php endif; ?>
                        </div>
                        <div class="notification-time">
                            <i class="fas fa-clock"></i>
                            <?= $waktuText; ?>
                        </div>
                    </div>
                </div>
                
                <div class="notification-message">
                    <?= nl2br(htmlspecialchars($notif['pesan'])); ?>
                </div>

                <div class="notification-actions">
                    <?php if ($isUnread): ?>
                    <a href="?mark_read=1&id=<?= $notif['id_notifikasi']; ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-check me-1"></i>Tandai Sudah Dibaca
                    </a>
                    <?php endif; ?>
                    <a href="?delete=1&id=<?= $notif['id_notifikasi']; ?>" 
                       class="btn btn-sm btn-outline-danger"
                       onclick="return confirm('Yakin ingin menghapus notifikasi ini?')">
                        <i class="fas fa-trash me-1"></i>Hapus
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- INFO BOX -->
    <div class="info-box">
        <h6><i class="fas fa-lightbulb me-2 text-warning"></i>Tentang Notifikasi</h6>
        <ul class="mb-0" style="font-size: 0.9rem;">
            <li>Notifikasi akan dikirim ketika ada update penting terkait akun Anda</li>
            <li>Konfirmasi transaksi charging akan langsung dikirimkan</li>
            <li>Informasi pembayaran dan saldo akan muncul di sini</li>
            <li>Promo dan penawaran khusus juga akan diberitahukan melalui notifikasi</li>
            <li>Anda dapat menghapus notifikasi yang sudah tidak diperlukan</li>
        </ul>
    </div>
</div>

<!-- BOTTOM NAVIGATION (MOBILE) -->
<?php include '../components/bottom-nav.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Clean URL - Remove query parameters after page load
if (window.history.replaceState) {
    const url = new URL(window.location.href);
    if (url.search) {
        url.search = '';
        window.history.replaceState({}, document.title, url.toString());
    }
}

// === Tema Manual ===
const toggleBtn = document.getElementById("toggleTheme");
const mobileToggleBtn = document.getElementById("mobileThemeToggle");
const body = document.body;

// Fungsi untuk update icon theme
function updateThemeIcon(isLight) {
    const icon = isLight ? "🌙" : "☀️";
    if (toggleBtn) toggleBtn.textContent = icon;
    if (mobileToggleBtn) mobileToggleBtn.textContent = icon;
}

// Cek preferensi sebelumnya
if (localStorage.getItem("theme") === "light") {
    body.classList.add("light");
    updateThemeIcon(true);
} else {
    updateThemeIcon(false);
}

// Fungsi toggle theme
function toggleTheme() {
    body.classList.toggle("light");
    const isLight = body.classList.contains("light");
    
    if (isLight) {
        updateThemeIcon(true);
        localStorage.setItem("theme", "light");
    } else {
        updateThemeIcon(false);
        localStorage.setItem("theme", "dark");
    }
}

// Tombol toggle desktop
if (toggleBtn) {
    toggleBtn.addEventListener("click", toggleTheme);
}

// Tombol toggle mobile
if (mobileToggleBtn) {
    mobileToggleBtn.addEventListener("click", toggleTheme);
}

// === Auto dismiss alerts ===
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        if (alert) {
            alert.classList.add('fade-out');
            setTimeout(() => alert.remove(), 500);
        }
    });
}, 5000);

// === Smooth Scroll ===
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href !== '#' && href !== '#!') {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        }
    });
});
</script>
</body>
</html>