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

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Filter
$filter_stasiun = isset($_GET['stasiun']) ? intval($_GET['stasiun']) : 0;
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get stasiun list untuk filter
$sql_stasiun = "SELECT id_stasiun, nama_stasiun FROM stasiun_pengisian WHERE id_mitra = ? ORDER BY nama_stasiun";
$stmt_stasiun = $koneksi->prepare($sql_stasiun);
$stmt_stasiun->execute([$id_mitra]);
$stasiun_list = $stmt_stasiun->fetchAll(PDO::FETCH_ASSOC);

// Build query with filters
$where_conditions = ["sp.id_mitra = ?"];
$params = [$id_mitra];

if ($filter_stasiun > 0) {
    $where_conditions[] = "t.id_stasiun = ?";
    $params[] = $filter_stasiun;
}

if ($filter_status) {
    $where_conditions[] = "t.status_transaksi = ?";
    $params[] = $filter_status;
}

if ($filter_bulan) {
    $where_conditions[] = "DATE_FORMAT(t.tanggal_transaksi, '%Y-%m') = ?";
    $params[] = $filter_bulan;
}

if ($search) {
    $where_conditions[] = "(p.nama LIKE ? OR p.email LIKE ? OR sp.nama_stasiun LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_clause = implode(" AND ", $where_conditions);

// Count total records
$sql_count = "SELECT COUNT(*) as total 
              FROM transaksi t
              INNER JOIN stasiun_pengisian sp ON t.id_stasiun = sp.id_stasiun
              LEFT JOIN pengendara p ON t.id_pengendara = p.id_pengendara
              WHERE {$where_clause}";
$stmt_count = $koneksi->prepare($sql_count);
$stmt_count->execute($params);
$total_records = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $limit);

// Get transactions
$sql = "SELECT 
            t.id_transaksi,
            t.tanggal_transaksi,
            t.jumlah_kwh,
            t.total_harga,
            t.baterai_terpakai,
            t.status_transaksi,
            sp.nama_stasiun,
            sp.alamat as alamat_stasiun,
            p.nama as nama_pengendara,
            p.email as email_pengendara,
            k.merk as merk_kendaraan,
            k.model as model_kendaraan,
            k.no_plat
        FROM transaksi t
        INNER JOIN stasiun_pengisian sp ON t.id_stasiun = sp.id_stasiun
        LEFT JOIN pengendara p ON t.id_pengendara = p.id_pengendara
        LEFT JOIN kendaraan k ON p.id_pengendara = k.id_pengendara
        WHERE {$where_clause}
        ORDER BY t.tanggal_transaksi DESC
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;

$stmt = $koneksi->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$sql_stats = "SELECT 
                COUNT(*) as total_transaksi,
                SUM(jumlah_kwh) as total_kwh,
                SUM(total_harga) as total_pendapatan,
                SUM(CASE WHEN status_transaksi = 'berhasil' THEN 1 ELSE 0 END) as transaksi_berhasil,
                SUM(CASE WHEN status_transaksi = 'pending' THEN 1 ELSE 0 END) as transaksi_pending,
                SUM(CASE WHEN status_transaksi = 'gagal' THEN 1 ELSE 0 END) as transaksi_gagal
              FROM transaksi t
              INNER JOIN stasiun_pengisian sp ON t.id_stasiun = sp.id_stasiun
              WHERE sp.id_mitra = ?";
