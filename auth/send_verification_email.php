<?php
/**
 * ===================================
 * Send Verification Email
 * FIXED: Wasmer Environment Variables
 * ===================================
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// ========================================
// LOAD ENVIRONMENT VARIABLES
// ========================================
// Di localhost: Load dari .env file
// Di Wasmer: Environment variables diakses via getenv()
// ========================================

$env_file = __DIR__ . '/../.env';

if (file_exists($env_file)) {
    // Localhost - Load dari .env file
    try {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
        error_log("✅ .env file loaded from: {$env_file}");
    } catch (Exception $e) {
        error_log("⚠️ Failed to load .env: " . $e->getMessage());
    }
}

// ========================================
// HELPER FUNCTION: Get Environment Variable
// Compatible dengan localhost (.env) dan Wasmer (secrets)
// ========================================
function getEnvVar($key) {
    // Try $_ENV first (dari .env)
    if (isset($_ENV[$key]) && !empty($_ENV[$key])) {
        return $_ENV[$key];
    }
    
    // Try getenv() (dari system/Wasmer secrets)
    $value = getenv($key);
    if ($value !== false && !empty($value)) {
        return $value;
    }
    
    // Try $_SERVER
    if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
        return $_SERVER[$key];
    }
    
    return null;
}

// Validate required environment variables
$required_vars = [
    'BREVO_SMTP_HOST',
    'BREVO_SMTP_USER',
    'BREVO_SMTP_PASS',
    'BREVO_FROM_EMAIL',
    'BREVO_FROM_NAME',
    'APP_URL'
];

$missing_vars = [];
foreach ($required_vars as $var) {
    $value = getEnvVar($var);
    if ($value === null) {
        error_log("❌ CRITICAL: Missing environment variable: {$var}");
        $missing_vars[] = $var;
    } else {
        error_log("✅ Found: {$var} = " . substr($value, 0, 10) . "...");
    }
}

if (!empty($missing_vars)) {
    error_log("❌ Missing variables: " . implode(', ', $missing_vars));
}

function sendVerificationEmail($email, $nama, $verification_token, $role) {
    // Get environment variables using helper function
    $smtp_host = getEnvVar('BREVO_SMTP_HOST');
    $smtp_user = getEnvVar('BREVO_SMTP_USER');
    $smtp_pass = getEnvVar('BREVO_SMTP_PASS');
    $from_email = getEnvVar('BREVO_FROM_EMAIL');
    $from_name = getEnvVar('BREVO_FROM_NAME');
    $app_url = getEnvVar('APP_URL');
    
    // Validate environment variables
    if (empty($smtp_host) || empty($smtp_user) || empty($smtp_pass)) {
        error_log("❌ Cannot send email: Missing SMTP configuration");
        error_log("SMTP Host: " . ($smtp_host ?: 'MISSING'));
        error_log("SMTP User: " . ($smtp_user ?: 'MISSING'));
        error_log("SMTP Pass: " . ($smtp_pass ? 'SET' : 'MISSING'));
        return false;
    }
    
    $mail = new PHPMailer(true);
    
    try {
        // ========================================
        // SECURE SMTP CONFIGURATION
        // ========================================
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_user;
        $mail->Password = $smtp_pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Debug (set 0 di production)
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
        $mail->Subject = 'Verifikasi Email - Registrasi ' . ucfirst($role) . ' E-Station';
        
        // Build verification link
        $app_url = rtrim($app_url, '/');
        $verification_link = "{$app_url}/auth/verify_email.php?token={$verification_token}&role={$role}";
        
        error_log("📧 Preparing email for: {$email} (role: {$role})");
        error_log("🔗 Verification link: {$verification_link}");
        
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; margin: 0; padding: 0; }
                    .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                    .header { background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); color: white; padding: 30px 20px; text-align: center; }
                    .header h1 { margin: 0; font-size: 24px; }
                    .content { padding: 40px 30px; }
                    .content h2 { color: #333; font-size: 20px; margin-top: 0; }
                    .content p { color: #666; line-height: 1.8; }
                    .button { display: inline-block; background: #4CAF50; color: white !important; padding: 15px 40px; text-decoration: none; border-radius: 5px; margin: 25px 0; font-weight: bold; text-align: center; }
                    .button:hover { background: #45a049; }
                    .link-box { background: #f9f9f9; padding: 15px; border: 1px solid #e0e0e0; border-radius: 5px; word-break: break-all; font-size: 13px; color: #666; margin: 20px 0; }
                    .footer { background: #f9f9f9; padding: 20px; text-align: center; color: #999; font-size: 12px; border-top: 1px solid #e0e0e0; }
                    .note { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; color: #856404; font-size: 14px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>⚡ E-Station</h1>
                        <p style='margin: 10px 0 0 0; font-size: 16px;'>Verifikasi Email Anda</p>
                    </div>
                    <div class='content'>
                        <h2>Halo, {$nama}! 👋</h2>
                        <p>Terima kasih telah mendaftar sebagai <strong>" . ucfirst($role) . "</strong> di E-Station.</p>
                        <p>Untuk menyelesaikan pendaftaran, silakan verifikasi email Anda dengan klik tombol di bawah:</p>
                        
                        <div style='text-align: center;'>
                            <a href='{$verification_link}' class='button'>✅ Verifikasi Email Saya</a>
                        </div>
                        
                        <p>Atau copy link berikut ke browser Anda:</p>
                        <div class='link-box'>{$verification_link}</div>
                        
                        <div class='note'>
                            <strong>⏰ Penting:</strong> Link ini berlaku selama 24 jam.
                        </div>
                        
                        <hr style='border: none; border-top: 1px solid #e0e0e0; margin: 30px 0;'>
                        
                        <p style='color: #999; font-size: 13px;'>
                            <em>Jika Anda tidak merasa mendaftar, abaikan email ini. Akun tidak akan dibuat tanpa verifikasi.</em>
                        </p>
                    </div>
                    <div class='footer'>
                        <p style='margin: 0;'>&copy; 2025 E-Station. All rights reserved.</p>
                        <p style='margin: 10px 0 0 0;'>Layanan Pengisian Kendaraan Listrik</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        $mail->AltBody = "Halo {$nama},\n\nTerima kasih telah mendaftar sebagai " . ucfirst($role) . " di E-Station.\n\nSilakan verifikasi email Anda dengan mengunjungi link berikut:\n{$verification_link}\n\nLink berlaku selama 24 jam.\n\nJika Anda tidak merasa mendaftar, abaikan email ini.\n\n---\nE-Station\nLayanan Pengisian Kendaraan Listrik";
        
        // Send
        $start_time = microtime(true);
        error_log("📧 Sending email to: {$email} (role: {$role})");
        
        $mail->send();
        
        $end_time = microtime(true);
        $duration = round($end_time - $start_time, 2);
        
        error_log("✅ Email sent successfully to {$email} in {$duration}s");
        
        return true;
        
    } catch (Exception $e) {
        error_log("❌ Email failed to {$email}: {$mail->ErrorInfo}");
        error_log("Exception: {$e->getMessage()}");
        error_log("Stack trace: " . $e->getTraceAsString());
        return false;
    } finally {
        $mail->smtpClose();
    }
}
?>
