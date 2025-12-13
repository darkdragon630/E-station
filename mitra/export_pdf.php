<?php
session_start();
require_once '../config/koneksi.php';

// Cek authentication mitra
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mitra') {
    header('Location: ../auth/login.php');
    exit;
}

$id_mitra = $_SESSION['user_id'];

// Get parameters
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : 0;

// Get mitra info
$sql_mitra = "SELECT nama_mitra, email, no_telepon, alamat FROM mitra WHERE id_mitra = ?";
$stmt_mitra = $koneksi->prepare($sql_mitra);
$stmt_mitra->execute([$id_mitra]);
$mitra_info = $stmt_mitra->fetch(PDO::FETCH_ASSOC);

// Build WHERE clause
$where = "sp.id_mitra = ? AND YEAR(t.tanggal_transaksi) = ?";
$params = [$id_mitra, $selected_year];

if ($selected_month > 0) {
    $where .= " AND MONTH(t.tanggal_transaksi) = ?";
    $params[] = $selected_month;
}

// Get statistics per station
$sql_stasiun = "SELECT 
                    sp.nama_stasiun,
                    sp.alamat,
                    COUNT(t.id_transaksi) as total_transaksi,
                    SUM(t.jumlah_kwh) as total_kwh,
                    SUM(CASE WHEN t.baterai_terpakai > 0 THEN CEIL(t.baterai_terpakai / 100) ELSE 0 END) as total_baterai_terpakai,
                    SUM(t.total_harga) as total_pendapatan
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

// Get all transactions
$sql_trans = "SELECT 
                t.id_transaksi,
                t.tanggal_transaksi,
                sp.nama_stasiun,
                p.nama as nama_pengendara,
                t.jumlah_kwh,
                t.baterai_terpakai,
                t.total_harga,
                t.status_transaksi
              FROM transaksi t
              INNER JOIN stasiun_pengisian sp ON t.id_stasiun = sp.id_stasiun
              LEFT JOIN pengendara p ON t.id_pengendara = p.id_pengendara
              WHERE {$where}
              ORDER BY t.tanggal_transaksi DESC";
$stmt_trans = $koneksi->prepare($sql_trans);
$stmt_trans->execute($params);
$transactions = $stmt_trans->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_pendapatan = array_sum(array_column($stasiun_stats, 'total_pendapatan'));
$total_transaksi = array_sum(array_column($stasiun_stats, 'total_transaksi'));
$total_kwh = array_sum(array_column($stasiun_stats, 'total_kwh'));
$total_baterai = array_sum(array_column($stasiun_stats, 'total_baterai_terpakai'));

// Month names
$month_names = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$period_text = $selected_month > 0 
    ? $month_names[$selected_month] . ' ' . $selected_year 
    : 'Tahun ' . $selected_year;

// Generate PDF using HTML/CSS
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Laporan_Pendapatan_' . $selected_year . ($selected_month > 0 ? '_' . $selected_month : '') . '.pdf"');

