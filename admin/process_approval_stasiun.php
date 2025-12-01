<?php
session_start();
require_once '../config/koneksi.php';

// Cek authentication admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_stasiun = intval($_POST['id_stasiun']);
    $action = $_POST['action'];
    
    if (empty($id_stasiun) || empty($action)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Data tidak valid!'
        ];
        header('Location: approval_stasiun.php');
        exit;
    }
    
    try {
        // Check if mitra table exists
        $checkTable = $koneksi->query("SHOW TABLES LIKE 'mitra'")->rowCount();
        
        if ($checkTable > 0) {
            // Ambil data stasiun dan mitra dari tabel mitra
            $stmt = $koneksi->prepare("
                SELECT s.*, m.nama_mitra, m.email, m.no_telepon
                FROM stasiun_pengisian s
                LEFT JOIN mitra m ON s.id_mitra = m.id_mitra
                WHERE s.id_stasiun = :id_stasiun
            ");
        } else {
            // Ambil data stasiun dan mitra dari tabel users
            $stmt = $koneksi->prepare("
                SELECT s.*, u.nama as nama_mitra, u.email, u.no_telepon
                FROM stasiun_pengisian s
                LEFT JOIN users u ON s.id_mitra = u.id
                WHERE s.id_stasiun = :id_stasiun
            ");
        }
        
        $stmt->execute([':id_stasiun' => $id_stasiun]);
        $stasiun = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$stasiun) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => 'Stasiun tidak ditemukan!'
            ];
            header('Location: approval_stasiun.php');
            exit;
        }
        
        if ($action === 'approve') {
            // Approve - ubah status jadi disetujui dan aktif
            $updateStmt = $koneksi->prepare("
                UPDATE stasiun_pengisian 
                SET status_operasional = 'aktif',
                    status = 'disetujui',
                    approved_by = :admin_id,
                    approved_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id_stasiun = :id_stasiun
            ");
            $updateStmt->execute([
                ':id_stasiun' => $id_stasiun,
                ':admin_id' => $_SESSION['user_id']
            ]);
            
            // Log aktivitas jika tabel ada
            try {
                $logStmt = $koneksi->prepare("
                    INSERT INTO log_aktivitas (id_user, aktivitas, keterangan, created_at)
                    VALUES (:id_user, :aktivitas, :keterangan, CURRENT_TIMESTAMP)
                ");
                $logStmt->execute([
                    ':id_user' => $_SESSION['user_id'],
                    ':aktivitas' => 'approve_stasiun',
                    ':keterangan' => "Menyetujui stasiun: {$stasiun['nama_stasiun']} (ID: {$id_stasiun})"
                ]);
            } catch (PDOException $e) {
                // Log table might not exist, continue anyway
                error_log("Log aktivitas error: " . $e->getMessage());
            }
            
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => "✅ Stasiun \"{$stasiun['nama_stasiun']}\" berhasil disetujui dan diaktifkan!"
            ];
            
        } elseif ($action === 'reject') {
            $reason = trim($_POST['reason'] ?? '');
            
            if (empty($reason)) {
                $_SESSION['alert'] = [
                    'type' => 'danger',
                    'message' => 'Alasan penolakan harus diisi!'
                ];
                header('Location: approval_stasiun.php');
                exit;
            }
            
            // Reject - ubah status jadi ditolak
            $updateStmt = $koneksi->prepare("
                UPDATE stasiun_pengisian 
                SET status_operasional = 'nonaktif',
                    status = 'ditolak',
                    alasan_penolakan = :reason,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id_stasiun = :id_stasiun
            ");
            $updateStmt->execute([
                ':id_stasiun' => $id_stasiun,
                ':reason' => $reason
            ]);
            
            // Log aktivitas jika tabel ada
            try {
                $logStmt = $koneksi->prepare("
                    INSERT INTO log_aktivitas (id_user, aktivitas, keterangan, created_at)
                    VALUES (:id_user, :aktivitas, :keterangan, CURRENT_TIMESTAMP)
                ");
                $logStmt->execute([
                    ':id_user' => $_SESSION['user_id'],
                    ':aktivitas' => 'reject_stasiun',
                    ':keterangan' => "Menolak stasiun: {$stasiun['nama_stasiun']} (ID: {$id_stasiun}). Alasan: {$reason}"
                ]);
            } catch (PDOException $e) {
                // Log table might not exist, continue anyway
                error_log("Log aktivitas error: " . $e->getMessage());
            }
            
            $_SESSION['alert'] = [
                'type' => 'warning',
                'message' => "⚠️ Stasiun \"{$stasiun['nama_stasiun']}\" ditolak. Alasan: {$reason}"
            ];
            
        } else {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => 'Aksi tidak valid!'
            ];
        }
        
        header('Location: approval_stasiun.php');
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Gagal memproses approval: ' . $e->getMessage()
        ];
        error_log("Approval error: " . $e->getMessage());
        header('Location: approval_stasiun.php');
        exit;
    }
    
} else {
    header('Location: approval_stasiun.php');
    exit;
}
?>