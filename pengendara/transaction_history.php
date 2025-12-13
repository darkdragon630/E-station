<?php
session_start();
require_once '../config/koneksi.php';
require_once '../pesan/alerts.php';

// ✅ FIXED: Gunakan user_id dan role yang konsisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pengendara') {
    header('Location: ../auth/login.php');
    exit;
}

if (!isset($koneksi)) {
    die("Koneksi database tidak tersedia. Periksa file config/koneksi.php.");
}

// ✅ FIXED: Gunakan user_id sebagai id_pengendara
$id_pengendara = $_SESSION['user_id'];

try {
    // Ambil riwayat transaksi dengan info baterai
    $stmt = $koneksi->prepare("
        SELECT t.id_transaksi, t.tanggal_transaksi, t.jumlah_kwh, t.total_harga, 
               t.status_transaksi, t.baterai_terpakai, s.nama_stasiun, s.alamat
        FROM transaksi t 
        JOIN stasiun_pengisian s ON t.id_stasiun = s.id_stasiun 
        WHERE t.id_pengendara = ? 
        ORDER BY t.tanggal_transaksi DESC
    ");
    $stmt->execute([$id_pengendara]);
    $transaksi = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Hitung total statistik termasuk baterai
    $stmtStats = $koneksi->prepare("
        SELECT 
            COUNT(*) as total_transaksi,
            SUM(jumlah_kwh) as total_kwh,
            SUM(total_harga) as total_pengeluaran,
            SUM(CASE WHEN baterai_terpakai > 0 THEN CEIL(baterai_terpakai / 100) ELSE 0 END) as total_baterai_terpakai,
            AVG(baterai_terpakai) as avg_baterai_terpakai
        FROM transaksi 
        WHERE id_pengendara = ?
    ");
    $stmtStats->execute([$id_pengendara]);
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $transaksi = [];
    $stats = [
        'total_transaksi' => 0, 
        'total_kwh' => 0, 
        'total_pengeluaran' => 0,
        'total_baterai_terpakai' => 0,
        'avg_baterai_terpakai' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - E-Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/pengendara-style.css">
    <link rel="stylesheet" href="../css/alert.css">
    
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap');

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

/* === ANIMATIONS === */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

@keyframes shimmer {
    0% { background-position: -1000px 0; }
    100% { background-position: 1000px 0; }
}

/* === DARK MODE (DEFAULT) === */
body {
    background: linear-gradient(135deg, #0a192f 0%, #1e3a8a 50%, #312e81 100%);
    color: #e2e8f0;
    min-height: 100vh;
    transition: all 0.4s ease;
    position: relative;
    overflow-x: hidden;
}

body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(168, 85, 247, 0.1) 0%, transparent 50%);
    pointer-events: none;
    z-index: 0;
}

.container {
    position: relative;
    z-index: 1;
}

/* === HEADER === */
.page-header {
    text-align: center;
    margin: 40px 0;
    animation: fadeInUp 0.8s ease;
}

.page-header h1 {
    font-size: 3rem;
    font-weight: 800;
    background: linear-gradient(135deg, #60a5fa, #a855f7, #ec4899);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 10px;
    animation: shimmer 3s linear infinite;
    background-size: 200% 200%;
}

.page-header p {
    color: #94a3b8;
    font-size: 1.1rem;
}

/* === STATS CARDS === */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
    animation: fadeInUp 1s ease;
}

.stat-card {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 20px;
    padding: 25px;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(96, 165, 250, 0.1), rgba(168, 85, 247, 0.1));
    opacity: 0;
    transition: opacity 0.4s ease;
}

.stat-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: 0 20px 40px rgba(96, 165, 250, 0.3);
    border-color: rgba(96, 165, 250, 0.5);
}

.stat-card:hover::before {
    opacity: 1;
}

.stat-icon {
    font-size: 2.5rem;
    margin-bottom: 15px;
    display: inline-block;
    animation: pulse 2s infinite;
}

.stat-card h3 {
    font-size: 1.8rem;
    font-weight: 700;
    margin: 10px 0;
    color: #60a5fa;
}

.stat-card p {
    color: #94a3b8;
    font-size: 0.95rem;
    margin: 0;
}

/* === TABLE CONTAINER === */
.table-container {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 24px;
    padding: 30px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: fadeInUp 1.2s ease;
    overflow: hidden;
}

/* === SEARCH & FILTER === */
.filter-section {
    display: flex;
    gap: 15px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.filter-section input,
.filter-section select {
    flex: 1;
    min-width: 200px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    padding: 12px 20px;
    color: #e2e8f0;
    transition: all 0.3s ease;
}

.filter-section input:focus,
.filter-section select:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.12);
    border-color: #60a5fa;
    box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.2);
}

