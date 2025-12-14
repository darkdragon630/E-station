<?php
/**
 * ===================================
 * Send Reset Password Email
 * ===================================
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Load environment variables
$env_file = __DIR__ . '/../.env';

if (file_exists($env_file)) {
    try {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
        error_log("✅ .env file loaded for reset password email");
    } catch (Exception $e) {
        error_log("⚠️ Failed to load .env: " . $e->getMessage());
    }
}

// Helper function to get environment variables
function getEnvVar($key) {
    if (isset($_ENV[$key]) && !empty($_ENV[$key])) {
        return $_ENV[$key];
    }
    
    $value = getenv($key);
    if ($value !== false && !empty($value)) {
        return $value;
    }
    
    if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
        return $_SERVER[$key];
    }
    
    return null;
}

function sendResetPasswordEmail($email, $nama, $reset_token, $role) {
    // Get environment variables
    $smtp_host = getEnvVar('BREVO_SMTP_HOST');
    $smtp_user = getEnvVar('BREVO_SMTP_USER');
    $smtp_pass = getEnvVar('BREVO_SMTP_PASS');
    $from_email = getEnvVar('BREVO_FROM_EMAIL');
    $from_name = getEnvVar('BREVO_FROM_NAME');
    $app_url = getEnvVar('APP_URL');
    
    // Validate environment variables
    if (empty($smtp_host) || empty($smtp_user) || empty($smtp_pass)) {
        error_log("❌ Cannot send reset email: Missing SMTP configuration");
        return false;
    }
    
    $mail = new PHPMailer(true);
    
    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_user;
        $mail->Password = $smtp_pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Debug mode - set 0 untuk production, 2 untuk testing
        $mail->SMTPDebug = 0;
        $mail->Debugoutput = function($str, $level) {
            error_log("SMTP [$level]: $str");
        };
        
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        $mail->Timeout = 60;
        $mail->CharSet = 'UTF-8';
        
        // From & To
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($email, $nama);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Reset Password - E-Station';
        
        // Build reset link
        $app_url = rtrim($app_url, '/');
        $reset_link = "{$app_url}/auth/reset_password.php?token={$reset_token}";
        
        error_log("📧 Preparing reset password email for: {$email} (role: {$role})");
        error_log("🔗 Reset link: {$reset_link}");
        
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <style>
                    body { 
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                        line-height: 1.6; 
                        color: #333; 
                        background: #f4f4f4; 
                        margin: 0; 
                        padding: 0; 
                    }
                    .container { 
                        max-width: 600px; 
                        margin: 20px auto; 
                        background: white; 
                        border-radius: 15px; 
                        overflow: hidden; 
                        box-shadow: 0 4px 20px rgba(0,0,0,0.1); 
                    }
                    .header { 
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                        color: white; 
                        padding: 40px 30px; 
                        text-align: center; 
                    }
                    .header h1 { 
                        margin: 0; 
                        font-size: 28px; 
                        font-weight: 700; 
                    }
                    .header p { 
                        margin: 10px 0 0 0; 
                        font-size: 16px; 
                        opacity: 0.95; 
                    }
                    .content { 
                        padding: 40px 30px; 
                    }
                    .content h2 { 
                        color: #333; 
                        font-size: 22px; 
                        margin-top: 0; 
                        font-weight: 600; 
                    }
                    .content p { 
                        color: #666; 
                        line-height: 1.8; 
                        margin: 15px 0; 
                    }
                    .button-container { 
                        text-align: center; 
                        margin: 35px 0; 
                    }
                    .button { 
                        display: inline-block; 
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                        color: white !important; 
                        padding: 16px 45px; 
                        text-decoration: none; 
                        border-radius: 50px; 
                        font-weight: 600; 
                        font-size: 16px; 
                        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3); 
                        transition: all 0.3s ease; 
                    }
                    .button:hover { 
                        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4); 
                        transform: translateY(-2px); 
                    }
                    .link-box { 
                        background: #f9f9f9; 
                        padding: 18px; 
                        border: 1px solid #e0e0e0; 
                        border-radius: 10px; 
                        word-break: break-all; 
                        font-size: 13px; 
                        color: #666; 
                        margin: 25px 0; 
                        font-family: 'Courier New', monospace; 
                    }
                    .warning-box { 
                        background: #fff3cd; 
                        border-left: 4px solid #ffc107; 
                        padding: 18px; 
                        margin: 25px 0; 
                        border-radius: 5px; 
                    }
                    .warning-box strong { 
                        color: #856404; 
                        display: block; 
                        margin-bottom: 8px; 
                        font-size: 15px; 
                    }
                    .warning-box p { 
                        color: #856404; 
                        margin: 5px 0; 
                        font-size: 14px; 
                    }
                    .security-tips { 
                        background: #e3f2fd; 
                        border-left: 4px solid #2196F3; 
                        padding: 18px; 
                        margin: 25px 0; 
                        border-radius: 5px; 
                    }
                    .security-tips strong { 
                        color: #1976D2; 
                        display: block; 
                        margin-bottom: 10px; 
                        font-size: 15px; 
                    }
                    .security-tips ul { 
                        margin: 8px 0; 
                        padding-left: 20px; 
                    }
                    .security-tips li { 
                        color: #1976D2; 
                        margin-bottom: 6px; 
                        font-size: 14px; 
                    }
                    .footer { 
                        background: #f9f9f9; 
                        padding: 25px; 
                        text-align: center; 
                        color: #999; 
                        font-size: 13px; 
                        border-top: 1px solid #e0e0e0; 
                    }
                    .footer p { 
                        margin: 5px 0; 
                    }
                    .divider { 
                        border: none; 
                        border-top: 1px solid #e0e0e0; 
                        margin: 30px 0; 
                    }
                    @media only screen and (max-width: 600px) {
                        .container { 
                            margin: 10px; 
                            border-radius: 10px; 
                        }
                        .header { 
                            padding: 30px 20px; 
                        }
                        .header h1 { 
                            font-size: 24px; 
                        }
                        .content { 
                            padding: 30px 20px; 
                        }
                        .content h2 { 
                            font-size: 20px; 
                        }
                        .button { 
                            padding: 14px 35px; 
                            font-size: 15px; 
                        }
                        .link-box { 
                            font-size: 12px; 
                            padding: 15px; 
                        }
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>🔐 Reset Password</h1>
                        <p>Permintaan Reset Password E-Station</p>
                    </div>
                    
                    <div class='content'>
                        <h2>Halo, {$nama}! 👋</h2>
                        
                        <p>Kami menerima permintaan untuk mereset password akun <strong>" . ucfirst($role) . "</strong> Anda di E-Station.</p>
                        
                        <p>Klik tombol di bawah ini untuk membuat password baru:</p>
                        
                        <div class='button-container'>
                            <a href='{$reset_link}' class='button'>🔑 Reset Password Saya</a>
                        </div>
                        
                        <p>Atau copy link berikut ke browser Anda:</p>
                        <div class='link-box'>{$reset_link}</div>
                        
                        <div class='warning-box'>
                            <strong>⏰ Perhatian Penting:</strong>
                            <p>• Link ini hanya berlaku selama <strong>1 jam</strong></p>
                            <p>• Link hanya dapat digunakan <strong>satu kali</strong></p>
                            <p>• Jangan bagikan link ini kepada siapa pun</p>
                        </div>
                        
                        <div class='security-tips'>
                            <strong>🛡️ Tips Keamanan Password:</strong>
                            <ul>
                                <li>Minimal 8 karakter</li>
                                <li>Kombinasi huruf besar, kecil, angka & simbol</li>
                                <li>Hindari password yang mudah ditebak</li>
                                <li>Jangan gunakan password yang sama dengan akun lain</li>
                                <li>Gunakan password manager untuk keamanan maksimal</li>
                            </ul>
                        </div>
                        
                        <hr class='divider'>
                        
                        <p style='color: #999; font-size: 14px; font-style: italic;'>
                            ℹ️ Jika Anda tidak meminta reset password, abaikan email ini dan pastikan akun Anda aman. 
                            Password Anda tidak akan berubah tanpa konfirmasi.
                        </p>
                    </div>
                    
                    <div class='footer'>
                        <p style='margin: 0; font-weight: 600; color: #666;'>&copy; 2025 E-Station. All rights reserved.</p>
                        <p style='margin: 10px 0 0 0;'>Layanan Pengisian Kendaraan Listrik</p>
                        <p style='margin: 10px 0 0 0; font-size: 12px;'>
                            Email ini dikirim otomatis, mohon tidak membalas.
                        </p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        $mail->AltBody = "Halo {$nama},\n\nKami menerima permintaan untuk mereset password akun " . ucfirst($role) . " Anda di E-Station.\n\nSilakan reset password Anda dengan mengunjungi link berikut:\n{$reset_link}\n\nLink berlaku selama 1 jam dan hanya dapat digunakan satu kali.\n\nTIPS KEAMANAN PASSWORD:\n- Minimal 8 karakter\n- Kombinasi huruf besar, kecil, angka & simbol\n- Hindari password yang mudah ditebak\n- Jangan gunakan password yang sama dengan akun lain\n\nJika Anda tidak meminta reset password, abaikan email ini.\n\n---\nE-Station\nLayanan Pengisian Kendaraan Listrik";
        
        // Send email
        $start_time = microtime(true);
        error_log("📧 Sending reset password email to: {$email}");
        
        $mail->send();
        
        $end_time = microtime(true);
        $duration = round($end_time - $start_time, 2);
        
        error_log("✅ Reset password email sent successfully to {$email} in {$duration}s");
        
        return true;
        
    } catch (Exception $e) {
        error_log("❌ Reset email failed to {$email}: {$mail->ErrorInfo}");
        error_log("Exception: {$e->getMessage()}");
        return false;
    } finally {
        $mail->smtpClose();
    }
}
?>