// Note: Untuk production, gunakan library seperti TCPDF, FPDF, atau Dompdf
// Ini adalah implementasi sederhana menggunakan HTML yang bisa di-convert ke PDF dengan browser
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Pendapatan - <?= $period_text ?></title>
    <style>
        @page {
            margin: 2cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }
        .header h1 {
            margin: 0;
            color: #667eea;
            font-size: 24pt;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .info-box {
            background: #f5f7fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            margin-bottom: 5px;
        }
        .info-label {
            width: 150px;
            font-weight: bold;
        }
        .summary {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
        }
        .summary-box {
            flex: 1;
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin: 0 10px;
            border-radius: 10px;
        }
        .summary-box:first-child {
            margin-left: 0;
        }
        .summary-box:last-child {
            margin-right: 0;
        }
        .summary-box .label {
            font-size: 10pt;
            opacity: 0.9;
        }
        .summary-box .value {
            font-size: 20pt;
            font-weight: bold;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .section-title {
            font-size: 16pt;
            font-weight: bold;
            color: #667eea;
            margin-top: 30px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        .text-right {
            text-align: right;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            color: #999;
            font-size: 9pt;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>LAPORAN PENDAPATAN</h1>
        <p>Periode: <?= $period_text ?></p>
        <p>Tanggal Cetak: <?= date('d/m/Y H:i') ?> WIB</p>
    </div>

    <!-- Mitra Info -->
    <div class="info-box">
        <div class="info-row">
            <div class="info-label">Nama Mitra:</div>
            <div><?= htmlspecialchars($mitra_info['nama_mitra']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Email:</div>
            <div><?= htmlspecialchars($mitra_info['email']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">No. Telepon:</div>
            <div><?= htmlspecialchars($mitra_info['no_telepon'] ?? '-') ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Alamat:</div>
            <div><?= htmlspecialchars($mitra_info['alamat'] ?? '-') ?></div>
        </div>
    </div>

    <!-- Summary -->
    <div class="summary">
        <div class="summary-box">
            <div class="label">Total Pendapatan</div>
            <div class="value">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></div>
        </div>
        <div class="summary-box">
            <div class="label">Total Transaksi</div>
            <div class="value"><?= number_format($total_transaksi) ?></div>
        </div>
        <div class="summary-box">
            <div class="label">Total kWh</div>
            <div class="value"><?= number_format($total_kwh, 2) ?></div>
        </div>
        <div class="summary-box">
            <div class="label">Baterai Terpakai</div>
            <div class="value"><?= number_format($total_baterai) ?> unit</div>
        </div>
    </div>

    <!-- Station Performance -->
    <div class="section-title">PERFORMA PER STASIUN</div>
    <table>
        <thead>
            <tr>
                <th>Nama Stasiun</th>
                <th class="text-right">Transaksi</th>
                <th class="text-right">Total kWh</th>
                <th class="text-right">Baterai</th>
                <th class="text-right">Total Pendapatan</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($stasiun_stats as $stat): ?>
            <tr>
                <td><?= htmlspecialchars($stat['nama_stasiun']) ?></td>
                <td class="text-right"><?= number_format($stat['total_transaksi']) ?></td>
                <td class="text-right"><?= number_format($stat['total_kwh'], 2) ?></td>
                <td class="text-right"><?= number_format($stat['total_baterai_terpakai']) ?> unit</td>
                <td class="text-right">Rp <?= number_format($stat['total_pendapatan'], 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
            <tr style="font-weight: bold; background: #f0f0f0;">
                <td>TOTAL</td>
                <td class="text-right"><?= number_format($total_transaksi) ?></td>
                <td class="text-right"><?= number_format($total_kwh, 2) ?></td>
                <td class="text-right"><?= number_format($total_baterai) ?> unit</td>
                <td class="text-right">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></td>
            </tr>
        </tbody>
    </table>

    <!-- Transactions Detail -->
    <div class="section-title">DETAIL TRANSAKSI</div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tanggal</th>
                <th>Stasiun</th>
                <th>Pengendara</th>
                <th class="text-right">kWh</th>
                <th class="text-right">Baterai</th>
                <th class="text-right">Unit</th>
                <th class="text-right">Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $tr): ?>
            <tr>
                <td>#<?= $tr['id_transaksi'] ?></td>
                <td><?= date('d/m/Y H:i', strtotime($tr['tanggal_transaksi'])) ?></td>
                <td><?= htmlspecialchars(substr($tr['nama_stasiun'], 0, 20)) ?></td>
                <td><?= htmlspecialchars($tr['nama_pengendara'] ?? '-') ?></td>
                <td class="text-right"><?= number_format($tr['jumlah_kwh'], 2) ?></td>
                <td class="text-right"><?= number_format($tr['baterai_terpakai'], 1) ?>%</td>
                <td class="text-right"><?= ceil($tr['baterai_terpakai'] / 100) ?></td>
                <td class="text-right">Rp <?= number_format($tr['total_harga'], 0, ',', '.') ?></td>
                <td><?= ucfirst($tr['status_transaksi']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        <p>Laporan ini dibuat secara otomatis oleh sistem E-Station</p>
        <p>&copy; <?= date('Y') ?> E-Station - Electric Vehicle Charging Station Management</p>
    </div>
</body>
</html>
<?php
// Note: Untuk menghasilkan PDF sebenarnya, Anda perlu:
// 1. Install library: composer require tecnickcom/tcpdf
// 2. Atau: composer require dompdf/dompdf
// 3. Kemudian convert HTML di atas ke PDF dengan library tersebut
?>