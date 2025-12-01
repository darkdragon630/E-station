<?php
session_start();
require_once '../config/koneksi.php';
require_once '../pesan/alerts.php';

// Cek authentication admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Fungsi ambil data stasiun dengan filter
function getApprovalStasiunData($koneksi, $search = '', $status_filter = '') {
    try {
        $checkTable = $koneksi->query("SHOW TABLES LIKE 'mitra'")->rowCount();
        
        if ($checkTable > 0) {
            $query = "SELECT sp.*, m.nama_mitra, m.email, m.no_telepon
                      FROM stasiun_pengisian sp
                      LEFT JOIN mitra m ON sp.id_mitra = m.id_mitra
                      WHERE 1=1";
        } else {
            $query = "SELECT sp.*, u.nama as nama_mitra, u.email, u.no_telepon
                      FROM stasiun_pengisian sp
                      LEFT JOIN users u ON sp.id_mitra = u.id
                      WHERE 1=1";
        }
        
        $params = [];

        if (!empty($search)) {
            if ($checkTable > 0) {
                $query .= " AND (sp.id_stasiun LIKE :search1 
                            OR sp.nama_stasiun LIKE :search2 
                            OR m.nama_mitra LIKE :search3)";
            } else {
                $query .= " AND (sp.id_stasiun LIKE :search1 
                            OR sp.nama_stasiun LIKE :search2 
                            OR u.nama LIKE :search3)";
            }
            $search_param = "%$search%";
            $params['search1'] = $search_param;
            $params['search2'] = $search_param;
            $params['search3'] = $search_param;
        }

        if (!empty($status_filter)) {
            $query .= " AND sp.status = :status";
            $params['status'] = $status_filter;
        }

        $query .= " ORDER BY sp.created_at DESC";

        $stmt = $koneksi->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching stasiun data: " . $e->getMessage());
        return [];
    }
}

// Fungsi statistik
function getApprovalStasiunStatistic($koneksi) {
    try {
        $stats = [
            'total' => 0,
            'pending' => 0,
            'disetujui' => 0,
            'ditolak' => 0
        ];

        $stmt = $koneksi->query("SELECT COUNT(*) FROM stasiun_pengisian");
        $stats['total'] = $stmt->fetchColumn();

        $stmt = $koneksi->query("SELECT COUNT(*) FROM stasiun_pengisian WHERE status = 'pending'");
        $stats['pending'] = $stmt->fetchColumn();

        $stmt = $koneksi->query("SELECT COUNT(*) FROM stasiun_pengisian WHERE status = 'disetujui'");
        $stats['disetujui'] = $stmt->fetchColumn();

        $stmt = $koneksi->query("SELECT COUNT(*) FROM stasiun_pengisian WHERE status = 'ditolak'");
        $stats['ditolak'] = $stmt->fetchColumn();

        return $stats;
    } catch (PDOException $e) {
        error_log("Error fetching stats: " . $e->getMessage());
        return ['total' => 0, 'pending' => 0, 'disetujui' => 0, 'ditolak' => 0];
    }
}

function formatTanggal($datetime, $format = 'd M Y') {
    return date($format, strtotime($datetime));
}

function truncateText($text, $length = 30) {
    $text = $text ?? '-';
    return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
}

