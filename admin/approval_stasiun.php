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
        $query = "SELECT sp.*, m.nama_mitra, m.email as email_mitra, m.no_telepon as telepon_mitra
                  FROM stasiun_pengisian sp
                  LEFT JOIN mitra m ON sp.id_mitra = m.id_mitra
                  WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (sp.id_stasiun LIKE :search1 
                        OR sp.nama_stasiun LIKE :search2 
                        OR m.nama_mitra LIKE :search3)";
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

// Ambil data dengan filter
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$stasiun_pending = getApprovalStasiunData($koneksi, $search, $status_filter);
$stats = getApprovalStasiunStatistic($koneksi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a192f">
    <title>Approval Stasiun - E-Station Admin</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin-style.css">
    <link rel="stylesheet" href="../css/alert.css">

    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .stat-card .icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .stat-card .label {
            color: var(--muted);
            font-size: 0.9rem;
        }

        .stat-card.pending { border-left: 4px solid #fbbf24; }
        .stat-card.pending .icon { color: #fbbf24; }

        .stat-card.aktif { border-left: 4px solid #22c55e; }
        .stat-card.aktif .icon { color: #22c55e; }

        .stat-card.ditolak { border-left: 4px solid #ef4444; }
        .stat-card.ditolak .icon { color: #ef4444; }

        .station-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .station-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border-color: rgba(96, 165, 250, 0.5);
        }

        .station-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .station-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #60a5fa;
            margin-bottom: 5px;
        }

        .mitra-info {
            background: rgba(185, 140, 255, 0.15);
            padding: 8px 15px;
            border-radius: 10px;
            font-size: 0.9rem;
        }

        .station-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .detail-item i {
            color: #60a5fa;
            font-size: 1.2rem;
            min-width: 25px;
        }

        .detail-item .label {
            font-size: 0.85rem;
            color: var(--muted);
            margin-bottom: 2px;
        }

        .detail-item .value {
            font-weight: 600;
            font-size: 1rem;
        }

        .map-preview {
            height: 300px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 2px solid rgba(96, 165, 250, 0.3);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-approve {
            background: linear-gradient(135deg, #22c55e, #4ade80);
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            flex: 1;
            min-width: 150px;
        }

        .btn-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(34, 197, 94, 0.4);
        }

        .btn-reject {
            background: linear-gradient(135deg, #ef4444, #f87171);
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            flex: 1;
            min-width: 150px;
        }

        .btn-reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            border: 2px dashed rgba(255, 255, 255, 0.2);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--muted);
            margin-bottom: 20px;
        }

        .empty-state h4 {
            color: var(--muted);
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .station-details {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-approve, .btn-reject {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="theme-toggle">
    <button id="toggleTheme">🌙</button>
</div>

<?php include '../components/navbar-admin.php'; ?>

<div class="mobile-header d-md-none">
    <div class="header-top">
        <div class="logo"><i class="fas fa-shield-alt"></i> Admin Panel</div>
        <div class="header-actions">
            <button id="mobileThemeToggle">🌙</button>
        </div>
    </div>
    <div class="welcome-text">
        <h2>✅ Approval Stasiun</h2>
        <p>Verifikasi pengajuan stasiun baru</p>
    </div>
</div>

<div class="container mt-4 mb-5">
    <?php tampilkan_alert(); ?>

    <h2 class="mb-4 d-none d-md-block">
        <i class="fas fa-clipboard-check me-2"></i>Approval Stasiun Pengisian
    </h2>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card pending">
            <div class="icon"><i class="fas fa-clock"></i></div>
            <div class="value"><?= $stats['pending'] ?></div>
            <div class="label">Menunggu Approval</div>
        </div>
        <div class="stat-card aktif">
            <div class="icon"><i class="fas fa-check-circle"></i></div>
            <div class="value"><?= $stats['aktif'] ?></div>
            <div class="label">Stasiun Aktif</div>
        </div>
        <div class="stat-card ditolak">
            <div class="icon"><i class="fas fa-times-circle"></i></div>
            <div class="value"><?= $stats['ditolak'] ?></div>
            <div class="label">Ditolak</div>
        </div>
    </div>

    <!-- Pending Stations -->
    <?php if (empty($stasiun_pending)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h4>Tidak Ada Pengajuan</h4>
            <p class="text-muted">Saat ini tidak ada pengajuan stasiun yang perlu diverifikasi.</p>
        </div>
    <?php else: ?>
        <?php foreach ($stasiun_pending as $stasiun): ?>
            <div class="station-card">
                <div class="station-header">
                    <div>
                        <h3 class="station-title">
                            <i class="fas fa-charging-station me-2"></i>
                            <?= htmlspecialchars($stasiun['nama_stasiun']) ?>
                        </h3>
                        <div class="mitra-info">
                            <i class="fas fa-user me-2"></i>
                            <strong>Mitra:</strong> <?= htmlspecialchars($stasiun['nama_mitra']) ?>
                            <span class="ms-3"><i class="fas fa-envelope me-1"></i><?= htmlspecialchars($stasiun['email']) ?></span>
                        </div>
                    </div>
                    <span class="badge bg-warning text-dark px-3 py-2" style="font-size: 0.9rem;">
                        <i class="fas fa-clock me-1"></i>Pending
                    </span>
                </div>

                <div class="station-details">
                    <div class="detail-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <div class="label">Alamat</div>
                            <div class="value"><?= htmlspecialchars($stasiun['alamat']) ?></div>
                        </div>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-globe"></i>
                        <div>
                            <div class="label">Koordinat</div>
                            <div class="value"><?= $stasiun['latitude'] ?>, <?= $stasiun['longitude'] ?></div>
                        </div>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-plug"></i>
                        <div>
                            <div class="label">Kapasitas</div>
                            <div class="value"><?= $stasiun['kapasitas'] ?> Unit</div>
                        </div>
                    </div>
                    
                    <?php 
                    // Check if additional columns exist
                    $hasExtraColumns = isset($stasiun['jumlah_slot']) || isset($stasiun['tarif_per_kwh']) || isset($stasiun['jam_operasional']);
                    
                    if ($hasExtraColumns):
                    ?>
                        <?php if (isset($stasiun['jumlah_slot'])): ?>
                        <div class="detail-item">
                            <i class="fas fa-parking"></i>
                            <div>
                                <div class="label">Slot Parkir</div>
                                <div class="value"><?= $stasiun['jumlah_slot'] ?> Slot</div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($stasiun['tarif_per_kwh'])): ?>
                        <div class="detail-item">
                            <i class="fas fa-money-bill-wave"></i>
                            <div>
                                <div class="label">Tarif per kWh</div>
                                <div class="value">Rp <?= number_format($stasiun['tarif_per_kwh'], 0, ',', '.') ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($stasiun['jam_operasional'])): ?>
                        <div class="detail-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <div class="label">Jam Operasional</div>
                                <div class="value"><?= htmlspecialchars($stasiun['jam_operasional']) ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <?php if (!empty($stasiun['fasilitas']) && isset($stasiun['fasilitas'])): ?>
                    <div class="detail-item mb-3">
                        <i class="fas fa-star"></i>
                        <div>
                            <div class="label">Fasilitas</div>
                            <div class="value"><?= htmlspecialchars($stasiun['fasilitas']) ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Map Preview -->
                <div class="map-preview" id="map_<?= $stasiun['id_stasiun'] ?>" 
                     data-lat="<?= $stasiun['latitude'] ?>" 
                     data-lng="<?= $stasiun['longitude'] ?>"
                     data-name="<?= htmlspecialchars($stasiun['nama_stasiun']) ?>">
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button type="button" class="btn-approve" 
                            onclick="approveStation(<?= $stasiun['id_stasiun'] ?>, '<?= htmlspecialchars($stasiun['nama_stasiun']) ?>')">
                        <i class="fas fa-check-circle me-2"></i>Setujui & Aktifkan
                    </button>
                    <button type="button" class="btn-reject" 
                            onclick="rejectStation(<?= $stasiun['id_stasiun'] ?>, '<?= htmlspecialchars($stasiun['nama_stasiun']) ?>')">
                        <i class="fas fa-times-circle me-2"></i>Tolak Pengajuan
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include '../components/bottom-nav-admin.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

<script>
// Theme Toggle
function initTheme(btnId) {
    const btn = document.getElementById(btnId);
    if (!btn) return;
    
    const saved = localStorage.getItem("theme");
    if (saved === "light") {
        document.body.classList.add("light");
        btn.textContent = "☀️";
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