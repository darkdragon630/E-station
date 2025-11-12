<?php
// memulai sesi
session_start();
// menghubungkan ke database
require_once "../config/koneksi.php";

// menangani data dari form login
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $email = trim($_POST['email']); 
    $password = trim($_POST['password']);
    
    // Validasi: Field kosong
    if (empty($email) || empty($password)) {
        header("Location: login.php?error=empty_fields");
        exit();
    }
    
    $login_berhasil = false;

    // cek apakah admin ada di database
    $stmt = $koneksi->prepare("SELECT * FROM admin WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        // verifikasi password
        if (password_verify($password, $user['password'])) {
            // set session
            $_SESSION['user_id'] = $user['id_admin'];
            $_SESSION['nama'] = $user['nama_admin'];
            $_SESSION['role'] = 'admin';
            // arahkan ke dashboard admin
            header("Location: ../admin/dashboard.php?success=login");
            exit();
        }
    }
    
    // cek apakah pengendara ada di database
    $stmt = $koneksi->prepare("SELECT * FROM pengendara WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        // verifikasi password
        if (password_verify($password, $user['password'])) {
            // cek apakah email sudah diverifikasi
            if (isset($user['status_akun']) && $user['status_akun'] == 'nonaktif') {
                header("Location: login.php?error=email_not_verified");
                exit();
            }
            // set session
            $_SESSION['user_id'] = $user['id_pengendara'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = 'pengendara';
            // arahkan ke dashboard pengendara
            header("Location: ../pengendara/dashboard.php?success=login");
            exit();
        }
    }
    
    // cek apakah mitra ada di database
    $stmt = $koneksi->prepare("SELECT * FROM mitra WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        // verifikasi password
        if (password_verify($password, $user['password'])) {
            // ✅ PERBAIKAN: CEK EMAIL TERVERIFIKASI
            if (isset($user['email_terverifikasi']) && $user['email_terverifikasi'] == 0) {
                header("Location: login.php?error=email_not_verified");
                exit();
            }
            
            // ✅ PERBAIKAN: CEK STATUS AKUN MITRA
            if (isset($user['status'])) {
                if ($user['status'] == 'pending') {
                    header("Location: login.php?error=account_pending");
                    exit();
                }
                
                if ($user['status'] == 'ditolak') {
                    header("Location: login.php?error=account_rejected");
                    exit();
                }
                
                if ($user['status'] != 'disetujui') {
                    header("Location: login.php?error=account_inactive");
                    exit();
                }
            }
            
            // set session
            $_SESSION['user_id'] = $user['id_mitra'];
            $_SESSION['nama'] = $user['nama_mitra'];
            $_SESSION['role'] = 'mitra';
            // arahkan ke dashboard mitra
            header("Location: ../mitra/dashboard.php?success=login");
            exit();
        }
    }
    
    // jika gagal, kembali ke halaman login dengan pesan error
    header("Location: login.php?error=invalid_credentials");
    exit();
    
} else {
    // Jika bukan POST request, redirect ke halaman login
    header("Location: login.php");
    exit();
}
?>