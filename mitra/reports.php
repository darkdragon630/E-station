<?php
session_start();
require_once '../config/koneksi.php';
require_once '../pesan/alerts.php';

// Cek authentication mitra
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mitra') {
    header('Location: ../auth/login.php');
    exit;
}

$id_mitra = $_SESSION['user_id'];

// Get mitra info
$sql_mitra = "SELECT nama_mitra, email, no_telepon, alamat FROM mitra WHERE id_mitra = ?";
$stmt_mitra = $koneksi->prepare($sql_mitra);
$stmt_mitra->execute([$id_mitra]);
$mitra_info = $stmt_mitra->fetch(PDO::FETCH_ASSOC);

// Get current year
$current_year = date('Y');
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : $current_year;
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : 0;

// Get available years
$sql_years = "SELECT DISTINCT YEAR(tanggal_transaksi) as tahun 
              FROM transaksi t
              INNER JOIN stasiun_pengisian sp ON t.id_stasiun = sp.id_stasiun
              WHERE sp.id_mitra = ?
              ORDER BY tahun DESC";
$stmt_years = $koneksi->prepare($sql_years);
$stmt_years->execute([$id_mitra]);
$available_years = $stmt_years->fetchAll(PDO::FETCH_COLUMN);

if (empty($available_years)) {
    $available_years[] = $current_year;
}

// Build WHERE clause
$where = "sp.id_mitra = ? AND YEAR(t.tanggal_transaksi) = ?";
$params = [$id_mitra, $selected_year];

if ($selected_month > 0) {
    $where .= " AND MONTH(t.tanggal_transaksi) = ?";
    $params[] = $selected_month;
}

// Get monthly statistics
$sql_monthly = "SELECT 
                    MONTH(t.tanggal_transaksi) as bulan,
                    COUNT(*) as total_transaksi,
                    SUM(t.jumlah_kwh) as total_kwh,
                    SUM(t.total_harga) as total_pendapatan,
                    SUM(CASE WHEN t.status_transaksi = 'berhasil' THEN t.total_harga ELSE 0 END) as pendapatan_berhasil
                FROM transaksi t
                INNER JOIN stasiun_pengisian sp ON t.id_stasiun = sp.id_stasiun
                WHERE {$where}
                GROUP BY MONTH(t.tanggal_transaksi)
                ORDER BY bulan";
$stmt_monthly = $koneksi->prepare($sql_monthly);
$stmt_monthly->execute($params);
$monthly_data = $stmt_monthly->fetchAll(PDO::FETCH_ASSOC);

// Get statistics per station
$sql_stasiun = "SELECT 
                    sp.id_stasiun,
                    sp.nama_stasiun,
                    sp.alamat,
                    COUNT(t.id_transaksi) as total_transaksi,
                    SUM(t.jumlah_kwh) as total_kwh,
                    SUM(t.total_harga) as total_pendapatan,
                    SUM(CASE WHEN t.baterai_terpakai > 0 THEN CEIL(t.baterai_terpakai / 100) ELSE 0 END) as total_baterai_terpakai,
                    AVG(t.total_harga) as avg_transaksi,
                    MAX(t.total_harga) as max_transaksi
                FROM stasiun_pengisian sp
                LEFT JOIN transaksi t ON sp.id_stasiun = t.id_stasiun AND YEAR(t.tanggal_transaksi) = ? " . 
                ($selected_month > 0 ? "AND MONTH(t.tanggal_transaksi) = ?" : "") . "
                WHERE sp.id_mitra = ?
                GROUP BY sp.id_stasiun
                ORDER BY total_pendapatan DESC";

$params_stasiun = [$selected_year];
if ($selected_month > 0) {
    $params_stasiun[] = $selected_month;
}
$params_stasiun[] = $id_mitra;

$stmt_stasiun = $koneksi->prepare($sql_stasiun);
$stmt_stasiun->execute($params_stasiun);
$stasiun_stats = $stmt_stasiun->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_pendapatan = 0;
$total_transaksi = 0;
$total_kwh = 0;
$total_baterai_terpakai = 0;

foreach ($stasiun_stats as $stat) {
    $total_pendapatan += $stat['total_pendapatan'] ?? 0;
    $total_transaksi += $stat['total_transaksi'] ?? 0;
    $total_kwh += $stat['total_kwh'] ?? 0;
    $total_baterai_terpakai += $stat['total_baterai_terpakai'] ?? 0;
}

