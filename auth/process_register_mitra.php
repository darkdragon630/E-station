<?php
// ===================================
// PRODUCTION MODE - Process Register Mitra
// ===================================

error_reporting(E_ALL);
ini_set('display_errors', '0'); // Production: hide errors from users
ini_set('log_errors', '1');

// Load PHP configuration (optional)
if (file_exists(__DIR__ . '/../config/php_config.php')) {
    require_once __DIR__ . '/../config/php_config.php';
}

session_start();

// Check required files
if (!file_exists(__DIR__ . "/../config/koneksi.php")) {
    error_log("CRITICAL: koneksi.php not found");
    die("Database configuration not found");
}

if (!file_exists(__DIR__ . "/send_verification_email.php")) {
    error_log("CRITICAL: send_verification_email.php not found");
    die("Email service not found");
}

require_once __DIR__ . "/../config/koneksi.php";
require_once __DIR__ . "/send_verification_email.php";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    try {
        // Validasi POST data exists
        if (!isset($_POST['nama_mitra']) || !isset($_POST['email']) || !isset($_POST['password'])) {
            throw new Exception("Missing required fields");
        }
        
        $nama = trim($_POST['nama_mitra']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $no_telepon = isset($_POST['no_telepon']) ? trim($_POST['no_telepon']) : '';
        $alamat = isset($_POST['alamat']) ? trim($_POST['alamat']) : '';
        
        error_log("📝 Registration attempt - Mitra: {$nama}, Email: {$email}");
        
        // Validasi input kosong
        if (empty($nama) || empty($email) || empty($password)) {
            error_log("❌ Empty fields detected");
            header("Location: auth.php?error=empty_fields");
            exit();
        }
        
        // Validasi email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("❌ Invalid email format: {$email}");
            header("Location: auth.php?error=invalid_email");
            exit();
        }
        
        // ✅ VALIDASI PASSWORD REQUIREMENTS
        if (strlen($password) < 8 || 
            !preg_match('/[a-z]/', $password) || 
            !preg_match('/[A-Z]/', $password) || 
            !preg_match('/[0-9]/', $password) || 
            !preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
            error_log("❌ Weak password");
            header("Location: auth.php?error=weak_password");
            exit();
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Generate token verifikasi (random 32 karakter)
        $verification_token = bin2hex(random_bytes(16));
        
        error_log("🔍 Checking existing email...");
        
        // Cek email sudah terdaftar atau belum
        $stmt = $koneksi->prepare("SELECT * FROM mitra WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            error_log("❌ Email already exists: {$email}");
            header("Location: auth.php?error=email_exists");
            exit();
        }

        error_log("🔍 Checking existing nama_mitra...");
        
        // Cek nama mitra sudah terdaftar atau belum
        $stmt = $koneksi->prepare("SELECT * FROM mitra WHERE nama_mitra = ?");
        $stmt->execute([$nama]);
        
        if ($stmt->rowCount() > 0) {
            error_log("❌ Nama mitra already exists: {$nama}");
            header("Location: auth.php?error=nama_mitra_exists");
            exit();
        }
        
        error_log("💾 Inserting to database...");
        
        // Insert ke database dengan status 'pending'
        $stmt = $koneksi->prepare("
            INSERT INTO mitra 
            (nama_mitra, email, password, no_telepon, alamat, status, email_terverifikasi, verifikasi_token, token_created_at) 
            VALUES (?, ?, ?, ?, ?, 'pending', 0, ?, NOW())
        ");
        
        $result = $stmt->execute([$nama, $email, $hashed_password, $no_telepon, $alamat, $verification_token]);
        
        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            error_log("❌ Database insert failed: " . print_r($errorInfo, true));
            throw new Exception("Database insert failed: " . $errorInfo[2]);
        }
        
        $user_id = $koneksi->lastInsertId();
        
        error_log("✅ Mitra registered successfully: ID={$user_id}, Email={$email}");
        
        // ===== KIRIM EMAIL VERIFIKASI =====
        error_log("📧 Attempting to send verification email...");
        
        $email_sent = sendVerificationEmail($email, $nama, $verification_token, 'mitra');
        
        if ($email_sent) {
            // Email berhasil dikirim
            error_log("✅ Verification email sent successfully to: {$email}");
            header("Location: auth.php?success=check_email");
            exit();
        } else {
            // Jika gagal kirim email, hapus mitra dari database
            error_log("❌ Failed to send verification email, rolling back registration");
            $stmt = $koneksi->prepare("DELETE FROM mitra WHERE id_mitra = ?");
            $stmt->execute([$user_id]);
            error_log("🔄 Rollback complete - mitra deleted");
            
            header("Location: auth.php?error=email_failed");
            exit();
        }
        
    } catch (PDOException $e) {
        error_log("❌ PDO Database error: " . $e->getMessage());
        error_log("Error code: " . $e->getCode());
        error_log("Stack trace: " . $e->getTraceAsString());
        header("Location: auth.php?error=database_error");
        exit();
    } catch (Exception $e) {
        error_log("❌ General error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        header("Location: auth.php?error=server_error");
        exit();
    }
    
} else {
    header("Location: auth.php");
    exit();
}
?>