.filter-section input::placeholder {
    color: #64748b;
}

/* === TABLE === */
.transaction-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10px;
}

.transaction-table thead th {
    background: linear-gradient(135deg, rgba(96, 165, 250, 0.2), rgba(168, 85, 247, 0.2));
    color: #e2e8f0;
    padding: 18px;
    font-weight: 600;
    text-align: left;
    border: none;
    font-size: 0.95rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.transaction-table thead th:first-child {
    border-radius: 12px 0 0 12px;
}

.transaction-table thead th:last-child {
    border-radius: 0 12px 12px 0;
}

.transaction-table tbody tr {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    animation: slideInLeft 0.5s ease;
    animation-fill-mode: both;
}

.transaction-table tbody tr:nth-child(1) { animation-delay: 0.1s; }
.transaction-table tbody tr:nth-child(2) { animation-delay: 0.2s; }
.transaction-table tbody tr:nth-child(3) { animation-delay: 0.3s; }
.transaction-table tbody tr:nth-child(4) { animation-delay: 0.4s; }
.transaction-table tbody tr:nth-child(5) { animation-delay: 0.5s; }

.transaction-table tbody tr:hover {
    background: rgba(96, 165, 250, 0.15);
    transform: scale(1.02);
    box-shadow: 0 8px 25px rgba(96, 165, 250, 0.3);
}

.transaction-table tbody td {
    padding: 20px 18px;
    border: none;
    color: #cbd5e1;
}

.transaction-table tbody td:first-child {
    border-radius: 12px 0 0 12px;
}

.transaction-table tbody td:last-child {
    border-radius: 0 12px 12px 0;
}

/* === BATTERY INDICATOR === */
.battery-indicator {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    background: rgba(56, 239, 125, 0.15);
    border: 1px solid rgba(56, 239, 125, 0.3);
    border-radius: 20px;
    font-weight: 600;
}

.battery-icon {
    color: #38ef7d;
    font-size: 1.2rem;
}

/* === STATUS BADGES === */
.status-badge {
    display: inline-block;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-berhasil {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
}

.status-pending {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);
}

.status-gagal {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
}

/* === PRICE HIGHLIGHT === */
.price-highlight {
    font-weight: 700;
    font-size: 1.1rem;
    color: #60a5fa;
    text-shadow: 0 0 10px rgba(96, 165, 250, 0.5);
}

/* === EMPTY STATE === */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    animation: fadeInUp 0.8s ease;
}

.empty-state i {
    font-size: 5rem;
    color: #475569;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state h3 {
    color: #94a3b8;
    margin-bottom: 10px;
}

.empty-state p {
    color: #64748b;
}

/* === LIGHT MODE === */
body.light {
    background: linear-gradient(135deg, #f0f9ff 0%, #dbeafe 50%, #e0e7ff 100%);
    color: #1e293b;
}

body.light::before {
    background: 
        radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.05) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(168, 85, 247, 0.05) 0%, transparent 50%);
}

body.light .stat-card {
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(0, 0, 0, 0.1);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

body.light .stat-card:hover {
    box-shadow: 0 20px 40px rgba(59, 130, 246, 0.2);
}

body.light .stat-card p {
    color: #64748b;
}

body.light .table-container {
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(0, 0, 0, 0.1);
}

body.light .filter-section input,
body.light .filter-section select {
    background: white;
    border: 1px solid #cbd5e1;
    color: #1e293b;
}

body.light .filter-section input:focus,
body.light .filter-section select:focus {
    background: white;
    border-color: #3b82f6;
}

body.light .transaction-table thead th {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(168, 85, 247, 0.1));
    color: #1e293b;
}

body.light .transaction-table tbody tr {
    background: white;
}

body.light .transaction-table tbody tr:hover {
    background: rgba(59, 130, 246, 0.1);
}

body.light .transaction-table tbody td {
    color: #475569;
}

body.light .battery-indicator {
    background: rgba(56, 239, 125, 0.1);
    border-color: rgba(56, 239, 125, 0.2);
}