// Ambil data dengan filter
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$stasiun_list = getApprovalStasiunData($koneksi, $search, $status_filter);
$stats = getApprovalStasiunStatistic($koneksi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Stasiun</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin-style.css">
    <link rel="stylesheet" href="../css/alert.css">

    <style>
        .map-preview {
            height: 300px;
            border-radius: 10px;
            margin-bottom: 15px;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .station-detail-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .detail-item {
            display: flex;
            align-items: start;
            gap: 10px;
        }

        .detail-item i {
            color: #6366f1;
            font-size: 1.2rem;
            min-width: 25px;
            margin-top: 3px;
        }

        .detail-item .label {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 2px;
        }

        .detail-item .value {
            font-weight: 600;
            color: #212529;
        }

        .mitra-info-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-approve {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            flex: 1;
            min-width: 150px;
        }

        .btn-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(34, 197, 94, 0.3);
        }

        .btn-reject {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            flex: 1;
            min-width: 150px;
        }

        .btn-reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
        }

        @media (max-width: 768px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-approve, .btn-reject {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <?php tampilkan_alert(); ?>
    
    <!-- Page Header -->
    <div class="top-bar">
        <h5><i class="fas fa-clipboard-check me-2"></i>Approval Stasiun Pengisian</h5>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['pending']); ?></h3>
                    <p>Menunggu Verifikasi</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['disetujui']); ?></h3>
                    <p>Disetujui</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['ditolak']); ?></h3>
                    <p>Ditolak</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-charging-station"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total']); ?></h3>
                    <p>Total Stasiun</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter & Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <input 
                        type="text" 
                        name="search" 
                        class="form-control" 
                        placeholder="Cari nama stasiun, ID, atau nama mitra..." 
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Menunggu</option>
                        <option value="disetujui" <?php echo $status_filter === 'disetujui' ? 'selected' : ''; ?>>Disetujui</option>
                        <option value="ditolak" <?php echo $status_filter === 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Cari
                    </button>
                    <a href="approval_stasiun.php" class="btn btn-secondary">
                        <i class="fas fa-redo me-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Station List -->
    <?php if (empty($stasiun_list)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Tidak Ada Data</h5>
                <p class="text-muted">
                    <?php if (!empty($search) || !empty($status_filter)): ?>
                        Tidak ditemukan stasiun dengan kriteria pencarian tersebut.
                    <?php else: ?>
                        Saat ini tidak ada pengajuan stasiun yang perlu diverifikasi.
                    <?php endif; ?>
                </p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($stasiun_list as $stasiun): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="mb-1">
                                <i class="fas fa-charging-station me-2 text-primary"></i>
                                <?php echo htmlspecialchars($stasiun['nama_stasiun']); ?>
                            </h5>
                            <small class="text-muted">ID: <?php echo $stasiun['id_stasiun']; ?></small>
                        </div>
                        <?php
                        $status = $stasiun['status'] ?? 'pending';
                        $badgeClass = 'warning';
                        $badgeIcon = 'clock';
                        $badgeText = 'Menunggu';
                        
                        if ($status === 'disetujui') {
                            $badgeClass = 'success';
                            $badgeIcon = 'check-circle';
                            $badgeText = 'Disetujui';
                        } elseif ($status === 'ditolak') {
                            $badgeClass = 'danger';
                            $badgeIcon = 'times-circle';
                            $badgeText = 'Ditolak';
                        }
                        ?>
                        <span class="badge bg-<?php echo $badgeClass; ?> px-3 py-2">
                            <i class="fas fa-<?php echo $badgeIcon; ?> me-1"></i><?php echo $badgeText; ?>
                        </span>
                    </div>

                    <!-- Mitra Info -->
                    <div class="mitra-info-box">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="fas fa-user"></i>
                            <strong>Mitra:</strong> <?php echo htmlspecialchars($stasiun['nama_mitra'] ?? 'N/A'); ?>
                        </div>
                        <?php if (!empty($stasiun['email'])): ?>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="fas fa-envelope"></i>
                            <?php echo htmlspecialchars($stasiun['email']); ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($stasiun['no_telepon'])): ?>
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-phone"></i>
                            <?php echo htmlspecialchars($stasiun['no_telepon']); ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Station Details -->
                    <div class="station-detail-card">
                        <div class="detail-grid">
                            <div class="detail-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <div>
                                    <div class="label">Alamat</div>
                                    <div class="value"><?php echo htmlspecialchars($stasiun['alamat']); ?></div>
                                </div>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-globe"></i>
                                <div>
                                    <div class="label">Koordinat</div>
                                    <div class="value"><?php echo $stasiun['latitude']; ?>, <?php echo $stasiun['longitude']; ?></div>
                                </div>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-plug"></i>
                                <div>
                                    <div class="label">Kapasitas</div>
                                    <div class="value"><?php echo $stasiun['kapasitas']; ?> Unit</div>
                                </div>
                            </div>
                            
                            <?php if (isset($stasiun['jumlah_slot'])): ?>
                            <div class="detail-item">
                                <i class="fas fa-parking"></i>
                                <div>
                                    <div class="label">Slot Parkir</div>
                                    <div class="value"><?php echo $stasiun['jumlah_slot']; ?> Slot</div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($stasiun['tarif_per_kwh'])): ?>
                            <div class="detail-item">
                                <i class="fas fa-money-bill-wave"></i>
                                <div>
                                    <div class="label">Tarif per kWh</div>
                                    <div class="value">Rp <?php echo number_format($stasiun['tarif_per_kwh'], 0, ',', '.'); ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($stasiun['jam_operasional'])): ?>
                            <div class="detail-item">
                                <i class="fas fa-clock"></i>
                                <div>
                                    <div class="label">Jam Operasional</div>
                                    <div class="value"><?php echo htmlspecialchars($stasiun['jam_operasional']); ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($stasiun['fasilitas'])): ?>
                        <div class="detail-item">
                            <i class="fas fa-star"></i>
                            <div>
                                <div class="label">Fasilitas</div>
                                <div class="value"><?php echo htmlspecialchars($stasiun['fasilitas']); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Map Preview -->
                    <div class="map-preview" id="map_<?php echo $stasiun['id_stasiun']; ?>" 
                         data-lat="<?php echo $stasiun['latitude']; ?>" 
                         data-lng="<?php echo $stasiun['longitude']; ?>"
                         data-name="<?php echo htmlspecialchars($stasiun['nama_stasiun']); ?>">
                    </div>

                    <!-- Action Buttons - Only show for pending status -->
                    <?php if ($status === 'pending'): ?>
                    <div class="action-buttons">
                        <button type="button" class="btn btn-approve" 
                                onclick="approveStation(<?php echo $stasiun['id_stasiun']; ?>, '<?php echo htmlspecialchars($stasiun['nama_stasiun']); ?>')">
                            <i class="fas fa-check-circle me-2"></i>Setujui & Aktifkan
                        </button>
                        <button type="button" class="btn btn-reject" 
                                onclick="rejectStation(<?php echo $stasiun['id_stasiun']; ?>, '<?php echo htmlspecialchars($stasiun['nama_stasiun']); ?>')">
                            <i class="fas fa-times-circle me-2"></i>Tolak Pengajuan
                        </button>
                    </div>
                    <?php elseif ($status === 'ditolak' && !empty($stasiun['alasan_penolakan'])): ?>
                    <div class="alert alert-danger mb-0">
                        <strong><i class="fas fa-info-circle me-2"></i>Alasan Penolakan:</strong><br>
                        <?php echo htmlspecialchars($stasiun['alasan_penolakan']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="../js/clean-url.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

<script>
// Initialize all maps
document.addEventListener('DOMContentLoaded', function() {
    const mapElements = document.querySelectorAll('.map-preview');
    
    mapElements.forEach(mapEl => {
        const lat = parseFloat(mapEl.dataset.lat);
        const lng = parseFloat(mapEl.dataset.lng);
        const name = mapEl.dataset.name;
        
        const map = L.map(mapEl.id).setView([lat, lng], 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);
        
        const icon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
        
        L.marker([lat, lng], {icon: icon})
            .addTo(map)
            .bindPopup(`<b>${name}</b><br>Lat: ${lat}<br>Lng: ${lng}`)
            .openPopup();
    });
});

// Approve Station
function approveStation(id, name) {
    if (confirm(`✅ Setujui dan aktifkan stasiun "${name}"?\n\nStasiun akan langsung aktif dan dapat digunakan oleh pengendara.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'process_approval_stasiun.php';
        
        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'id_stasiun';
        inputId.value = id;
        
        const inputAction = document.createElement('input');
        inputAction.type = 'hidden';
        inputAction.name = 'action';
        inputAction.value = 'approve';
        
        form.appendChild(inputId);
        form.appendChild(inputAction);
        document.body.appendChild(form);
        form.submit();
    }
}

// Reject Station
function rejectStation(id, name) {
    const reason = prompt(`❌ Tolak pengajuan stasiun "${name}"?\n\nMasukkan alasan penolakan (akan dikirim ke mitra):`);
    
    if (reason && reason.trim() !== '') {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'process_approval_stasiun.php';
        
        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'id_stasiun';
        inputId.value = id;
        
        const inputAction = document.createElement('input');
        inputAction.type = 'hidden';
        inputAction.name = 'action';
        inputAction.value = 'reject';
        
        const inputReason = document.createElement('input');
        inputReason.type = 'hidden';
        inputReason.name = 'reason';
        inputReason.value = reason;
        
        form.appendChild(inputId);
        form.appendChild(inputAction);
        form.appendChild(inputReason);
        document.body.appendChild(form);
        form.submit();
    } else if (reason !== null) {
        alert('⚠️ Alasan penolakan harus diisi!');
    }
}
</script>

</body>
</html>