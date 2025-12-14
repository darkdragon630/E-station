<?php
/**
 * Test Email Configuration
 * Gunakan file ini untuk mengecek apakah email SMTP berfungsi
 */

require_once __DIR__ . '/../helpers/send_reset_email.php';

// Test email - GANTI DENGAN EMAIL ANDA
$test_email = "burhanjepara41@gmail.com"; // Email tujuan test
$test_nama = "Burhan Jepara";
$test_token = bin2hex(random_bytes(32));
$test_role = "pengendara";

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Test Email Configuration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }
        .env-check {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🧪 Test Email Configuration</h1>
";

// Check environment variables
echo "<h2>1. Environment Variables Check</h2>";
echo "<div class='env-check'>";

$env_vars = [
    'BREVO_SMTP_HOST' => getEnvVar('BREVO_SMTP_HOST'),
    'BREVO_SMTP_USER' => getEnvVar('BREVO_SMTP_USER'),
    'BREVO_SMTP_PASS' => getEnvVar('BREVO_SMTP_PASS') ? '***SET***' : 'NOT SET',
    'BREVO_FROM_EMAIL' => getEnvVar('BREVO_FROM_EMAIL'),
    'BREVO_FROM_NAME' => getEnvVar('BREVO_FROM_NAME'),
    'APP_URL' => getEnvVar('APP_URL')
];

$all_set = true;
foreach ($env_vars as $key => $value) {
    $status = ($value && $value !== 'NOT SET') ? '✅' : '❌';
    if (!$value || $value === 'NOT SET') $all_set = false;
    echo "<p><strong>{$status} {$key}:</strong> " . ($value ?: 'NOT SET') . "</p>";
}

echo "</div>";

if (!$all_set) {
    echo "<div class='info-box error'>";
    echo "<strong>⚠️ Environment variables tidak lengkap!</strong><br>";
    echo "Pastikan file .env Anda sudah diisi dengan benar.";
    echo "</div>";
    echo "<h3>Contoh file .env:</h3>";
    echo "<pre>";
    echo "BREVO_SMTP_HOST=smtp-relay.brevo.com\n";
    echo "BREVO_SMTP_USER=your-email@example.com\n";
    echo "BREVO_SMTP_PASS=your-smtp-api-key\n";
    echo "BREVO_FROM_EMAIL=noreply@yourdomain.com\n";
    echo "BREVO_FROM_NAME=E-Station\n";
    echo "APP_URL=http://localhost/your-project\n";
    echo "</pre>";
    exit;
}

// Test send email
echo "<h2>2. Send Test Email</h2>";
echo "<div class='info-box'>";
echo "<p>📧 Mengirim email test ke: <strong>{$test_email}</strong></p>";
echo "<p>Mohon tunggu...</p>";
echo "</div>";

// Flush output
ob_flush();
flush();

$result = sendResetPasswordEmail($test_email, $test_nama, $test_token, $test_role);

if ($result) {
    echo "<div class='info-box success'>";
    echo "<h3>✅ Email Berhasil Dikirim!</h3>";
    echo "<p>Silakan cek inbox email <strong>{$test_email}</strong></p>";
    echo "<p>Jika tidak ada, cek folder spam/junk.</p>";
    echo "</div>";
} else {
    echo "<div class='info-box error'>";
    echo "<h3>❌ Email Gagal Dikirim</h3>";
    echo "<p>Cek error log untuk detail lebih lanjut.</p>";
    echo "<p>Pastikan:</p>";
    echo "<ul>";
    echo "<li>SMTP credentials benar</li>";
    echo "<li>Port 587 tidak diblok firewall</li>";
    echo "<li>API key Brevo masih aktif</li>";
    echo "<li>Email pengirim sudah diverifikasi di Brevo</li>";
    echo "</ul>";
    echo "</div>";
}

// Check error log
echo "<h2>3. Recent Error Logs</h2>";
$error_log_file = __DIR__ . '/../auth/error_log.txt';
if (file_exists($error_log_file)) {
    $logs = file_get_contents($error_log_file);
    $recent_logs = implode("\n", array_slice(explode("\n", $logs), -20));
    echo "<pre>{$recent_logs}</pre>";
} else {
    echo "<p>No error log file found.</p>";
}

echo "<h2>4. PHP Mail Configuration</h2>";
echo "<pre>";
echo "SMTP: " . ini_get('SMTP') . "\n";
echo "smtp_port: " . ini_get('smtp_port') . "\n";
echo "sendmail_from: " . ini_get('sendmail_from') . "\n";
echo "</pre>";

echo "<a href='../auth/lupa_password.php' class='button'>← Kembali ke Lupa Password</a>";

echo "
    </div>
</body>
</html>";
?>