body.light .empty-state i {
    color: #cbd5e1;
}

body.light .empty-state h3 {
    color: #475569;
}

/* === RESPONSIVE === */
@media (max-width: 768px) {
    .page-header h1 {
        font-size: 2rem;
    }
    
    .stats-container {
        grid-template-columns: 1fr;
    }
    
    .table-container {
        overflow-x: auto;
        padding: 15px;
    }
    
    .transaction-table {
        min-width: 900px;
    }
    
    .filter-section {
        flex-direction: column;
    }
    
    .filter-section input,
    .filter-section select {
        min-width: 100%;
    }
}

/* === SCROLLBAR === */
::-webkit-scrollbar {
    width: 12px;
    height: 12px;
}

::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #60a5fa, #a855f7);
    border-radius: 10px;
    border: 2px solid transparent;
    background-clip: content-box;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #3b82f6, #9333ea);
    background-clip: content-box;
}
</style>
</head>
<body>
    <!-- DESKTOP THEME TOGGLE -->
    <div class="theme-toggle">
        <button id="toggleTheme" aria-label="Ganti Tema">🌙</button>
    </div>

    <!-- DESKTOP NAVBAR - Reusable Component -->
    <?php include '../components/navbar-pengendara.php'; ?>

    <!-- MOBILE HEADER -->
    <div class="mobile-header d-md-none">
        <div class="header-top">
            <div class="logo">
                <i class="fas fa-bolt"></i>
                E-Station
            </div>
            <div class="header-actions">
                <button id="mobileThemeToggle">🌙</button>
            </div>
        </div>
        <div class="welcome-text">
            <h2>📊 Riwayat Transaksi</h2>
            <p>Lihat semua transaksi pengisian baterai</p>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="container mt-md-5 mb-5">
        <?php tampilkan_alert(); ?>
        
        <!-- DESKTOP HEADER -->
        <div class="page-header d-none d-md-block">
            <h1><i class="fas fa-receipt me-3"></i>Riwayat Transaksi</h1>
            <p>Lihat semua transaksi pengisian baterai Anda</p>
        </div>

        <!-- STATISTICS CARDS -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <h3><?php echo number_format($stats['total_transaksi'] ?? 0); ?></h3>
                <p>Total Transaksi</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⚡</div>
                <h3><?php echo number_format($stats['total_kwh'] ?? 0, 2); ?> kWh</h3>
                <p>Total Energi Terpakai</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🔋</div>
                <h3><?php echo number_format($stats['total_baterai_terpakai'] ?? 0); ?> unit</h3>
                <p>Total Baterai Terpakai</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <h3>Rp <?php echo number_format($stats['total_pengeluaran'] ?? 0, 0, ',', '.'); ?></h3>
                <p>Total Pengeluaran</p>
            </div>
        </div>

        <!-- TABLE CONTAINER -->
        <div class="table-container">
            <!-- FILTER SECTION -->
            <div class="filter-section">
                <input type="text" id="searchInput" placeholder="🔍 Cari nama stasiun...">
                <select id="statusFilter">
                    <option value="">Semua Status</option>
                    <option value="berhasil">Berhasil</option>
                    <option value="pending">Pending</option>
                    <option value="gagal">Gagal</option>
                </select>
                <input type="date" id="dateFilter">
            </div>

            <?php if (empty($transaksi)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Belum Ada Transaksi</h3>
                    <p>Anda belum melakukan transaksi pengisian baterai</p>
                </div>
            <?php else: ?>
                <!-- TABLE -->
                <div style="overflow-x: auto;">
                    <table class="transaction-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-calendar me-2"></i>Tanggal</th>
                                <th><i class="fas fa-charging-station me-2"></i>Stasiun</th>
                                <th><i class="fas fa-bolt me-2"></i>Energi (kWh)</th>
                                <th><i class="fas fa-battery-three-quarters me-2"></i>Baterai</th>
                                <th><i class="fas fa-money-bill-wave me-2"></i>Total Harga</th>
                                <th><i class="fas fa-info-circle me-2"></i>Status</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <?php foreach ($transaksi as $t): 
                                $statusClass = '';
                                switch(strtolower($t['status_transaksi'])) {
                                    case 'berhasil':
                                    case 'selesai':
                                        $statusClass = 'status-berhasil';
                                        break;
                                    case 'pending':
                                        $statusClass = 'status-pending';
                                        break;
                                    case 'gagal':
                                        $statusClass = 'status-gagal';
                                        break;
                                }
                            ?>
                                <tr>
                                    <td>
                                        <i class="far fa-calendar-alt me-2"></i>
                                        <?php echo date('d M Y, H:i', strtotime($t['tanggal_transaksi'])); ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($t['nama_stasiun']); ?></strong><br>
                                        <small style="color: #64748b;">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($t['alamat']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <i class="fas fa-plug me-2" style="color: #fbbf24;"></i>
                                        <strong><?php echo number_format($t['jumlah_kwh'], 2); ?> kWh</strong>
                                    </td>
                                    <td>
                                        <?php if ($t['baterai_terpakai'] > 0): ?>
                                            <div class="battery-indicator">
                                                <i class="fas fa-battery-three-quarters battery-icon"></i>
                                                <div>
                                                    <div style="font-weight: 700;"><?php echo number_format($t['baterai_terpakai'], 1); ?>%</div>
                                                    <small style="font-size: 0.75rem; opacity: 0.8;">
                                                        <?php echo ceil($t['baterai_terpakai'] / 100); ?> unit
                                                    </small>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #64748b;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="price-highlight">
                                            Rp <?php echo number_format($t['total_harga'], 0, ',', '.'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo ucfirst($t['status_transaksi']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- BOTTOM NAVIGATION (MOBILE) -->
    <?php include '../components/bottom-nav.php'; ?>

    <!-- SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/clean-url.js"></script>
    <script>
        // Desktop Theme Toggle
        const toggleButton = document.getElementById("toggleTheme");
        if (toggleButton) {
            const savedTheme = localStorage.getItem("theme");
            if (savedTheme === "light") {
                document.body.classList.add("light");
                toggleButton.textContent = "☀️";
            } else {
                toggleButton.textContent = "🌙";
            }

            toggleButton.addEventListener("click", () => {
                document.body.classList.toggle("light");
                const isLight = document.body.classList.contains("light");
                toggleButton.textContent = isLight ? "☀️" : "🌙";
                localStorage.setItem("theme", isLight ? "light" : "dark");
            });
        }

        // Mobile Theme Toggle
        const mobileToggleButton = document.getElementById("mobileThemeToggle");
        if (mobileToggleButton) {
            const savedTheme = localStorage.getItem("theme");
            if (savedTheme === "light") {
                document.body.classList.add("light");
                mobileToggleButton.textContent = "☀️";
            } else {
                mobileToggleButton.textContent = "🌙";
            }

            mobileToggleButton.addEventListener("click", () => {
                document.body.classList.toggle("light");
                const isLight = document.body.classList.contains("light");
                mobileToggleButton.textContent = isLight ? "☀️" : "🌙";
                localStorage.setItem("theme", isLight ? "light" : "dark");
            });
        }

        // SEARCH FUNCTIONALITY
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const dateFilter = document.getElementById('dateFilter');
        const tableBody = document.getElementById('tableBody');
        
        function filterTable() {
            if (!tableBody) return;
            
            const searchTerm = searchInput.value.toLowerCase();
            const statusTerm = statusFilter.value.toLowerCase();
            const dateTerm = dateFilter.value;
            
            const rows = tableBody.getElementsByTagName('tr');
            
            for (let row of rows) {
                const stationName = row.cells[1].textContent.toLowerCase();
                const status = row.cells[5].textContent.toLowerCase();
                const date = row.cells[0].textContent;
                
                let showRow = true;
                
                if (searchTerm && !stationName.includes(searchTerm)) {
                    showRow = false;
                }
                
                if (statusTerm && !status.includes(statusTerm)) {
                    showRow = false;
                }
                
                if (dateTerm) {
                    const rowDate = new Date(date);
                    const filterDate = new Date(dateTerm);
                    if (rowDate.toDateString() !== filterDate.toDateString()) {
                        showRow = false;
                    }
                }
                
                row.style.display = showRow ? '' : 'none';
            }
        }
        
        if (searchInput) searchInput.addEventListener('keyup', filterTable);
        if (statusFilter) statusFilter.addEventListener('change', filterTable);
        if (dateFilter) dateFilter.addEventListener('change', filterTable);

        // Prevent zoom on double tap (iOS)
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function (event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
    </script>
</body>
</html>