$stmt_stats = $koneksi->prepare($sql_stats);
$stmt_stats->execute([$id_mitra]);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#0f2746">
  <title>Riwayat Transaksi — E-Station</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/mitra-style.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="../css/alert.css?v=<?= time(); ?>">
  <link rel="icon" type="image/png" href="../images/Logo_1.png">

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

    /* Statistics Cards */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: linear-gradient(135deg, rgba(185, 140, 255, 0.1), rgba(68, 216, 255, 0.1));
      border: 2px solid rgba(185, 140, 255, 0.3);
      border-radius: 15px;
      padding: 20px;
      transition: all 0.3s;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(185, 140, 255, 0.2);
    }

    .stat-card .icon {
      width: 50px;
      height: 50px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin-bottom: 15px;
    }

    .stat-card.primary .icon {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
    }

    .stat-card.success .icon {
      background: linear-gradient(135deg, #11998e, #38ef7d);
      color: white;
    }

    .stat-card.warning .icon {
      background: linear-gradient(135deg, #f093fb, #f5576c);
      color: white;
    }

    .stat-card .label {
      color: var(--muted);
      font-size: 0.9rem;
      margin-bottom: 5px;
    }

    .stat-card .value {
      font-size: 1.8rem;
      font-weight: 800;
      background: linear-gradient(90deg, #b98cff, #44d8ff);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    /* Filters */
    .filters-section {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 15px;
      padding: 20px;
      margin-bottom: 25px;
    }

    .filters-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-bottom: 15px;
    }

    .filter-group label {
      display: block;
      color: var(--muted);
      font-size: 0.85rem;
      margin-bottom: 5px;
      font-weight: 600;
    }

    .filter-group select,
    .filter-group input {
      width: 100%;
      padding: 10px 15px;
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      background: rgba(255, 255, 255, 0.1);
      color: var(--text);
      font-size: 0.9rem;
    }

    .filter-actions {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
    }

    .btn-filter {
      padding: 10px 20px;
      border-radius: 10px;
      border: none;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-filter.primary {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
    }

    .btn-filter.secondary {
      background: rgba(255, 255, 255, 0.1);
      color: var(--text);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    /* Transactions Table */
    .transactions-table {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 15px;
      overflow: hidden;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .table-responsive {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    thead {
      background: rgba(185, 140, 255, 0.1);
    }

    th {
      padding: 15px;
      text-align: left;
      color: #b98cff;
      font-weight: 700;
      font-size: 0.85rem;
      text-transform: uppercase;
      border-bottom: 2px solid rgba(185, 140, 255, 0.3);
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

    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .status-badge.berhasil {
      background: rgba(56, 239, 125, 0.2);
      color: #38ef7d;
    }

    .status-badge.pending {
      background: rgba(255, 193, 7, 0.2);
      color: #ffc107;
    }

    .status-badge.gagal {
      background: rgba(245, 87, 108, 0.2);
      color: #f5576c;
    }

    /* Pagination */
    .pagination {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
      margin-top: 25px;
    }

    .pagination a,
    .pagination span {
      padding: 8px 15px;
      border-radius: 8px;
      background: rgba(255, 255, 255, 0.1);
      color: var(--text);
      text-decoration: none;
      transition: all 0.3s;
    }

    .pagination a:hover {
      background: rgba(185, 140, 255, 0.3);
    }

    .pagination .active {
      background: linear-gradient(135deg, #b98cff, #44d8ff);
      color: white;
      font-weight: 700;
    }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
    }

    .empty-state i {
      font-size: 4rem;
      color: var(--muted);
      margin-bottom: 20px;
    }

    .empty-state h4 {
      color: var(--text);
      margin-bottom: 10px;
    }

    .empty-state p {
      color: var(--muted);
    }

    @media (max-width: 768px) {
      .stats-grid {
        grid-template-columns: 1fr;
      }

      .filters-grid {
        grid-template-columns: 1fr;
      }

      .table-responsive {
        font-size: 0.85rem;
      }

      th, td {
        padding: 10px 8px;
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
        <div class="logo">📊 Riwayat</div>
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
        <h2><i class="fas fa-history me-2"></i>Riwayat Transaksi</h2>
        <p>Pantau semua transaksi pengisian daya di stasiun Anda</p>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="icon">
                <i class="fas fa-receipt"></i>
            </div>
            <div class="label">Total Transaksi</div>
            <div class="value"><?= number_format($stats['total_transaksi'] ?? 0) ?></div>
        </div>

        <div class="stat-card success">
            <div class="icon">
                <i class="fas fa-bolt"></i>
            </div>
            <div class="label">Total Energi Terjual</div>
            <div class="value"><?= number_format($stats['total_kwh'] ?? 0, 2) ?> kWh</div>
        </div>

        <div class="stat-card warning">
            <div class="icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="label">Total Pendapatan</div>
            <div class="value">Rp <?= number_format($stats['total_pendapatan'] ?? 0, 0, ',', '.') ?></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" action="" id="filterForm">
            <div class="filters-grid">
                <div class="filter-group">
                    <label for="stasiun">Stasiun</label>
                    <select name="stasiun" id="stasiun">
                        <option value="">Semua Stasiun</option>
                        <?php foreach ($stasiun_list as $st): ?>
                            <option value="<?= $st['id_stasiun'] ?>" <?= $filter_stasiun == $st['id_stasiun'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($st['nama_stasiun']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="status">Status</label>
                    <select name="status" id="status">
                        <option value="">Semua Status</option>
                        <option value="berhasil" <?= $filter_status == 'berhasil' ? 'selected' : '' ?>>Berhasil</option>
                        <option value="pending" <?= $filter_status == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="gagal" <?= $filter_status == 'gagal' ? 'selected' : '' ?>>Gagal</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="bulan">Bulan</label>
                    <input type="month" name="bulan" id="bulan" value="<?= htmlspecialchars($filter_bulan) ?>">
                </div>

                <div class="filter-group">
                    <label for="search">Cari</label>
                    <input type="text" name="search" id="search" placeholder="Nama/Email/Stasiun" value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>

            <div class="filter-actions">
                <button type="button" class="btn-filter secondary" onclick="resetFilters()">
                    <i class="fas fa-redo"></i> Reset
                </button>
                <button type="submit" class="btn-filter primary">
                    <i class="fas fa-filter"></i> Terapkan Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Transactions Table -->
    <div class="transactions-table">
        <?php if (count($transactions) > 0): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tanggal</th>
                        <th>Stasiun</th>
                        <th>Pengendara</th>
                        <th>Kendaraan</th>
                        <th>kWh</th>
                        <th>Baterai</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $tr): ?>
                    <tr>
                        <td><strong>#<?= $tr['id_transaksi'] ?></strong></td>
                        <td>
                            <div><?= date('d/m/Y', strtotime($tr['tanggal_transaksi'])) ?></div>
                            <small style="color: var(--muted);"><?= date('H:i', strtotime($tr['tanggal_transaksi'])) ?></small>
                        </td>
                        <td>
                            <div style="font-weight: 600;"><?= htmlspecialchars($tr['nama_stasiun']) ?></div>
                            <small style="color: var(--muted);"><?= htmlspecialchars(substr($tr['alamat_stasiun'], 0, 30)) ?>...</small>
                        </td>
                        <td>
                            <div><?= htmlspecialchars($tr['nama_pengendara'] ?? 'N/A') ?></div>
                            <small style="color: var(--muted);"><?= htmlspecialchars($tr['email_pengendara'] ?? '') ?></small>
                        </td>
                        <td>
                            <?php if ($tr['merk_kendaraan']): ?>
                            <div><?= htmlspecialchars($tr['merk_kendaraan']) ?> <?= htmlspecialchars($tr['model_kendaraan']) ?></div>
                            <small style="color: var(--muted);"><?= htmlspecialchars($tr['no_plat']) ?></small>
                            <?php else: ?>
                            <span style="color: var(--muted);">-</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= number_format($tr['jumlah_kwh'], 2) ?></strong></td>
                        <td>
                            <?php if ($tr['baterai_terpakai'] > 0): ?>
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <i class="fas fa-battery-three-quarters" style="color: #38ef7d;"></i>
                                    <strong><?= number_format($tr['baterai_terpakai'], 1) ?>%</strong>
                                </div>
                                <small style="color: var(--muted);">
                                    <?= ceil($tr['baterai_terpakai'] / 100) ?> unit
                                </small>
                            <?php else: ?>
                                <span style="color: var(--muted);">-</span>
                            <?php endif; ?>
                        </td>
                        <td><strong>Rp <?= number_format($tr['total_harga'], 0, ',', '.') ?></strong></td>
                        <td>
                            <span class="status-badge <?= $tr['status_transaksi'] ?>">
                                <?php
                                $icon = $tr['status_transaksi'] == 'berhasil' ? 'check-circle' : ($tr['status_transaksi'] == 'pending' ? 'clock' : 'times-circle');
                                ?>
                                <i class="fas fa-<?= $icon ?>"></i>
                                <?= ucfirst($tr['status_transaksi']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?><?= $filter_stasiun ? '&stasiun='.$filter_stasiun : '' ?><?= $filter_status ? '&status='.$filter_status : '' ?><?= $filter_bulan ? '&bulan='.$filter_bulan : '' ?><?= $search ? '&search='.$search : '' ?>">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);
            
            for ($i = $start; $i <= $end; $i++):
            ?>
            <a href="?page=<?= $i ?><?= $filter_stasiun ? '&stasiun='.$filter_stasiun : '' ?><?= $filter_status ? '&status='.$filter_status : '' ?><?= $filter_bulan ? '&bulan='.$filter_bulan : '' ?><?= $search ? '&search='.$search : '' ?>" 
               class="<?= $i == $page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page + 1 ?><?= $filter_stasiun ? '&stasiun='.$filter_stasiun : '' ?><?= $filter_status ? '&status='.$filter_status : '' ?><?= $filter_bulan ? '&bulan='.$filter_bulan : '' ?><?= $search ? '&search='.$search : '' ?>">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h4>Tidak Ada Transaksi</h4>
            <p>Belum ada riwayat transaksi <?= $filter_stasiun || $filter_status || $filter_bulan || $search ? 'dengan filter yang dipilih' : 'yang tercatat' ?>.</p>
            <?php if ($filter_stasiun || $filter_status || $filter_bulan || $search): ?>
            <button class="btn-filter secondary" onclick="resetFilters()" style="margin-top: 15px;">
                <i class="fas fa-redo"></i> Reset Filter
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- BOTTOM NAVIGATION (MOBILE) -->
<?php include '../components/bottom-nav-mitra.php'; ?>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

// Reset Filters
function resetFilters() {
    window.location.href = 'usage_history.php';
}
</script>
<script src="../js/clean-url.js?v=<?= time(); ?>"></script>
</body>
</html>