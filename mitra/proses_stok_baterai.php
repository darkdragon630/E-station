<?php
session_start();
require_once '../config/koneksi.php';
require_once '../pesan/alerts.php';

// Cek authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mitra') {
    header('Location: ../auth/login.php');
    exit;
}

$id_mitra = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

try {
    if ($action === 'tambah') {
        // Validasi input
        $id_stasiun = $_POST['id_stasiun'] ?? null;
        $tipe_baterai = trim($_POST['tipe_baterai'] ?? '');
        $jumlah = intval($_POST['jumlah'] ?? 0);

        if (empty($id_stasiun) || empty($tipe_baterai) || $jumlah < 1) {
            set_error_handler('danger', 'Data tidak lengkap atau tidak valid');
            header('Location: stok_baterai.php?stasiun=' . $id_stasiun);
            exit;
        }

        // Verifikasi stasiun milik mitra
        $stmt = $koneksi->prepare("SELECT id_stasiun FROM stasiun_pengisian WHERE id_stasiun = ? AND id_mitra = ?");
        $stmt->execute([$id_stasiun, $id_mitra]);
        
        if (!$stmt->fetch()) {
            set_error_handler('danger', 'Stasiun tidak ditemukan atau bukan milik Anda');
            header('Location: stok_baterai.php');
            exit;
        }

        // Cek apakah tipe baterai sudah ada
        $stmt = $koneksi->prepare("SELECT id_stok, jumlah FROM stok_baterai WHERE id_stasiun = ? AND tipe_baterai = ?");
        $stmt->execute([$id_stasiun, $tipe_baterai]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Update jumlah jika sudah ada
            $jumlah_baru = $existing['jumlah'] + $jumlah;
            $stmt = $koneksi->prepare("UPDATE stok_baterai SET jumlah = ?, terakhir_update = NOW() WHERE id_stok = ?");
            $stmt->execute([$jumlah_baru, $existing['id_stok']]);
            set_flash_message('success', "Stok baterai {$tipe_baterai} berhasil ditambahkan ({$jumlah} unit)");
        } else {
            // Insert baru
            $stmt = $koneksi->prepare("INSERT INTO stok_baterai (id_stasiun, tipe_baterai, jumlah, terakhir_update) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$id_stasiun, $tipe_baterai, $jumlah]);
            set_flash_message('success', "Stok baterai {$tipe_baterai} berhasil ditambahkan");
        }

        header('Location: stok_baterai.php?stasiun=' . $id_stasiun);
        exit;

    } elseif ($action === 'edit') {
        // Validasi input
        $id_stok = $_POST['id_stok'] ?? null;
        $jumlah = intval($_POST['jumlah'] ?? 0);

        if (empty($id_stok) || $jumlah < 0) {
            set_error_handler('danger', 'Data tidak lengkap atau tidak valid');
            header('Location: stok_baterai.php');
            exit;
        }

        // Verifikasi stok milik mitra
        $stmt = $koneksi->prepare("
            SELECT sb.id_stok, sb.id_stasiun, sb.tipe_baterai
            FROM stok_baterai sb
            JOIN stasiun_pengisian sp ON sb.id_stasiun = sp.id_stasiun
            WHERE sb.id_stok = ? AND sp.id_mitra = ?
        ");
        $stmt->execute([$id_stok, $id_mitra]);
        $stok = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$stok) {
            set_error_handler('danger', 'Stok tidak ditemukan atau bukan milik Anda');
            header('Location: stok_baterai.php');
            exit;
        }

        // Update jumlah
        $stmt = $koneksi->prepare("UPDATE stok_baterai SET jumlah = ?, terakhir_update = NOW() WHERE id_stok = ?");
        $stmt->execute([$jumlah, $id_stok]);

        set_flash_message('success', "Stok baterai {$stok['tipe_baterai']} berhasil diperbarui");
        header('Location: stok_baterai.php?stasiun=' . $stok['id_stasiun']);
        exit;

    } elseif ($action === 'hapus') {
        $id_stok = $_POST['id_stok'] ?? null;

        if (empty($id_stok)) {
            set_error_handler('danger', 'ID stok tidak valid');
            header('Location: stok_baterai.php');
            exit;
        }

        // Verifikasi dan hapus
        $stmt = $koneksi->prepare("
            SELECT sb.id_stok, sb.id_stasiun, sb.tipe_baterai
            FROM stok_baterai sb
            JOIN stasiun_pengisian sp ON sb.id_stasiun = sp.id_stasiun
            WHERE sb.id_stok = ? AND sp.id_mitra = ?
        ");
        $stmt->execute([$id_stok, $id_mitra]);
        $stok = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$stok) {
            set_error_handler('danger', 'Stok tidak ditemukan atau bukan milik Anda');
            header('Location: stok_baterai.php');
            exit;
        }

        $stmt = $koneksi->prepare("DELETE FROM stok_baterai WHERE id_stok = ?");
        $stmt->execute([$id_stok]);

        set_flash_message('success', "Stok baterai {$stok['tipe_baterai']} berhasil dihapus");
        header('Location: stok_baterai.php?stasiun=' . $stok['id_stasiun']);
        exit;

    } else {
        set_error_handler('danger', 'Aksi tidak valid');
        header('Location: stok_baterai.php');
        exit;
    }

} catch (PDOException $e) {
    error_log("Process Stok Baterai Error: " . $e->getMessage());
    set_error_handler('danger', 'Terjadi kesalahan sistem. Silakan coba lagi.');
    header('Location: stok_baterai.php');
    exit;
}
?>