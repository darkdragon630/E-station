<?php
require_once "../config/koneksi.php";

if (isset($_GET['token']) && isset($_GET['role'])) {
    $token = trim($_GET['token']);
    $role = trim($_GET['role']);
    
    // Validasi input
    if (empty($token) || empty($role)) {
        header("Location: login.php?error=invalid_request");
        exit();
    }
    
    try {
        if ($role == 'pengendara') {
            // Cari user berdasarkan token yang belum verified
            $stmt = $koneksi->prepare("
                SELECT id_pengendara, status_akun, token_created_at 
                FROM pengendara 
                WHERE verifikasi_token = ?
            ");
            $stmt->execute([$token]);
            
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Cek apakah sudah verified sebelumnya
                if ($user['status_akun'] == 'aktif') {
                    header("Location: login.php?error=already_verified");
                    exit();
                }
                
                // Validasi token expired (24 jam)
                if ($user['token_created_at']) {
                    $token_time = strtotime($user['token_created_at']);
                    $current_time = time();
                    $time_diff = ($current_time - $token_time) / 3600; // dalam jam
                    
                    if ($time_diff > 24) {
                        // Token expired, hapus token lama
                        $stmt = $koneksi->prepare("
                            UPDATE pengendara 
                            SET verifikasi_token = NULL, token_created_at = NULL 
                            WHERE id_pengendara = ?
                        ");
                        $stmt->execute([$user['id_pengendara']]);
                        
                        header("Location: login.php?error=token_expired");
                        exit();
                    }
                }
                
                // Token valid, update status menjadi aktif
                $stmt = $koneksi->prepare("
                    UPDATE pengendara 
                    SET status_akun = 'aktif', 
                        verifikasi_token = NULL, 
                        token_created_at = NULL 
                    WHERE verifikasi_token = ?
                ");
                $stmt->execute([$token]);
                
                header("Location: login.php?success=email_verified");
                exit();
                
            } else {
                // Token tidak ditemukan atau invalid
                header("Location: login.php?error=invalid_token");
                exit();
            }
            
        } else if ($role == 'mitra') {
            // Cari mitra berdasarkan token
            $stmt = $koneksi->prepare("
                SELECT id_mitra, email_terverifikasi, status, token_created_at 
                FROM mitra 
                WHERE verifikasi_token = ?
            ");
            $stmt->execute([$token]);
            
            if ($stmt->rowCount() == 1) {
                $mitra = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Cek apakah sudah verified sebelumnya
                if ($mitra['email_terverifikasi'] == 1) {
                    header("Location: login.php?error=already_verified");
                    exit();
                }
                
                // Validasi token expired (24 jam)
                if ($mitra['token_created_at']) {
                    $token_time = strtotime($mitra['token_created_at']);
                    $current_time = time();
                    $time_diff = ($current_time - $token_time) / 3600;
                    
                    if ($time_diff > 24) {
                        // Token expired, hapus token lama
                        $stmt = $koneksi->prepare("
                            UPDATE mitra 
                            SET verifikasi_token = NULL, token_created_at = NULL 
                            WHERE id_mitra = ?
                        ");
                        $stmt->execute([$mitra['id_mitra']]);
                        
                        header("Location: login.php?error=token_expired");
                        exit();
                    }
                }
                
                // Token valid, update email_terverifikasi dan status
                $stmt = $koneksi->prepare("
                    UPDATE mitra 
                    SET email_terverifikasi = 1, 
                        status = 'disetujui',
                        verifikasi_token = NULL, 
                        token_created_at = NULL 
                    WHERE verifikasi_token = ?
                ");
                $stmt->execute([$token]);
                
                header("Location: login.php?success=email_verified");
                exit();
                
            } else {
                // Token tidak ditemukan atau invalid
                header("Location: login.php?error=invalid_token");
                exit();
            }
            
        } else {
            // Role tidak valid
            header("Location: login.php?error=invalid_role");
            exit();
        }
        
    } catch (PDOException $e) {
        error_log("Verification error: " . $e->getMessage());
        header("Location: login.php?error=verification_failed");
        exit();
    }
    
} else {
    // Parameter tidak lengkap
    header("Location: login.php?error=invalid_request");
    exit();
}
?>