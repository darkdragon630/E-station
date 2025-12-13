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

// Ambil ID stasiun dari GET atau POST
$id_stasiun = 0;
if (isset($_GET['id'])) {
    $id_stasiun = intval($_GET['id']);
} elseif (isset($_POST['id_stasiun'])) {
    $id_stasiun = intval($_POST['id_stasiun']);
}

if ($id_stasiun <= 0) {
    set_flash_message('error', 'ID Stasiun tidak valid');
    header("Location: dashboard.php");
    exit;
}

// Proses form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nama_stasiun = trim($_POST['nama_stasiun'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $kapasitas = intval($_POST['kapasitas'] ?? 0);
        $jumlah_slot = intval($_POST['jumlah_slot'] ?? 0);
        $tarif_per_kwh = floatval($_POST['tarif_per_kwh'] ?? 0);
        $jam_operasional = trim($_POST['jam_operasional'] ?? '');
        $fasilitas = trim($_POST['fasilitas'] ?? '');
        $latitude = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
        $longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;
        $status_operasional = $_POST['status_operasional'] ?? 'nonaktif';

        // Validasi
        if (empty($nama_stasiun) || empty($alamat) || $kapasitas <= 0 || $tarif_per_kwh <= 0) {
            set_flash_message('error', 'Semua field wajib diisi dengan benar!');
        } else {
            // Ambil status stasiun saat ini
            $checkStmt = $koneksi->prepare("SELECT status FROM stasiun_pengisian WHERE id_stasiun = ? AND id_mitra = ?");
            $checkStmt->execute([$id_stasiun, $id_mitra]);
            $currentStatus = $checkStmt->fetchColumn();
            
            // Tentukan status baru berdasarkan kondisi
            $newStatus = $currentStatus;
            $newStatusOperasional = $status_operasional;
            
            // Jika stasiun ditolak dan sedang diperbaiki, ubah status ke pending
            if ($currentStatus === 'ditolak') {
                $newStatus = 'pending';
                $newStatusOperasional = 'nonaktif'; // Set nonaktif saat menunggu approval
                set_flash_message('info', 'Stasiun akan diajukan ulang untuk verifikasi admin.');
            }
            // Jika stasiun belum disetujui (pending), tetap pending
            elseif ($currentStatus === 'pending') {
                $newStatus = 'pending';
                $newStatusOperasional = 'nonaktif';
            }
            // Jika stasiun sudah disetujui, biarkan statusnya dan gunakan status_operasional yang dipilih
            
            // Update data stasiun
            $stmt = $koneksi->prepare("
                UPDATE stasiun_pengisian 
                SET nama_stasiun = ?, 
                    alamat = ?, 
                    kapasitas = ?, 
                    jumlah_slot = ?,
                    tarif_per_kwh = ?, 
                    jam_operasional = ?, 
                    fasilitas = ?,
                    latitude = ?,
                    longitude = ?,
                    status_operasional = ?,
                    status = ?,
                    alasan_penolakan = NULL,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id_stasiun = ? AND id_mitra = ?
            ");
            
            $success = $stmt->execute([
                $nama_stasiun,
                $alamat,
                $kapasitas,
                $jumlah_slot,
                $tarif_per_kwh,
                $jam_operasional,
                $fasilitas,
                $latitude,
                $longitude,
                $newStatusOperasional,
                $newStatus,
                $id_stasiun,
                $id_mitra
            ]);

            if ($success && $stmt->rowCount() > 0) {
                if ($currentStatus === 'ditolak') {
                    set_flash_message('success', 'Data stasiun berhasil diperbaiki dan diajukan ulang untuk verifikasi!');
                } else {
                    set_flash_message('success', 'Data stasiun berhasil diperbarui!');
                }
                header("Location: detail_stasiun.php?id=" . $id_stasiun);
                exit;
            } else {
                set_flash_message('error', 'Gagal memperbarui data stasiun atau tidak ada perubahan');
            }
        }
    } catch (PDOException $e) {
        error_log("Error Update Stasiun: " . $e->getMessage());
        set_flash_message('error', 'Terjadi kesalahan: ' . $e->getMessage());
    }
}

