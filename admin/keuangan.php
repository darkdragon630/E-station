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
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : 0;
$mitra_filter = isset($_GET['mitra']) ? intval($_GET['mitra']) : 0;

// Get available years
function getAvailableYears($koneksi) {
    try {
        $stmt = $koneksi->query("SELECT DISTINCT YEAR(tanggal_transaksi) as tahun FROM transaksi ORDER BY tahun DESC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        return [date('Y')];
    }
}

// Get financial statistics
function getFinancialStatistics($koneksi, $year, $month = 0, $mitra_id = 0) {
    try {
        $where = "YEAR(t.tanggal_transaksi) = :year AND t.status_transaksi = 'berhasil'";
        $params = ['year' => $year];
        
        if ($month > 0) {
            $where .= " AND MONTH(t.tanggal_transaksi) = :month";
            $params['month'] = $month;
        }
        
        if ($mitra_id > 0) {
            $where .= " AND sp.id_mitra = :mitra";
            $params['mitra'] = $mitra_id;
        }
        
        $query = "SELECT 
                    COUNT(*) as total_transaksi,
                    SUM(t.total_harga) as total_pendapatan,
                    SUM(t.jumlah_kwh) as total_kwh,
                    SUM(CASE WHEN t.baterai_terpakai > 0 THEN CEIL(t.baterai_terpakai / 100) ELSE 0 END) as total_baterai,
                    AVG(t.total_harga) as avg_transaksi,
                    MAX(t.total_harga) as max_transaksi,
                    MIN(t.total_harga) as min_transaksi
                  FROM transaksi t
                  INNER JOIN stasiun_pengisian sp ON t.id_stasiun = sp.id_stasiun
                  WHERE {$where}";
        
        $stmt = $koneksi->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching financial statistics: " . $e->getMessage());
        return [
            'total_transaksi' => 0,
            'total_pendapatan' => 0,
            'total_kwh' => 0,
            'total_baterai' => 0,
            'avg_transaksi' => 0,
            'max_transaksi' => 0,
            'min_transaksi' => 0
        ];
    }
}

// Get monthly revenue data
function getMonthlyRevenue($koneksi, $year, $mitra_id = 0) {
    try {
        $where = "YEAR(t.tanggal_transaksi) = :year AND t.status_transaksi = 'berhasil'";
        $params = ['year' => $year];
        
        if ($mitra_id > 0) {
            $where .= " AND sp.id_mitra = :mitra";
            $params['mitra'] = $mitra_id;
        }
        
        $query = "SELECT 
                    MONTH(t.tanggal_transaksi) as bulan,
                    COUNT(*) as total_transaksi,
                    SUM(t.total_harga) as total_pendapatan,
                    SUM(t.jumlah_kwh) as total_kwh
                  FROM transaksi t
                  INNER JOIN stasiun_pengisian sp ON t.id_stasiun = sp.id_stasiun
                  WHERE {$where}
                  GROUP BY MONTH(t.tanggal_transaksi)
                  ORDER BY bulan";
        
        $stmt = $koneksi->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching monthly revenue: " . $e->getMessage());
        return [];
    }
}

// Get revenue by mitra
function getRevenueByMitra($koneksi, $year, $month = 0) {
    try {
        $where = "YEAR(t.tanggal_transaksi) = :year AND t.status_transaksi = 'berhasil'";
        $params = ['year' => $year];
        
        if ($month > 0) {
            $where .= " AND MONTH(t.tanggal_transaksi) = :month";
            $params['month'] = $month;
        }
        
        $query = "SELECT 
                    m.id_mitra,
                    m.nama_mitra,
                    m.email,
                    COUNT(t.id_transaksi) as total_transaksi,
                    SUM(t.total_harga) as total_pendapatan,
                    SUM(t.jumlah_kwh) as total_kwh,
                    COUNT(DISTINCT sp.id_stasiun) as jumlah_stasiun
                  FROM mitra m
                  LEFT JOIN stasiun_pengisian sp ON m.id_mitra = sp.id_mitra
                  LEFT JOIN transaksi t ON sp.id_stasiun = t.id_stasiun AND {$where}
                  GROUP BY m.id_mitra
                  HAVING total_pendapatan > 0
                  ORDER BY total_pendapatan DESC";
        
        $stmt = $koneksi->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching revenue by mitra: " . $e->getMessage());
        return [];
    }
}

// Get top stations
function getTopStations($koneksi, $year, $month = 0, $limit = 5) {
    try {
        $where = "YEAR(t.tanggal_transaksi) = :year AND t.status_transaksi = 'berhasil'";
        $params = ['year' => $year];
        
        if ($month > 0) {
            $where .= " AND MONTH(t.tanggal_transaksi) = :month";
            $params['month'] = $month;
        }
        
        $query = "SELECT 
                    sp.nama_stasiun,
                    sp.alamat,
                    m.nama_mitra,
                    COUNT(t.id_transaksi) as total_transaksi,
                    SUM(t.total_harga) as total_pendapatan,
                    SUM(t.jumlah_kwh) as total_kwh
                  FROM stasiun_pengisian sp
                  LEFT JOIN transaksi t ON sp.id_stasiun = t.id_stasiun AND {$where}
                  LEFT JOIN mitra m ON sp.id_mitra = m.id_mitra
                  GROUP BY sp.id_stasiun
                  HAVING total_pendapatan > 0
                  ORDER BY total_pendapatan DESC
                  LIMIT {$limit}";
        
        $stmt = $koneksi->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching top stations: " . $e->getMessage());
        return [];
    }
}

// Get mitra list for filter
function getMitraList($koneksi) {
    try {
        $stmt = $koneksi->query("SELECT id_mitra, nama_mitra FROM mitra ORDER BY nama_mitra");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// ==================== MAIN EXECUTION ====================
$available_years = getAvailableYears($koneksi);
$statistics = getFinancialStatistics($koneksi, $selected_year, $selected_month, $mitra_filter);
$monthly_revenue = getMonthlyRevenue($koneksi, $selected_year, $mitra_filter);
$revenue_by_mitra = getRevenueByMitra($koneksi, $selected_year, $selected_month);
$top_stations = getTopStations($koneksi, $selected_year, $selected_month, 10);
$mitra_list = getMitraList($koneksi);

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
    <title>Monitoring Keuangan</title>
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
        <h5><i class="fas fa-chart-line me-2"></i>Monitoring Keuangan</h5>
    </div>
    
    <!-- Period Selector -->
    <div class="card mb-4" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); border: 2px solid rgba(102, 126, 234, 0.3);">
        <div class="card-body">
            <h6 class="mb-3"><i class="fas fa-filter me-2"></i>Filter Periode & Mitra</h6>
            
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
                        <option value="0">Semua Bulan</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $m == $selected_month ? 'selected' : '' ?>>
                                <?= $month_names[$m] ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Mitra</label>
                    <select name="mitra" class="form-select" onchange="this.form.submit()">
                        <option value="0">Semua Mitra</option>
                        <?php foreach ($mitra_list as $mitra): ?>
                            <option value="<?= $mitra['id_mitra'] ?>" <?= $mitra_filter == $mitra['id_mitra'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mitra['nama_mitra']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <a href="keuangan.php" class="btn btn-secondary w-100">
                        <i class="fas fa-redo me-2"></i>Reset Filter
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-info">
                    <h3>Rp <?= number_format($statistics['total_pendapatan'], 0, ',', '.') ?></h3>
                    <p>Total Pendapatan</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($statistics['total_transaksi']) ?></h3>
                    <p>Total Transaksi</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($statistics['total_kwh'], 2) ?> kWh</h3>
                    <p>Total Energi Terjual</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-battery-three-quarters"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($statistics['total_baterai']) ?></h3>
                    <p>Baterai Terpakai</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Additional Stats -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-chart-line fa-2x text-primary mb-2"></i>
                    <h4>Rp <?= number_format($statistics['avg_transaksi'], 0, ',', '.') ?></h4>
                    <p class="mb-0 text-muted">Rata-rata Transaksi</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-arrow-up fa-2x text-success mb-2"></i>
                    <h4>Rp <?= number_format($statistics['max_transaksi'], 0, ',', '.') ?></h4>
                    <p class="mb-0 text-muted">Transaksi Tertinggi</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-arrow-down fa-2x text-info mb-2"></i>
                    <h4>Rp <?= number_format($statistics['min_transaksi'], 0, ',', '.') ?></h4>
                    <p class="mb-0 text-muted">Transaksi Terendah</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Monthly Chart -->
    <?php if (count($monthly_revenue) > 0 && $selected_month == 0): ?>
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Grafik Pendapatan Bulanan <?= $selected_year ?></h6>
            <div style="height: 300px;">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Revenue by Mitra -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fas fa-building me-2"></i>Pendapatan per Mitra</h6>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Mitra</th>
                                    <th>Stasiun</th>
                                    <th class="text-end">Pendapatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($revenue_by_mitra)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            Tidak ada data
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php 
                                    $rank = 1;
                                    foreach ($revenue_by_mitra as $mitra): 
                                        $badge_class = $rank == 1 ? 'bg-warning' : ($rank == 2 ? 'bg-secondary' : ($rank == 3 ? 'bg-danger' : 'bg-info'));
                                    ?>
                                        <tr>
                                            <td>
                                                <span class="badge <?= $badge_class ?>">#<?= $rank ?></span>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($mitra['nama_mitra']) ?></strong><br>
                                                <small class="text-muted"><?= $mitra['total_transaksi'] ?> transaksi</small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary"><?= $mitra['jumlah_stasiun'] ?></span>
                                            </td>
                                            <td class="text-end">
                                                <strong>Rp <?= number_format($mitra['total_pendapatan'], 0, ',', '.') ?></strong>
                                            </td>
                                        </tr>
                                    <?php 
                                    $rank++;
                                    endforeach; 
                                    ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Top Stations -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fas fa-trophy me-2"></i>Top 10 Stasiun Terbaik</h6>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Stasiun</th>
                                    <th class="text-center">Transaksi</th>
                                    <th class="text-end">Pendapatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($top_stations)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            Tidak ada data
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php 
                                    $rank = 1;
                                    foreach ($top_stations as $station): 
                                        $badge_class = $rank <= 3 ? 'bg-success' : 'bg-secondary';
                                    ?>
                                        <tr>
                                            <td>
                                                <span class="badge <?= $badge_class ?>">#<?= $rank ?></span>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($station['nama_stasiun']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($station['nama_mitra']) ?></small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info"><?= $station['total_transaksi'] ?></span>
                                            </td>
                                            <td class="text-end">
                                                <strong>Rp <?= number_format($station['total_pendapatan'], 0, ',', '.') ?></strong>
                                            </td>
                                        </tr>
                                    <?php 
                                    $rank++;
                                    endforeach; 
                                    ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../js/clean-url.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
<?php if (count($monthly_revenue) > 0 && $selected_month == 0): ?>
// Monthly Revenue Chart
const monthlyData = <?= json_encode($monthly_revenue) ?>;
const monthNames = <?= json_encode($month_names) ?>;

const chartLabels = monthlyData.map(d => monthNames[d.bulan]);
const chartData = monthlyData.map(d => d.total_pendapatan);

const ctx = document.getElementById('monthlyChart');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: chartData,
                backgroundColor: 'rgba(102, 126, 234, 0.6)',
                borderColor: 'rgba(102, 126, 234, 1)',
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 2.5,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + (value / 1000000).toFixed(1) + 'jt';
                        }
                    }
                }
            }
        }
    });
}
<?php endif; ?>
</script>

</body>
</html>