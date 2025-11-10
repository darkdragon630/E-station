<?php
session_start();
require_once "../config/koneksi.php";
require_once "../pesan/alerts.php";

// Keamanan dasar
session_regenerate_id(true);
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Frame-Options: DENY");
header("Referrer-Policy: no-referrer");
header("X-XSS-Protection: 1; mode=block");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php?error=unauthorized");
    exit();
}

// Update status transaksi (admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id_transaksi = $_POST['id_transaksi'];
    $status_baru = $_POST['status_transaksi'];

    $stmt = $koneksi->prepare("UPDATE transaksi SET status_transaksi = :status WHERE id_transaksi = :id");
    $stmt->execute([':status' => $status_baru, ':id' => $id_transaksi]);

    header("Location: transaksi.php?success=Status transaksi berhasil diperbarui");
    exit();
}

// Ambil semua data transaksi
$sql = "
    SELECT t.*, 
           p.nama AS nama_pengendara, 
           s.nama_stasiun
    FROM transaksi t
    LEFT JOIN pengendara p ON t.id_pengendara = p.id_pengendara
    LEFT JOIN stasiun_pengisian s ON t.id_stasiun = s.id_stasiun
    ORDER BY t.tanggal_transaksi DESC
";

$stmt = $koneksi->query($sql);
$transaksi = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Riwayat Transaksi - Admin</title>
<style>
    body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 20px; }
    .container { max-width: 1100px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
    h1 { text-align: center; color: #333; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
    th { background: #4f46e5; color: white; }
    .btn { background: #4f46e5; color: white; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; }
    .btn:hover { background: #3730a3; }
    .back { display: inline-block; margin-top: 15px; background: #6b7280; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
    .back:hover { background: #4b5563; }
</style>
</head>
<body>
<div class="container">
    <h1>Riwayat Transaksi</h1>
    <?php tampilkan_alert(); ?>

    <table>
        <tr>
            <th>ID</th>
            <th>Pengendara</th>
            <th>Stasiun</th>
            <th>Tanggal</th>
            <th>Jumlah kWh</th>
            <th>Total Harga (Rp)</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
        <?php if ($transaksi): ?>
            <?php foreach ($transaksi as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t['id_transaksi']) ?></td>
                    <td><?= htmlspecialchars($t['nama_pengendara'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($t['nama_stasiun'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($t['tanggal_transaksi']) ?></td>
                    <td><?= htmlspecialchars($t['jumlah_kwh']) ?></td>
                    <td><?= number_format($t['total_harga'], 0, ',', '.') ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id_transaksi" value="<?= $t['id_transaksi'] ?>">
                            <select name="status_transaksi">
                                <option value="pending" <?= $t['status_transaksi'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="berhasil" <?= $t['status_transaksi'] == 'berhasil' ? 'selected' : '' ?>>Berhasil</option>
                                <option value="gagal" <?= $t['status_transaksi'] == 'gagal' ? 'selected' : '' ?>>Gagal</option>
                            </select>
                    </td>
                    <td><button type="submit" name="update_status" class="btn">Simpan</button></td>
                        </form>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8">Belum ada transaksi.</td></tr>
        <?php endif; ?>
    </table>

    <a href="dashboard.php" class="back">⬅ Kembali ke Dashboard</a>
</div>
</body>
</html>
