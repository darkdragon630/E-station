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
                    SUM(t.total_harga) as total_pendapatan,
                    AVG(t.total_harga) as avg_transaksi
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
                sp.alamat as alamat_stasiun,
                p.nama as nama_pengendara,
                p.email as email_pengendara,
                k.merk as merk_kendaraan,
                k.model as model_kendaraan,
                k.no_plat,
                t.jumlah_kwh,
                t.baterai_terpakai,
                t.total_harga,
                t.status_transaksi
              FROM transaksi t
              INNER JOIN stasiun_pengisian sp ON t.id_stasiun = sp.id_stasiun
              LEFT JOIN pengendara p ON t.id_pengendara = p.id_pengendara
              LEFT JOIN kendaraan k ON p.id_pengendara = k.id_pengendara
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
    ? $month_names[$selected_month] . '_' . $selected_year 
    : 'Tahun_' . $selected_year;

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="Laporan_Pendapatan_' . $period_text . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Start output
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Pendapatan</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #4472C4;
            color: white;
            font-weight: bold;
        }
        .header {
            font-size: 18pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
        }
        .info {
            margin-bottom: 10px;
        }
        .section-title {
            font-size: 14pt;
            font-weight: bold;
            background-color: #D9E1F2;
            padding: 8px;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row {
            background-color: #FFF2CC;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        LAPORAN PENDAPATAN E-STATION
    </div>
    <div class="info">
        <table style="border: none; margin-bottom: 20px;">
            <tr>
                <td style="border: none; width: 150px;"><strong>Nama Mitra:</strong></td>
                <td style="border: none;"><?= htmlspecialchars($mitra_info['nama_mitra']) ?></td>
            </tr>
            <tr>
                <td style="border: none;"><strong>Email:</strong></td>
                <td style="border: none;"><?= htmlspecialchars($mitra_info['email']) ?></td>
            </tr>
            <tr>
                <td style="border: none;"><strong>No. Telepon:</strong></td>
                <td style="border: none;"><?= htmlspecialchars($mitra_info['no_telepon'] ?? '-') ?></td>
            </tr>
            <tr>
                <td style="border: none;"><strong>Alamat:</strong></td>
                <td style="border: none;"><?= htmlspecialchars($mitra_info['alamat'] ?? '-') ?></td>
            </tr>
            <tr>
                <td style="border: none;"><strong>Periode:</strong></td>
                <td style="border: none;"><?= $selected_month > 0 ? $month_names[$selected_month] . ' ' : '' ?><?= $selected_year ?></td>
            </tr>
            <tr>
                <td style="border: none;"><strong>Tanggal Cetak:</strong></td>
                <td style="border: none;"><?= date('d/m/Y H:i') ?> WIB</td>
            </tr>
        </table>
    </div>

    <!-- Summary -->
    <div class="section-title">RINGKASAN</div>
    <table>
        <thead>
            <tr>
                <th>Total Pendapatan</th>
                <th>Total Transaksi</th>
                <th>Total kWh Terjual</th>
                <th>Total Baterai Terpakai</th>
                <th>Rata-rata per Transaksi</th>
            </tr>
        </thead>
        <tbody>
            <tr class="text-right">
                <td>Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></td>
                <td class="text-center"><?= number_format($total_transaksi) ?></td>
                <td class="text-center"><?= number_format($total_kwh, 2) ?></td>
                <td class="text-center"><?= number_format($total_baterai) ?> unit</td>
                <td>Rp <?= $total_transaksi > 0 ? number_format($total_pendapatan / $total_transaksi, 0, ',', '.') : '0' ?></td>
            </tr>
        </tbody>
    </table>

    <!-- Station Performance -->
    <div class="section-title">PERFORMA PER STASIUN</div>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Stasiun</th>
                <th>Alamat</th>
                <th class="text-center">Total Transaksi</th>
                <th class="text-center">Total kWh</th>
                <th class="text-center">Baterai Terpakai</th>
                <th class="text-right">Total Pendapatan</th>
                <th class="text-right">Rata-rata Transaksi</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            foreach ($stasiun_stats as $stat): 
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($stat['nama_stasiun']) ?></td>
                <td><?= htmlspecialchars($stat['alamat']) ?></td>
                <td class="text-center"><?= number_format($stat['total_transaksi']) ?></td>
                <td class="text-center"><?= number_format($stat['total_kwh'], 2) ?></td>
                <td class="text-center"><?= number_format($stat['total_baterai_terpakai']) ?> unit</td>
                <td class="text-right">Rp <?= number_format($stat['total_pendapatan'], 0, ',', '.') ?></td>
                <td class="text-right">Rp <?= number_format($stat['avg_transaksi'], 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="3" class="text-center"><strong>TOTAL</strong></td>
                <td class="text-center"><strong><?= number_format($total_transaksi) ?></strong></td>
                <td class="text-center"><strong><?= number_format($total_kwh, 2) ?></strong></td>
                <td class="text-center"><strong><?= number_format($total_baterai) ?> unit</strong></td>
                <td class="text-right"><strong>Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <!-- Transactions Detail -->
    <div class="section-title">DETAIL TRANSAKSI</div>
    <table>
        <thead>
            <tr>
                <th>ID Transaksi</th>
                <th>Tanggal & Waktu</th>
                <th>Stasiun</th>
                <th>Nama Pengendara</th>
                <th>Email Pengendara</th>
                <th>Merk Kendaraan</th>
                <th>Model</th>
                <th>No. Plat</th>
                <th class="text-center">kWh</th>
                <th class="text-center">Baterai (%)</th>
                <th class="text-center">Unit Baterai</th>
                <th class="text-right">Total Harga</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $tr): ?>
            <tr>
                <td><?= $tr['id_transaksi'] ?></td>
                <td><?= date('d/m/Y H:i', strtotime($tr['tanggal_transaksi'])) ?></td>
                <td><?= htmlspecialchars($tr['nama_stasiun']) ?></td>
                <td><?= htmlspecialchars($tr['nama_pengendara'] ?? '-') ?></td>
                <td><?= htmlspecialchars($tr['email_pengendara'] ?? '-') ?></td>
                <td><?= htmlspecialchars($tr['merk_kendaraan'] ?? '-') ?></td>
                <td><?= htmlspecialchars($tr['model_kendaraan'] ?? '-') ?></td>
                <td><?= htmlspecialchars($tr['no_plat'] ?? '-') ?></td>
                <td class="text-center"><?= number_format($tr['jumlah_kwh'], 2) ?></td>
                <td class="text-center"><?= number_format($tr['baterai_terpakai'], 1) ?>%</td>
                <td class="text-center"><?= ceil($tr['baterai_terpakai'] / 100) ?></td>
                <td class="text-right">Rp <?= number_format($tr['total_harga'], 0, ',', '.') ?></td>
                <td><?= ucfirst($tr['status_transaksi']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <br><br>
    <div style="margin-top: 30px; text-align: center; color: #666; font-size: 10pt;">
        <p>Laporan ini dibuat secara otomatis oleh sistem E-Station</p>
        <p>&copy; <?= date('Y') ?> E-Station - Electric Vehicle Charging Station Management</p>
    </div>
</body>
</html>