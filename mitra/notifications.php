<?php
session_start();
require_once '../config/koneksi.php';
require_once '../pesan/alerts.php';

// Cek authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mitra') {
    header("Location: ../auth/login.php");
    exit;
}

$id_mitra = $_SESSION['user_id'];

// Handle mark as read
if (isset($_GET['mark_read']) && isset($_GET['id'])) {
    $id_notif = intval($_GET['id']);
    try {
        $stmt = $koneksi->prepare("
            UPDATE notifikasi 
            SET dibaca = 1 
            WHERE id_notifikasi = ? AND id_penerima = ? AND tipe_penerima = 'mitra'
        ");
        $stmt->execute([$id_notif, $id_mitra]);
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
            WHERE id_penerima = ? AND tipe_penerima = 'mitra' AND dibaca = 0
        ");
        $stmt->execute([$id_mitra]);
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
            WHERE id_notifikasi = ? AND id_penerima = ? AND tipe_penerima = 'mitra'
        ");
        $stmt->execute([$id_notif, $id_mitra]);
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
        WHERE id_penerima = ? AND tipe_penerima = 'mitra'
        ORDER BY dikirim_pada DESC
    ");
    $stmt->execute([$id_mitra]);
    $notifikasi = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Hitung notifikasi belum dibaca
    $stmt = $koneksi->prepare("
        SELECT COUNT(*) FROM notifikasi 
        WHERE id_penerima = ? AND tipe_penerima = 'mitra' AND dibaca = 0
    ");
    $stmt->execute([$id_mitra]);
    $notifBelumDibaca = $stmt->fetchColumn();

    // Ambil data mitra untuk nama
    $stmt = $koneksi->prepare("SELECT nama_mitra FROM mitra WHERE id_mitra = ?");
    $stmt->execute([$id_mitra]);
    $dataMitra = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error load notifications: " . $e->getMessage());
    $notifikasi = [];
    $notifBelumDibaca = 0;
    $dataMitra = ['nama_mitra' => 'Mitra'];
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - E-Station Mitra</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/mitra-style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="../css/alert.css?v=<?= time(); ?>">
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
            border-color: rgba(123, 97, 255, 0.3);
        }
        .notification-item.unread {
            border-left: 4px solid #7b61ff;
            background: linear-gradient(135deg, rgba(123, 97, 255, 0.1), rgba(255, 107, 154, 0.05));
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
        }
        .badge-unread {
            background: linear-gradient(135deg, #7b61ff, #ff6b9a);
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
        @media (max-width: 768px) {
            .notification-item {
                padding: 15px;
            }
            .notification-header {
                flex-direction: column;
                gap: 10px;
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-2">
                <i class="fas fa-bell me-2"></i>
                Notifikasi
                <?php if ($notifBelumDibaca > 0): ?>
                <span class="badge-unread"><?= $notifBelumDibaca; ?> Baru</span>
                <?php endif; ?>
            </h2>
            <p class="text-muted mb-0">Semua notifikasi dan update terkait stasiun Anda</p>
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
            <p class="text-muted mb-4">Anda akan menerima notifikasi terkait stasiun dan transaksi di sini</p>
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
                
                if (stripos($notif['judul'], 'disetujui') !== false || stripos($notif['judul'], 'approved') !== false) {
                    $icon = 'fa-check-circle';
                    $iconColor = 'text-success';
                } elseif (stripos($notif['judul'], 'ditolak') !== false || stripos($notif['judul'], 'rejected') !== false) {
                    $icon = 'fa-times-circle';
                    $iconColor = 'text-danger';
                } elseif (stripos($notif['judul'], 'transaksi') !== false || stripos($notif['judul'], 'pembayaran') !== false) {
                    $icon = 'fa-money-bill-wave';
                    $iconColor = 'text-success';
                } elseif (stripos($notif['judul'], 'stok') !== false || stripos($notif['judul'], 'baterai') !== false) {
                    $icon = 'fa-battery-half';
                    $iconColor = 'text-warning';
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

        <!-- PAGINATION (Optional - jika notifikasi banyak) -->
        <?php if (count($notifikasi) > 20): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item"><a class="page-link" href="#">Previous</a></li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item"><a class="page-link" href="#">Next</a></li>
            </ul>
        </nav>
        <?php endif; ?>
    <?php endif; ?>

    <!-- INFO BOX -->
    <div class="card mt-4" style="background: linear-gradient(135deg, rgba(123, 97, 255, 0.1), rgba(255, 107, 154, 0.1)); border: 1px solid rgba(123, 97, 255, 0.3);">
        <div class="card-body">
            <h6><i class="fas fa-lightbulb me-2 text-warning"></i>Tentang Notifikasi</h6>
            <ul class="mb-0" style="font-size: 0.9rem;">
                <li>Notifikasi akan dikirim ketika ada update penting terkait stasiun Anda</li>
                <li>Status pengajuan stasiun (disetujui/ditolak) akan diberitahukan melalui notifikasi</li>
                <li>Informasi transaksi dan pembayaran juga akan muncul di sini</li>
                <li>Peringatan stok baterai rendah akan dikirim otomatis</li>
            </ul>
        </div>
    </div>
</div>

<!-- BOTTOM NAVIGATION (MOBILE) -->
<?php include '../components/bottom-nav-mitra.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/clean-url.js?v=<?= time(); ?>"></script>
<script src="../js/mitra-dashboard.js?v=<?= time(); ?>"></script>
</body>
</html>