// Get top 5 customers
$sql_top_customers = "SELECT 
                        p.nama,
                        p.email,
                        COUNT(t.id_transaksi) as total_transaksi,
                        SUM(t.jumlah_kwh) as total_kwh,
                        SUM(t.total_harga) as total_belanja
                    FROM transaksi t
                    INNER JOIN pengendara p ON t.id_pengendara = p.id_pengendara
                    INNER JOIN stasiun_pengisian sp ON t.id_stasiun = sp.id_stasiun
                    WHERE {$where}
                    GROUP BY t.id_pengendara
                    ORDER BY total_belanja DESC
                    LIMIT 5";
$stmt_customers = $koneksi->prepare($sql_top_customers);
$stmt_customers->execute($params);
$top_customers = $stmt_customers->fetchAll(PDO::FETCH_ASSOC);

// Month names
$month_names = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#0f2746">
  <title>Laporan Pendapatan — E-Station</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/mitra-style.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="../css/alert.css?v=<?= time(); ?>">

  <style>
    .page-header {
      margin-bottom: 30px;
    }

    .page-header h2 {
      background: linear-gradient(90deg, #b98cff, #ff6fa6);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      font-weight: 800;
      margin-bottom: 5px;
    }

    .page-header p {
      color: var(--muted);
    }

    /* Period Selector */
    .period-selector {
      background: linear-gradient(135deg, rgba(185, 140, 255, 0.15), rgba(68, 216, 255, 0.15));
      border: 2px solid rgba(185, 140, 255, 0.3);
      border-radius: 15px;
      padding: 20px;
      margin-bottom: 30px;
    }

    .period-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-bottom: 15px;
    }

    .period-grid select {
      width: 100%;
      padding: 12px 15px;
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      background: rgba(255, 255, 255, 0.1);
      color: var(--text);
      font-weight: 600;
    }

    .download-buttons {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .btn-download {
      padding: 12px 20px;
      border-radius: 10px;
      border: none;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
    }

    .btn-download.pdf {
      background: linear-gradient(135deg, #f093fb, #f5576c);
      color: white;
    }

    .btn-download.excel {
      background: linear-gradient(135deg, #11998e, #38ef7d);
      color: white;
    }

    .btn-download:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    /* Summary Cards */
    .summary-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .summary-card {
      background: linear-gradient(135deg, rgba(185, 140, 255, 0.1), rgba(68, 216, 255, 0.1));
      border: 2px solid rgba(185, 140, 255, 0.3);
      border-radius: 15px;
      padding: 25px;
      transition: all 0.3s;
    }

    .summary-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(185, 140, 255, 0.2);
    }

    .summary-card .icon {
      width: 60px;
      height: 60px;
      border-radius: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      margin-bottom: 15px;
    }

    .summary-card.revenue .icon {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
    }

    .summary-card.transactions .icon {
      background: linear-gradient(135deg, #f093fb, #f5576c);
      color: white;
    }

    .summary-card.energy .icon {
      background: linear-gradient(135deg, #11998e, #38ef7d);
      color: white;
    }

    .summary-card .label {
      color: var(--muted);
      font-size: 0.9rem;
      margin-bottom: 8px;
      font-weight: 600;
    }

    .summary-card .value {
      font-size: 2rem;
      font-weight: 800;
      background: linear-gradient(90deg, #b98cff, #44d8ff);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    /* Chart Section */
    .chart-section {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 15px;
      padding: 25px;
      margin-bottom: 30px;
    }

    .chart-section h4 {
      color: var(--text);
      margin-bottom: 20px;
      font-weight: 700;
    }

    .chart-container {
      height: 300px;
      position: relative;
    }

    /* Tables */
    .report-table {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 15px;
      overflow: hidden;
      border: 1px solid rgba(255, 255, 255, 0.1);
      margin-bottom: 30px;
    }

    .report-table h4 {
      padding: 20px;
      margin: 0;
      background: rgba(185, 140, 255, 0.1);
      color: var(--text);
      font-weight: 700;
      border-bottom: 2px solid rgba(185, 140, 255, 0.3);
    }

    .table-responsive {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    thead {
      background: rgba(185, 140, 255, 0.05);
    }

    th {
      padding: 15px;
      text-align: left;
      color: #b98cff;
      font-weight: 700;
      font-size: 0.85rem;
      text-transform: uppercase;
      border-bottom: 2px solid rgba(185, 140, 255, 0.2);
    }

    td {
      padding: 15px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      color: var(--text);
    }

    tbody tr {
      transition: all 0.3s;
    }

    tbody tr:hover {
      background: rgba(185, 140, 255, 0.05);
    }

    .rank-badge {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 0.85rem;
    }

    .rank-badge.gold {
      background: linear-gradient(135deg, #FFD700, #FFA500);
      color: #000;
    }

    .rank-badge.silver {
      background: linear-gradient(135deg, #C0C0C0, #808080);
      color: #000;
    }

    .rank-badge.bronze {
      background: linear-gradient(135deg, #CD7F32, #8B4513);
      color: #fff;
    }

    .rank-badge.default {
      background: rgba(255, 255, 255, 0.1);
      color: var(--text);
    }

    @media (max-width: 768px) {
      .summary-grid {
        grid-template-columns: 1fr;
      }

      .period-grid {
        grid-template-columns: 1fr;
      }

      .download-buttons {
        flex-direction: column;
      }

      .btn-download {
        width: 100%;
        justify-content: center;
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
        <div class="logo">📊 Laporan</div>
        <div class="header-actions">
            <button id="mobileThemeToggle">🌙</button>
        </div>
    </div>
</div>

<!-- CONTENT -->
<div class="container mt-4 mb-5">
    <?php tampilkan_alert(); ?>

    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="fas fa-chart-line me-2"></i>Laporan Pendapatan</h2>
        <p>Analisis dan unduh laporan pendapatan stasiun Anda</p>
    </div>

    <!-- Period Selector & Download -->
    <div class="period-selector">
        <form method="GET" action="" id="periodForm">
            <div class="period-grid">
                <div>
                    <label style="display: block; color: var(--muted); font-size: 0.85rem; margin-bottom: 5px; font-weight: 600;">Tahun</label>
                    <select name="year" id="year" onchange="document.getElementById('periodForm').submit()">
                        <?php foreach ($available_years as $year): ?>
                            <option value="<?= $year ?>" <?= $year == $selected_year ? 'selected' : '' ?>>
                                <?= $year ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; color: var(--muted); font-size: 0.85rem; margin-bottom: 5px; font-weight: 600;">Bulan</label>
                    <select name="month" id="month" onchange="document.getElementById('periodForm').submit()">
                        <option value="0">Semua Bulan</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $m == $selected_month ? 'selected' : '' ?>>
                                <?= $month_names[$m] ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
        </form>

                <div>
                    <label style="display: block; color: var(--muted); font-size: 0.85rem; margin-bottom: 5px; font-weight: 600;">
                        <i class="fas fa-download me-1"></i> Unduh Laporan
                    </label>
                    <div class="download-buttons">
                        <a href="export_pdf.php?year=<?= $selected_year ?>&month=<?= $selected_month ?>" class="btn-download pdf" target="_blank">
                            <i class="fas fa-file-pdf"></i>
                            Download PDF
                        </a>
                        <a href="export_excel.php?year=<?= $selected_year ?>&month=<?= $selected_month ?>" class="btn-download excel">
                            <i class="fas fa-file-excel"></i>
                            Download Excel
                        </a>
                    </div>
                    <p style="margin-top: 10px; color: var(--muted); font-size: 0.8rem;">
                        <i class="fas fa-info-circle"></i> Laporan mencakup data: <?= htmlspecialchars($mitra_info['nama_mitra'] ?? 'Mitra') ?>
                    </p>
                </div>
    </div>

    <!-- Summary Cards -->
    <div class="summary-grid">
        <div class="summary-card revenue">
            <div class="icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="label">Total Pendapatan</div>
            <div class="value">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></div>
        </div>

        <div class="summary-card transactions">
            <div class="icon">
                <i class="fas fa-receipt"></i>
            </div>
            <div class="label">Total Transaksi</div>
            <div class="value"><?= number_format($total_transaksi) ?></div>
        </div>

        <div class="summary-card energy">
            <div class="icon">
                <i class="fas fa-bolt"></i>
            </div>
            <div class="label">Total Energi Terjual</div>
            <div class="value"><?= number_format($total_kwh, 2) ?> kWh</div>
        </div>
        
        <div class="summary-card" style="border-color: rgba(56, 239, 125, 0.3);">
            <div class="icon" style="background: linear-gradient(135deg, #38ef7d, #11998e);">
                <i class="fas fa-battery-three-quarters"></i>
            </div>
            <div class="label">Total Baterai Terpakai</div>
            <div class="value"><?= number_format($total_baterai_terpakai) ?> unit</div>
        </div>
    </div>

    <!-- Monthly Chart -->
    <?php if (count($monthly_data) > 0 && $selected_month == 0): ?>
    <div class="chart-section">
        <h4><i class="fas fa-chart-bar me-2"></i>Pendapatan Bulanan <?= $selected_year ?></h4>
        <div class="chart-container">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>
    <?php endif; ?>

    <!-- Station Performance Table -->
    <div class="report-table">
        <h4><i class="fas fa-charging-station me-2"></i>Performa Per Stasiun</h4>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nama Stasiun</th>
                        <th>Transaksi</th>
                        <th>Total kWh</th>
                        <th>Baterai</th>
                        <th>Total Pendapatan</th>
                        <th>Rata-rata</th>
                        <th>Tertinggi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($stasiun_stats) > 0): ?>
                        <?php foreach ($stasiun_stats as $stat): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 700;"><?= htmlspecialchars($stat['nama_stasiun']) ?></div>
                                <small style="color: var(--muted);"><?= htmlspecialchars(substr($stat['alamat'], 0, 40)) ?>...</small>
                            </td>
                            <td><strong><?= number_format($stat['total_transaksi'] ?? 0) ?></strong></td>
                            <td><?= number_format($stat['total_kwh'] ?? 0, 2) ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <i class="fas fa-battery-three-quarters" style="color: #38ef7d;"></i>
                                    <strong><?= number_format($stat['total_baterai_terpakai'] ?? 0) ?></strong> unit
                                </div>
                            </td>
                            <td><strong>Rp <?= number_format($stat['total_pendapatan'] ?? 0, 0, ',', '.') ?></strong></td>
                            <td>Rp <?= number_format($stat['avg_transaksi'] ?? 0, 0, ',', '.') ?></td>
                            <td>Rp <?= number_format($stat['max_transaksi'] ?? 0, 0, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--muted);">
                                Tidak ada data untuk periode ini
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Customers Table -->
    <?php if (count($top_customers) > 0): ?>
    <div class="report-table">
        <h4><i class="fas fa-trophy me-2"></i>Top 5 Pelanggan Terbaik</h4>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">Rank</th>
                        <th>Nama Pelanggan</th>
                        <th>Total Transaksi</th>
                        <th>Total kWh</th>
                        <th>Total Belanja</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    foreach ($top_customers as $customer): 
                        $badge_class = $rank == 1 ? 'gold' : ($rank == 2 ? 'silver' : ($rank == 3 ? 'bronze' : 'default'));
                    ?>
                    <tr>
                        <td>
                            <span class="rank-badge <?= $badge_class ?>">
                                <?= $rank ?>
                            </span>
                        </td>
                        <td>
                            <div style="font-weight: 700;"><?= htmlspecialchars($customer['nama']) ?></div>
                            <small style="color: var(--muted);"><?= htmlspecialchars($customer['email']) ?></small>
                        </td>
                        <td><strong><?= number_format($customer['total_transaksi']) ?></strong></td>
                        <td><?= number_format($customer['total_kwh'], 2) ?></td>
                        <td><strong>Rp <?= number_format($customer['total_belanja'], 0, ',', '.') ?></strong></td>
                    </tr>
                    <?php 
                    $rank++;
                    endforeach; 
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- BOTTOM NAVIGATION (MOBILE) -->
<?php include '../components/bottom-nav-mitra.php'; ?>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
        
        const other = btnId === "toggleTheme" ? "mobileThemeToggle" : "toggleTheme";
        const otherBtn = document.getElementById(other);
        if (otherBtn) otherBtn.textContent = isLight ? "☀️" : "🌙";
    });
}

initTheme("toggleTheme");
initTheme("mobileThemeToggle");

// Monthly Chart
<?php if (count($monthly_data) > 0 && $selected_month == 0): ?>
const monthlyData = <?= json_encode($monthly_data) ?>;
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
                backgroundColor: 'rgba(185, 140, 255, 0.6)',
                borderColor: 'rgba(185, 140, 255, 1)',
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
<script src="../js/clean-url.js?v=<?= time(); ?>"></script>
</body>
</html>