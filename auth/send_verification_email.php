<?php
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendVerificationEmail($email, $nama, $verification_token, $role) {
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
        
        // ===== OPTIMASI UNTUK MENGURANGI DELAY =====
        $mail->Timeout = 10; // Timeout connection (default 300 detik)
        $mail->SMTPKeepAlive = true; // Keep connection alive
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Set charset UTF-8
        $mail->CharSet = 'UTF-8';
        
        // Pengirim dan penerima
        $mail->setFrom('luminark.dev@gmail.com', 'E-Station');
        $mail->addAddress($email, $nama);
        
        // Konten email
        $mail->isHTML(true);
        $mail->Subject = 'Verifikasi Email - Registrasi ' . ucfirst($role) . ' E-Station';
        
        // Link verifikasi
        $verification_link = "https://e-station.wasmer.app/auth/verify_email.php?token=" . $verification_token . "&role=" . $role;
        
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
                        <p>Terima kasih telah mendaftar sebagai <strong>" . ucfirst($role) . " E-Station</strong>.</p>
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
                        <p>&copy; 2025 E-Station. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        $mail->AltBody = "Halo {$nama},\n\nTerima kasih telah mendaftar sebagai " . ucfirst($role) . " E-Station.\nSilakan verifikasi email Anda dengan mengunjungi link berikut:\n\n{$verification_link}\n\nLink berlaku selama 24 jam.";
        
        // Log waktu pengiriman
        $start_time = microtime(true);
        $mail->send();
        $end_time = microtime(true);
        $duration = round($end_time - $start_time, 2);
        
        error_log("Email sent to {$email} in {$duration} seconds");
        
        return true;
        
    } catch (Exception $e) {
        error_log("Email send failed to {$email}: " . $mail->ErrorInfo);
        return false;
    }
}
?>