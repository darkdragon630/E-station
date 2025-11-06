<?php
require_once "../config/koneksi.php";

if (isset($_GET['token']) && isset($_GET['role'])) {
    $token = $_GET['token'];
    $role = $_GET['role'];
    
    try {
        if ($role == 'pengendara') {
            // Cari user berdasarkan token
            $stmt = $koneksi->prepare("SELECT * FROM pengendara WHERE verifikasi_token = ? AND status_akun = 'nonaktif'");
            $stmt->execute([$token]);
            
            if ($stmt->rowCount() == 1) {
                // Update status menjadi aktif dan hapus token
                $stmt = $koneksi->prepare("UPDATE pengendara SET status_akun = 'aktif', verifikasi_token = NULL WHERE verifikasi_token = ?");
                $stmt->execute([$token]);
                
                header("Location: login.php?success=email_verified");
                exit();
            } else {
                // Cek apakah sudah verified sebelumnya
                $stmt = $koneksi->prepare("SELECT * FROM pengendara WHERE verifikasi_token = ? AND status_akun = 'aktif'");
                $stmt->execute([$token]);
                
                if ($stmt->rowCount() == 1) {
                    header("Location: login.php?error=already_verified");
                    exit();
                }
                
                header("Location: login.php?error=invalid_token");
                exit();
            }
            
        } else if ($role == 'mitra') {
            // Cari mitra berdasarkan token
            $stmt = $koneksi->prepare("SELECT * FROM mitra WHERE verifikasi_token = ? AND email_terverifikasi = 0");
            $stmt->execute([$token]);
            
            if ($stmt->rowCount() == 1) {
                // Update email_terverifikasi menjadi 1 dan hapus token
                $stmt = $koneksi->prepare("UPDATE mitra SET email_terverifikasi = 1, verifikasi_token = NULL WHERE verifikasi_token = ?");
                $stmt->execute([$token]);
                
                header("Location: login.php?success=email_verified");
                exit();
            } else {
                // Cek apakah sudah verified sebelumnya
                $stmt = $koneksi->prepare("SELECT * FROM mitra WHERE verifikasi_token = ? AND email_terverifikasi = 1");
                $stmt->execute([$token]);
                
                if ($stmt->rowCount() == 1) {
                    header("Location: login.php?error=already_verified");
                    exit();
                }
                
                header("Location: login.php?error=invalid_token");
                exit();
            }
        } else {
            header("Location: login.php?error=invalid_role");
            exit();
        }
        
    } catch (PDOException $e) {
        error_log("Verification error: " . $e->getMessage());
        header("Location: login.php?error=verification_failed");
        exit();
    }
    
} else {
    header("Location: login.php");
    exit();
}
?>