<?php
session_start();
require_once '../config/koneksi.php';

// Cek authentication mitra
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mitra') {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_mitra = $_SESSION['user_id'];
    
    // Ambil data dari form
    $nama_stasiun = trim($_POST['nama_stasiun']);
    $alamat = trim($_POST['alamat']);
    $latitude = trim($_POST['latitude']);
    $longitude = trim($_POST['longitude']);
    $kapasitas = intval($_POST['kapasitas']);
    $jumlah_slot = intval($_POST['jumlah_slot']);
    $tarif = intval($_POST['tarif']);
    $jam_operasional = trim($_POST['jam_operasional']);
    $fasilitas = trim($_POST['fasilitas']);
    
    // Validasi data wajib
    if (empty($nama_stasiun) || empty($alamat) || empty($latitude) || empty($longitude) || 
        $kapasitas <= 0 || $jumlah_slot <= 0 || $tarif < 0 || empty($jam_operasional)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => '⚠️ Semua field wajib diisi dengan benar!'
        ];
        header('Location: tambah_stasiun.php');
        exit;
    }
    
    // Validasi koordinat
    if (!is_numeric($latitude) || !is_numeric($longitude)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => '⚠️ Koordinat tidak valid!'
        ];
        header('Location: tambah_stasiun.php');
        exit;
    }
    
    if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => '⚠️ Koordinat di luar jangkauan yang valid!'
        ];
        header('Location: tambah_stasiun.php');
        exit;
    }
    
    try {
        // Cek dulu apakah kolom tambahan ada di tabel
        $checkColumns = $koneksi->query("SHOW COLUMNS FROM stasiun_pengisian LIKE 'jumlah_slot'");
        $hasExtraColumns = $checkColumns->rowCount() > 0;
        
        if ($hasExtraColumns) {
            // Insert dengan kolom lengkap
            $stmt = $koneksi->prepare("
                INSERT INTO stasiun_pengisian 
                (id_mitra, nama_stasiun, alamat, latitude, longitude, kapasitas, 
                 jumlah_slot, tarif_per_kwh, jam_operasional, fasilitas,
                 status_operasional, status, created_at) 
                VALUES 
                (:id_mitra, :nama_stasiun, :alamat, :latitude, :longitude, :kapasitas, 
                 :jumlah_slot, :tarif, :jam_operasional, :fasilitas,
                 'nonaktif', 'pending', NOW())
            ");
            
            $result = $stmt->execute([
                ':id_mitra' => $id_mitra,
                ':nama_stasiun' => $nama_stasiun,
                ':alamat' => $alamat,
                ':latitude' => $latitude,
                ':longitude' => $longitude,
                ':kapasitas' => $kapasitas,
                ':jumlah_slot' => $jumlah_slot,
                ':tarif' => $tarif,
                ':jam_operasional' => $jam_operasional,
                ':fasilitas' => $fasilitas
            ]);
        } else {
            // Insert hanya kolom dasar (backward compatibility)
            $stmt = $koneksi->prepare("
                INSERT INTO stasiun_pengisian 
                (id_mitra, nama_stasiun, alamat, latitude, longitude, kapasitas, 
                 status_operasional, status, created_at) 
                VALUES 
                (:id_mitra, :nama_stasiun, :alamat, :latitude, :longitude, :kapasitas, 
                 'nonaktif', 'pending', NOW())
            ");
            
            $result = $stmt->execute([
                ':id_mitra' => $id_mitra,
                ':nama_stasiun' => $nama_stasiun,
                ':alamat' => $alamat,
                ':latitude' => $latitude,
                ':longitude' => $longitude,
                ':kapasitas' => $kapasitas
            ]);
        }
        
        if ($result) {
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => '✅ Pengajuan stasiun berhasil dikirim! Menunggu verifikasi admin (1-3 hari kerja).'
            ];
            
            // Redirect ke dashboard atau halaman stasiun
            header('Location: dashboard.php');
        } else {
            throw new Exception('Gagal menyimpan data');
        }
        
        exit;
        
    } catch (PDOException $e) {
        error_log("Error insert stasiun: " . $e->getMessage());
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => '❌ Gagal menyimpan data: ' . $e->getMessage()
        ];
        header('Location: tambah_stasiun.php');
        exit;
    }
    
} else {
    // Jika bukan POST, redirect ke form
    header('Location: tambah_stasiun.php');
    exit;
}
?>