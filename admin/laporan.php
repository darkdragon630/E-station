<?php
session_start();
require_once "../config/koneksi.php";
require_once "../pesan/alerts.php";

// Cek authentication admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php?error=unauthorized");
    exit();
}

// ==================== DATA RETRIEVAL ====================

// Get current filters
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$tipe_laporan = isset($_GET['tipe']) ? $_GET['tipe'] : 'semua';

// Get available years
function getAvailableYears($koneksi) {
    try {
        $stmt = $koneksi->query("SELECT DISTINCT YEAR(created_at) as tahun FROM pengendara 
                                UNION 
                                SELECT DISTINCT YEAR(created_at) as tahun FROM mitra 
                                ORDER BY tahun DESC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        return [date('Y')];
    }
}

// Get total registered users statistics
function getTotalUserStats($koneksi) {
    try {
        $query = "SELECT 
                    (SELECT COUNT(*) FROM pengendara) as total_pengendara,
                    (SELECT COUNT(*) FROM pengendara WHERE status_akun = 'aktif') as pengendara_aktif,
                    (SELECT COUNT(*) FROM pengendara WHERE status_akun = 'nonaktif') as pengendara_nonaktif,
                    (SELECT COUNT(*) FROM mitra) as total_mitra,
                    (SELECT COUNT(*) FROM mitra WHERE status = 'disetujui') as mitra_disetujui,
                    (SELECT COUNT(*) FROM mitra WHERE status = 'pending') as mitra_pending,
                    (SELECT COUNT(*) FROM mitra WHERE status = 'ditolak') as mitra_ditolak";
        
        $stmt = $koneksi->query($query);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching total user stats: " . $e->getMessage());
        return [];
    }
}

// Get monthly registration statistics
function getMonthlyRegistrations($koneksi, $year, $month, $tipe = 'semua') {
    try {
        $stats = [];
        
        if ($tipe == 'semua' || $tipe == 'pengendara') {
            // Pengendara registrations
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status_akun = 'aktif' THEN 1 ELSE 0 END) as aktif,
                        SUM(CASE WHEN status_akun = 'nonaktif' THEN 1 ELSE 0 END) as nonaktif
                      FROM pengendara 
                      WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month";
            
            $stmt = $koneksi->prepare($query);
            $stmt->execute(['year' => $year, 'month' => $month]);
            $stats['pengendara'] = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if ($tipe == 'semua' || $tipe == 'mitra') {
            // Mitra registrations
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'disetujui' THEN 1 ELSE 0 END) as disetujui,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak
                      FROM mitra 
                      WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month";
            
            $stmt = $koneksi->prepare($query);
            $stmt->execute(['year' => $year, 'month' => $month]);
            $stats['mitra'] = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return $stats;
    } catch (PDOException $e) {
        error_log("Error fetching monthly registrations: " . $e->getMessage());
        return [];
    }
}

// Get yearly registration data for chart
function getYearlyRegistrations($koneksi, $year, $tipe = 'semua') {
    try {
        $data = [];
        
        if ($tipe == 'semua' || $tipe == 'pengendara') {
            $query = "SELECT 
                        MONTH(created_at) as bulan,
                        COUNT(*) as total
                      FROM pengendara 
                      WHERE YEAR(created_at) = :year
                      GROUP BY MONTH(created_at)
                      ORDER BY bulan";
            
            $stmt = $koneksi->prepare($query);
            $stmt->execute(['year' => $year]);
            $data['pengendara'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if ($tipe == 'semua' || $tipe == 'mitra') {
            $query = "SELECT 
                        MONTH(created_at) as bulan,
                        COUNT(*) as total
                      FROM mitra 
                      WHERE YEAR(created_at) = :year
                      GROUP BY MONTH(created_at)
                      ORDER BY bulan";
            
            $stmt = $koneksi->prepare($query);
            $stmt->execute(['year' => $year]);
            $data['mitra'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $data;
    } catch (PDOException $e) {
        error_log("Error fetching yearly registrations: " . $e->getMessage());
        return [];
    }
}

// Get recent registrations
function getRecentRegistrations($koneksi, $year, $month, $tipe = 'semua', $limit = 10) {
    try {
        $data = [];
        
        if ($tipe == 'semua' || $tipe == 'pengendara') {
            $query = "SELECT 
                        'Pengendara' as user_type,
                        nama,
                        email,
                        no_telepon,
                        status_akun as status,
                        created_at
                      FROM pengendara 
                      WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month
                      ORDER BY created_at DESC
                      LIMIT :limit";
            
            $stmt = $koneksi->prepare($query);
            $stmt->bindValue(':year', $year, PDO::PARAM_INT);
            $stmt->bindValue(':month', $month, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $data = array_merge($data, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        
        if ($tipe == 'semua' || $tipe == 'mitra') {
            $query = "SELECT 
                        'Mitra' as user_type,
                        nama_mitra as nama,
                        email,
                        no_telepon,
                        status,
                        created_at
                      FROM mitra 
                      WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month
                      ORDER BY created_at DESC
                      LIMIT :limit";
            
            $stmt = $koneksi->prepare($query);
            $stmt->bindValue(':year', $year, PDO::PARAM_INT);
            $stmt->bindValue(':month', $month, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $data = array_merge($data, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        
        // Sort by created_at
        usort($data, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return array_slice($data, 0, $limit);
    } catch (PDOException $e) {
        error_log("Error fetching recent registrations: " . $e->getMessage());
        return [];
    }
}

// ==================== MAIN EXECUTION ====================
$available_years = getAvailableYears($koneksi);
$total_stats = getTotalUserStats($koneksi);
$monthly_stats = getMonthlyRegistrations($koneksi, $selected_year, $selected_month, $tipe_laporan);
$yearly_data = getYearlyRegistrations($koneksi, $selected_year, $tipe_laporan);
$recent_registrations = getRecentRegistrations($koneksi, $selected_year, $selected_month, $tipe_laporan, 15);

$month_names = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Registrasi Pengguna</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin-style.css">
    <link rel="stylesheet" href="../css/alert.css">
    <link rel="icon" type="image/png" href="../images/Logo_1.png">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <?php tampilkan_alert(); ?>
    
    <!-- Page Header -->
    <div class="top-bar">
        <h5><i class="fas fa-file-alt me-2"></i>Laporan Registrasi Pengguna</h5>
    </div>
    
    <!-- Period Selector -->
    <div class="card mb-4" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); border: 2px solid rgba(102, 126, 234, 0.3);">
        <div class="card-body">
            <h6 class="mb-3"><i class="fas fa-filter me-2"></i>Filter Periode & Tipe Laporan</h6>
            
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tahun</label>
                    <select name="year" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($available_years as $year): ?>
                            <option value="<?= $year ?>" <?= $year == $selected_year ? 'selected' : '' ?>>
                                <?= $year ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bulan</label>
                    <select name="month" class="form-select" onchange="this.form.submit()">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $m == $selected_month ? 'selected' : '' ?>>
                                <?= $month_names[$m] ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipe Pengguna</label>
                    <select name="tipe" class="form-select" onchange="this.form.submit()">
                        <option value="semua" <?= $tipe_laporan == 'semua' ? 'selected' : '' ?>>Semua</option>
                        <option value="pengendara" <?= $tipe_laporan == 'pengendara' ? 'selected' : '' ?>>Pengendara</option>
                        <option value="mitra" <?= $tipe_laporan == 'mitra' ? 'selected' : '' ?>>Mitra</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-success w-100" onclick="exportLaporan()">
                        <i class="fas fa-file-excel me-2"></i>Export Excel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Total Statistics -->
    <div class="row mb-4">
        <div class="col-12">
            <h6 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Total Keseluruhan Pengguna</h6>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($total_stats['total_pengendara']) ?></h3>
                    <p>Total Pengendara</p>
                    <small class="text-success">Aktif: <?= $total_stats['pengendara_aktif'] ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($total_stats['total_mitra']) ?></h3>
                    <p>Total Mitra</p>
                    <small class="text-success">Disetujui: <?= $total_stats['mitra_disetujui'] ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($total_stats['mitra_pending']) ?></h3>
                    <p>Mitra Pending</p>
                    <small class="text-muted">Menunggu Persetujuan</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($total_stats['total_pengendara'] + $total_stats['total_mitra']) ?></h3>
                    <p>Total Semua Pengguna</p>
                    <small class="text-muted">Pengendara + Mitra</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Monthly Statistics -->
    <div class="row mb-4">
        <div class="col-12">
            <h6 class="mb-3">
                <i class="fas fa-calendar-alt me-2"></i>
                Registrasi Bulan <?= $month_names[$selected_month] ?> <?= $selected_year ?>
            </h6>
        </div>
        
        <?php if ($tipe_laporan == 'semua' || $tipe_laporan == 'pengendara'): ?>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fas fa-motorcycle me-2"></i>Pengendara Baru</h6>
                    <div class="row text-center">
                        <div class="col-4">
                            <h3 class="text-primary"><?= $monthly_stats['pengendara']['total'] ?? 0 ?></h3>
                            <small class="text-muted">Total</small>
                        </div>
                        <div class="col-4">
                            <h3 class="text-success"><?= $monthly_stats['pengendara']['aktif'] ?? 0 ?></h3>
                            <small class="text-muted">Aktif</small>
                        </div>
                        <div class="col-4">
                            <h3 class="text-secondary"><?= $monthly_stats['pengendara']['nonaktif'] ?? 0 ?></h3>
                            <small class="text-muted">Non-aktif</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($tipe_laporan == 'semua' || $tipe_laporan == 'mitra'): ?>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fas fa-handshake me-2"></i>Mitra Baru</h6>
                    <div class="row text-center">
                        <div class="col-3">
                            <h3 class="text-primary"><?= $monthly_stats['mitra']['total'] ?? 0 ?></h3>
                            <small class="text-muted">Total</small>
                        </div>
                        <div class="col-3">
                            <h3 class="text-success"><?= $monthly_stats['mitra']['disetujui'] ?? 0 ?></h3>
                            <small class="text-muted">Disetujui</small>
                        </div>
                        <div class="col-3">
                            <h3 class="text-warning"><?= $monthly_stats['mitra']['pending'] ?? 0 ?></h3>
                            <small class="text-muted">Pending</small>
                        </div>
                        <div class="col-3">
                            <h3 class="text-danger"><?= $monthly_stats['mitra']['ditolak'] ?? 0 ?></h3>
                            <small class="text-muted">Ditolak</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Yearly Chart -->
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="mb-3"><i class="fas fa-chart-line me-2"></i>Grafik Registrasi Tahun <?= $selected_year ?></h6>
            <div style="height: 300px;">
                <canvas id="yearlyChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Recent Registrations Table -->
    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">
                <i class="fas fa-list me-2"></i>
                Registrasi Terbaru - <?= $month_names[$selected_month] ?> <?= $selected_year ?>
            </h6>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tipe</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>No. Telepon</th>
                            <th>Status</th>
                            <th>Tanggal Daftar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_registrations)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Tidak ada data registrasi untuk periode ini
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_registrations as $index => $user): 
                                $badge_class = '';
                                $status_text = '';
                                
                                if ($user['user_type'] == 'Pengendara') {
                                    $badge_class = $user['status'] == 'aktif' ? 'bg-success' : 'bg-secondary';
                                    $status_text = ucfirst($user['status']);
                                } else {
                                    if ($user['status'] == 'disetujui') {
                                        $badge_class = 'bg-success';
                                        $status_text = 'Disetujui';
                                    } elseif ($user['status'] == 'pending') {
                                        $badge_class = 'bg-warning';
                                        $status_text = 'Pending';
                                    } else {
                                        $badge_class = 'bg-danger';
                                        $status_text = 'Ditolak';
                                    }
                                }
                            ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <span class="badge <?= $user['user_type'] == 'Pengendara' ? 'bg-primary' : 'bg-info' ?>">
                                            <?= $user['user_type'] ?>
                                        </span>
                                    </td>
                                    <td><strong><?= htmlspecialchars($user['nama']) ?></strong></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['no_telepon'] ?? '-') ?></td>
                                    <td><span class="badge <?= $badge_class ?>"><?= $status_text ?></span></td>
                                    <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="../js/clean-url.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Yearly Registration Chart
const yearlyData = <?= json_encode($yearly_data) ?>;
const monthNames = <?= json_encode($month_names) ?>;
const tipeFilter = '<?= $tipe_laporan ?>';

// Prepare data for all 12 months
const allMonths = Array.from({length: 12}, (_, i) => i + 1);
const chartLabels = allMonths.map(m => monthNames[m]);

let datasets = [];

if (tipeFilter === 'semua' || tipeFilter === 'pengendara') {
    const pengendaraData = allMonths.map(month => {
        const found = yearlyData.pengendara?.find(d => d.bulan == month);
        return found ? found.total : 0;
    });
    
    datasets.push({
        label: 'Pengendara',
        data: pengendaraData,
        backgroundColor: 'rgba(54, 162, 235, 0.6)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 2,
        borderRadius: 6
    });
}

if (tipeFilter === 'semua' || tipeFilter === 'mitra') {
    const mitraData = allMonths.map(month => {
        const found = yearlyData.mitra?.find(d => d.bulan == month);
        return found ? found.total : 0;
    });
    
    datasets.push({
        label: 'Mitra',
        data: mitraData,
        backgroundColor: 'rgba(75, 192, 192, 0.6)',
        borderColor: 'rgba(75, 192, 192, 1)',
        borderWidth: 2,
        borderRadius: 6
    });
}

const ctx = document.getElementById('yearlyChart');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 2.5,
            plugins: {
                legend: {
                    display: datasets.length > 1,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y + ' registrasi';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        callback: function(value) {
                            return value + ' user';
                        }
                    }
                }
            }
        }
    });
}

// Export to Excel function
function exportLaporan() {
    const year = <?= $selected_year ?>;
    const month = <?= $selected_month ?>;
    const tipe = '<?= $tipe_laporan ?>';
    
    alert('Fitur export Excel akan segera tersedia!\n\nData yang akan diexport:\n- Tahun: ' + year + '\n- Bulan: ' + monthNames[month] + '\n- Tipe: ' + tipe);
}
</script>

</body>
</html>