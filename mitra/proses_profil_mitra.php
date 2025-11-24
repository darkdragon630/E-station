<?php
session_start();
require_once '../config/koneksi.php';
require_once '../pesan/alerts.php';

// Cek authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mitra') {
    header("Location: ../auth/login.php");
    exit;
}

// Cek METHOD POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: edit_profile.php");
    exit;
}

// Ambil data dari form
$id_mitra = $_SESSION['user_id'];
$nama_mitra = isset($_POST['nama_mitra']) ? trim($_POST['nama_mitra']) : '';
$no_telepon = isset($_POST['no_telepon']) ? trim($_POST['no_telepon']) : '';
$alamat = isset($_POST['alamat']) ? trim($_POST['alamat']) : '';
$password_baru = isset($_POST['password_baru']) ? trim($_POST['password_baru']) : '';
$konfirmasi_password = isset($_POST['konfirmasi_password']) ? trim($_POST['konfirmasi_password']) : '';

// Validasi input
// 1. Validasi nama_mitra wajib diisi
if (empty($nama_mitra)) {
    set_error_handler('error', 'Nama mitra tidak boleh kosong!');
    header("Location: edit_profile.php");
    exit;
}

// 2. Validasi alamat wajib diisi
if (empty($alamat)) {
    set_error_handler('error', 'Alamat mitra tidak boleh kosong!');
    header("Location: edit_profil.php");
    exit;
}

// 3. Validasi format nomor telepon (opsional, hanya jika diisi)
if (!empty($no_telepon)) {
     // Format: 08xxxxxxxxxx atau 62xxxxxxxxxx
    if (!preg_match('/^(08|62)[0-9]{9,12}$/', $no_telepon)) {
        set_error_handler('error', 'Format nomor telepon tidak valid! Contoh: 081234567890');
        header("Location: edit_profile.php");
        exit;
    }
}

// 4. Validasi password (hanya jika diisi)
if (!empty($password_baru)) {
    // Minimal 8 karakter
    if (strlen($password_baru) < 8) {
        set_flash_message('error', 'Password minimal 8 karakter!');
        header("Location: edit_profile.php");
        exit;
    }
    
    // Cek kesamaan password dengan konfirmasi
    if ($password_baru !== $konfirmasi_password) {
        set_flash_message('error', 'Konfirmasi password tidak sama!');
        header("Location: edit_profile.php");
        exit;
    }
}

// Proses update ke database
try {
    // Jika password TIDAK diubah (kosong)
    if (empty($password_baru)) {
        $stmt = $koneksi->prepare("UPDATE mitra SET nama_mitra = ?, no_telepon = ?, alamat = ? WHERE id_mitra = ?");
        $stmt->execute([$nama_mitra, $no_telepon, $alamat, $id_mitra]);
    } 
    // Jika password DIUBAH (ada isinya)
    else {
        $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $stmt = $koneksi->prepare("UPDATE mitra SET nama_mitra = ?, no_telepon = ?, alamat = ?, password = ? WHERE id_mitra = ?");
        $stmt->execute([$nama_mitra, $no_telepon, $alamat, $password_hash, $id_mitra]);
    }
    
    // Update session nama (agar navbar berubah)
    $_SESSION['nama'] = $nama_mitra;
    
    // Set pesan sukses
    set_flash_message('success', 'Profil berhasil diperbarui!');
    header("Location: edit_profile.php");
    exit;
    
} catch (PDOException $e) {
    set_error_handler('error', 'Gagal memperbarui profil: ' . $e->getMessage());
    header("Location: edit_profile.php");
    exit;
}
?>