// Ambil data stasiun
try {
    $stmt = $koneksi->prepare("
        SELECT * FROM stasiun_pengisian 
        WHERE id_stasiun = ? AND id_mitra = ?
    ");
    $stmt->execute([$id_stasiun, $id_mitra]);
    $stasiun = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$stasiun) {
        set_flash_message('error', 'Stasiun tidak ditemukan');
        header("Location: dashboard.php");
        exit;
    }

    $isApproved = $stasiun['status'] === 'disetujui';

} catch (PDOException $e) {
    error_log("Error Load Stasiun: " . $e->getMessage());
    set_flash_message('error', 'Gagal memuat data stasiun: ' . $e->getMessage());
    header("Location: dashboard.php");
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Stasiun - <?= htmlspecialchars($stasiun['nama_stasiun']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/mitra-style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="../css/alert.css?v=<?= time(); ?>">
    <style>
        #map {
            height: 400px;
            border-radius: 15px;
            border: 2px solid rgba(185, 140, 255, 0.3);
            margin-bottom: 15px;
            overflow: hidden;
        }
        .map-info {
            background: rgba(68, 216, 255, 0.1);
            border: 1px solid rgba(68, 216, 255, 0.3);
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        .coordinate-display {
            background: rgba(185, 140, 255, 0.1);
            border: 1px solid rgba(185, 140, 255, 0.3);
            border-radius: 10px;
            padding: 15px;
            margin-top: 10px;
            display: none;
        }
        .coordinate-display.show {
            display: block;
        }
        .gps-status-temp {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 768px) {
            #map {
                height: 300px;
            }
        }
    </style>
</head>
<body>

<div class="theme-toggle">
    <button id="toggleTheme">🌙</button>
</div>

<?php include '../components/navbar-mitra.php'; ?>

<div class="container mt-5 mb-5">
    <?php tampilkan_alert(); ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-2">
                <i class="fas fa-edit me-2"></i>Edit Stasiun
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="kelola_stasiun.php">Kelola Stasiun</a></li>
                    <li class="breadcrumb-item"><a href="detail_stasiun.php?id=<?= $id_stasiun; ?>">Detail</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <a href="detail_stasiun.php?id=<?= $id_stasiun; ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <?php if ($stasiun['status'] === 'ditolak' && !empty($stasiun['alasan_penolakan'])): ?>
    <div class="alert alert-warning">
        <h6 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Catatan Penolakan</h6>
        <p class="mb-0"><?= htmlspecialchars($stasiun['alasan_penolakan']); ?></p>
        <hr>
        <small>Harap perbaiki sesuai catatan di atas. Setelah disimpan, stasiun akan diajukan ulang untuk verifikasi admin.</small>
    </div>
    <?php endif; ?>

    <?php if ($stasiun['status'] === 'pending'): ?>
    <div class="alert alert-info">
        <h6 class="alert-heading"><i class="fas fa-clock me-2"></i>Status: Menunggu Verifikasi</h6>
        <p class="mb-0">Stasiun Anda sedang dalam proses verifikasi admin. Anda masih dapat melakukan perubahan sebelum disetujui.</p>
    </div>
    <?php endif; ?>

    <div class="station-form">
        <h4><i class="fas fa-charging-station me-2"></i>Informasi Stasiun</h4>
        <p class="form-description">
            <?php 
            if ($stasiun['status'] === 'ditolak') {
                echo 'Perbaiki data stasiun sesuai catatan penolakan di atas';
            } elseif ($stasiun['status'] === 'pending') {
                echo 'Lengkapi atau perbaiki data stasiun sebelum diverifikasi';
            } else {
                echo 'Edit informasi operasional stasiun Anda';
            }
            ?>
        </p>

        <form method="POST" action="">
            <input type="hidden" name="id_stasiun" value="<?= $id_stasiun; ?>">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="fas fa-charging-station me-2"></i>Nama Stasiun *
                    </label>
                    <input 
                        type="text" 
                        name="nama_stasiun" 
                        class="form-control" 
                        value="<?= htmlspecialchars($stasiun['nama_stasiun']); ?>"
                        <?= $isApproved ? 'readonly' : 'required'; ?>
                    >
                    <?php if ($isApproved): ?>
                    <small class="text-muted">Nama stasiun tidak dapat diubah setelah disetujui</small>
                    <?php endif; ?>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">
                        <i class="fas fa-car me-2"></i>Kapasitas Kendaraan *
                    </label>
                    <input 
                        type="number" 
                        name="kapasitas" 
                        class="form-control" 
                        value="<?= $stasiun['kapasitas']; ?>"
                        min="1"
                        required
                    >
                    <?php if ($isApproved): ?>
                    <small class="text-muted">Dapat diubah untuk menyesuaikan operasional</small>
                    <?php endif; ?>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">
                        <i class="fas fa-grip-horizontal me-2"></i>Jumlah Slot *
                    </label>
                    <input 
                        type="number" 
                        name="jumlah_slot" 
                        class="form-control" 
                        value="<?= $stasiun['jumlah_slot']; ?>"
                        min="1"
                        required
                    >
                    <?php if ($isApproved): ?>
                    <small class="text-muted">Dapat diubah untuk menyesuaikan operasional</small>
                    <?php endif; ?>
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label">
                        <i class="fas fa-map-marker-alt me-2"></i>Alamat Lengkap *
                    </label>
                    <textarea 
                        name="alamat" 
                        class="form-control" 
                        rows="3"
                        <?= $isApproved ? 'readonly' : 'required'; ?>
                    ><?= htmlspecialchars($stasiun['alamat']); ?></textarea>
                    <?php if ($isApproved): ?>
                    <small class="text-muted">Alamat tidak dapat diubah setelah disetujui</small>
                    <?php endif; ?>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="fas fa-money-bill-wave me-2"></i>Tarif per kWh (Rp) *
                    </label>
                    <input 
                        type="number" 
                        name="tarif_per_kwh" 
                        class="form-control" 
                        value="<?= $stasiun['tarif_per_kwh']; ?>"
                        step="0.01"
                        min="0"
                        required
                    >
                    <small class="text-muted">Tarif dapat diubah kapan saja</small>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="fas fa-toggle-on me-2"></i>Status Operasional *
                    </label>
                    <?php if (!$isApproved): ?>
                        <input type="text" class="form-control" value="Menunggu Verifikasi" readonly>
                        <input type="hidden" name="status_operasional" value="nonaktif">
                        <small class="text-muted">Status akan diatur setelah disetujui admin</small>
                    <?php else: ?>
                        <select name="status_operasional" class="form-control" required>
                            <option value="aktif" <?= $stasiun['status_operasional'] === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                            <option value="nonaktif" <?= $stasiun['status_operasional'] === 'nonaktif' ? 'selected' : ''; ?>>Non-Aktif</option>
                            <option value="maintenance" <?= $stasiun['status_operasional'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                        </select>
                        <small class="text-muted">Status dapat diubah sesuai kondisi stasiun</small>
                    <?php endif; ?>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="fas fa-clock me-2"></i>Jam Operasional
                    </label>
                    <input 
                        type="text" 
                        name="jam_operasional" 
                        class="form-control" 
                        value="<?= htmlspecialchars($stasiun['jam_operasional'] ?? ''); ?>"
                        placeholder="Contoh: 08:00 - 22:00"
                    >
                    <small class="text-muted">Jam operasional dapat diubah sesuai kebutuhan</small>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="fas fa-wifi me-2"></i>Fasilitas
                    </label>
                    <input 
                        type="text" 
                        name="fasilitas" 
                        class="form-control" 
                        value="<?= htmlspecialchars($stasiun['fasilitas'] ?? ''); ?>"
                        placeholder="Contoh: WiFi gratis, Mushola, Kantin"
                    >
                    <small class="text-muted">Fasilitas dapat diubah sesuai ketersediaan</small>
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label">
                        <i class="fas fa-map-marked-alt me-2"></i>Lokasi di Peta <span class="text-danger">*</span>
                    </label>
                    <div class="map-info">
                        <i class="fas fa-mouse-pointer"></i>
                        <strong><?= $isApproved ? 'Lokasi stasiun yang telah disetujui' : 'Klik pada peta'; ?></strong> 
                        <?= $isApproved ? '(tidak dapat diubah)' : 'untuk memperbarui lokasi stasiun, atau gunakan GPS untuk akurasi maksimal'; ?>
                    </div>
                    
                    <?php if (!$isApproved): ?>
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" id="useCurrentLocation" class="btn btn-success flex-grow-1">
                            <i class="fas fa-crosshairs"></i> Gunakan GPS Saya
                        </button>
                        <button type="button" id="resetMap" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <div id="map"></div>
                    
                    <div id="coordinateDisplay" class="coordinate-display <?= !empty($stasiun['latitude']) && !empty($stasiun['longitude']) ? 'show' : ''; ?>">
                        <span style="color: #b98cff; font-weight: 600;">📍 Koordinat Terpilih:</span>
                        <div class="d-flex gap-3 flex-wrap mt-2">
                            <div>
                                <small class="text-muted d-block">Latitude</small>
                                <span style="font-family: 'Courier New', monospace; font-size: 1.1rem; color: #44d8ff; font-weight: 700;" id="displayLat"><?= $stasiun['latitude'] ?? '-'; ?></span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Longitude</small>
                                <span style="font-family: 'Courier New', monospace; font-size: 1.1rem; color: #44d8ff; font-weight: 700;" id="displayLng"><?= $stasiun['longitude'] ?? '-'; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" id="latitude" name="latitude" value="<?= $stasiun['latitude'] ?? ''; ?>" <?= !$isApproved ? 'required' : ''; ?>>
                    <input type="hidden" id="longitude" name="longitude" value="<?= $stasiun['longitude'] ?? ''; ?>" <?= !$isApproved ? 'required' : ''; ?>>
                    
                    <?php if ($isApproved): ?>
                    <small class="text-muted d-block mt-2">
                        <i class="fas fa-lock me-1"></i>Koordinat tidak dapat diubah setelah stasiun disetujui
                    </small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end mt-4">
                <a href="detail_stasiun.php?id=<?= $id_stasiun; ?>" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Batal
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>
                    <?= $stasiun['status'] === 'ditolak' ? 'Simpan & Ajukan Ulang' : 'Simpan Perubahan'; ?>
                </button>
            </div>
        </form>
    </div>

    <div class="card mt-4" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(147, 51, 234, 0.1)); border: 1px solid rgba(59, 130, 246, 0.3);">
        <div class="card-body">
            <h6><i class="fas fa-info-circle me-2 text-info"></i>Informasi Penting</h6>
            <ul class="mb-0" style="font-size: 0.9rem;">
                <?php if ($isApproved): ?>
                <li>Data stasiun yang sudah disetujui hanya dapat diubah pada bagian <strong>Kapasitas Kendaraan</strong>, <strong>Jumlah Slot</strong>, <strong>Tarif per kWh</strong>, <strong>Status Operasional</strong>, <strong>Jam Operasional</strong>, dan <strong>Fasilitas</strong></li>
                <li>Untuk mengubah data lain (nama, alamat, koordinat), silakan hubungi admin</li>
                <?php elseif ($stasiun['status'] === 'ditolak'): ?>
                <li>Perbaiki data sesuai catatan penolakan dari admin</li>
                <li>Setelah menyimpan, stasiun akan <strong>otomatis diajukan ulang</strong> untuk verifikasi</li>
                <li>Status stasiun akan berubah menjadi "Menunggu Verifikasi"</li>
                <?php else: ?>
                <li>Pastikan semua data yang diisi sudah benar sebelum menyimpan</li>
                <li>Data yang sudah disetujui tidak dapat diubah kecuali bagian operasional tertentu</li>
                <?php endif; ?>
                <li>Koordinat lokasi sangat penting untuk memudahkan pelanggan menemukan stasiun Anda</li>
            </ul>
        </div>
    </div>
</div>

<?php include '../components/bottom-nav-mitra.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script src="../js/clean-url.js"></script>
<script src="../js/mitra-dashboard.js"></script>
<script>
// Data dari PHP
const stasiunData = {
    lat: <?= !empty($stasiun['latitude']) ? $stasiun['latitude'] : 'null'; ?>,
    lng: <?= !empty($stasiun['longitude']) ? $stasiun['longitude'] : 'null'; ?>,
    nama: "<?= htmlspecialchars($stasiun['nama_stasiun']); ?>",
    isApproved: <?= $isApproved ? 'true' : 'false'; ?>
};
</script>
<script src="../js/edit-stasiun-map.js?v=<?= time(); ?>"></script>
</body>
</html>