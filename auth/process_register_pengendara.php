<?php
session_start();
require_once "../config/koneksi.php";

// Import PHPMailer
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $no_telepon = isset($_POST['no_telepon']) ? trim($_POST['no_telepon']) : '';
    $alamat = isset($_POST['alamat']) ? trim($_POST['alamat']) : '';
    
    // Validasi input kosong
    if (empty($nama) || empty($email) || empty($password)) {
        header("Location: auth.php?error=empty_fields");
        exit();
    }
    
    // Validasi email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: auth.php?error=invalid_email");
        exit();
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate token verifikasi (random 32 karakter)
    $verification_token = bin2hex(random_bytes(16));
    
    try {
        // Cek email sudah terdaftar atau belum
        $stmt = $koneksi->prepare("SELECT * FROM pengendara WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            header("Location: auth.php?error=email_exists");
            exit();
        }
        
        // Insert ke database dengan status 'nonaktif'
        $stmt = $koneksi->prepare("INSERT INTO pengendara (nama, email, password, no_telepon, alamat, status_akun, verifikasi_token) VALUES (?, ?, ?, ?, ?, 'nonaktif', ?)");
        $stmt->execute([$nama, $email, $hashed_password, $no_telepon, $alamat, $verification_token]);
        
        $user_id = $koneksi->lastInsertId();
        
        // ===== KIRIM EMAIL VERIFIKASI =====
        $mail = new PHPMailer(true);
        
        try {
            // Konfigurasi SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'luminark.dev@gmail.com';
            $mail->Password = 'kwlz veim ywyp urqw';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            // Set charset UTF-8
            $mail->CharSet = 'UTF-8';
            
            // Pengirim dan penerima
            $mail->setFrom('luminark.dev@gmail.com', 'E-Station');
            $mail->addAddress($email, $nama);
            
            // Konten email
            $mail->isHTML(true);
            $mail->Subject = 'Verifikasi Email - Registrasi Pengendara E-Station';
            
            //  PERBAIKAN: Menggunakan domain hosting dan file auth.php
            $verification_link = "https://e-station.wasmer.app/auth/verify_email.php?token=" . $verification_token . "&role=pengendara";
            
            $mail->Body = "
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
                        .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
                        .button { display: inline-block; background: #4CAF50; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                        .footer { text-align: center; padding: 20px; color: #777; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>Verifikasi Email Anda</h1>
                        </div>
                        <div class='content'>
                            <h2>Halo, {$nama}!</h2>
                            <p>Terima kasih telah mendaftar sebagai <strong>Pengendara E-Station</strong>.</p>
                            <p>Untuk menyelesaikan pendaftaran, silakan klik tombol di bawah untuk memverifikasi email Anda:</p>
                            <p style='text-align: center;'>
                                <a href='{$verification_link}' class='button'>Verifikasi Email Saya</a>
                            </p>
                            <p>Atau copy link berikut ke browser Anda:</p>
                            <p style='word-break: break-all; background: #fff; padding: 10px; border: 1px solid #ddd;'>{$verification_link}</p>
                            <p><strong>Link ini berlaku selama 24 jam.</strong></p>
                            <hr>
                            <p style='color: #777; font-size: 14px;'>Jika Anda tidak merasa mendaftar, abaikan email ini.</p>
                        </div>
                        <div class='footer'>
                            <p>&copy; 2024 E-Station. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            $mail->AltBody = "Halo {$nama},\n\nTerima kasih telah mendaftar sebagai Pengendara E-Station.\nSilakan verifikasi email Anda dengan mengunjungi link berikut:\n\n{$verification_link}\n\nLink berlaku selama 24 jam.";
            
            $mail->send();
            
            // redirect ke auth.php
            header("Location: auth.php?success=check_email");
            exit();
            
        } catch (Exception $e) {
            // Jika gagal kirim email, hapus pengendara dari database
            $stmt = $koneksi->prepare("DELETE FROM pengendara WHERE id_pengendara = ?");
            $stmt->execute([$user_id]);
            
            error_log("Email send failed: " . $mail->ErrorInfo);
            
            // Redirect ke auth.php
            header("Location: auth.php?error=email_failed");
            exit();
        }
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        
        // Redirect ke auth.php
        header("Location: auth.php?error=database_error");
        exit();
    }
    
} else {
    // Redirect ke auth.php jika akses langsung
    header("Location: auth.php");
    exit();
}
?>
