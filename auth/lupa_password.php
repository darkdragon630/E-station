<?php
session_start();
require_once '../config/koneksi.php';
require_once "../pesan/alerts.php";

// Load email helper jika ada
$email_helper_path = __DIR__ . '/../helpers/send_reset_email.php';
$email_available = file_exists($email_helper_path);

if ($email_available) {
    require_once $email_helper_path;
}

// Redirect jika sudah login
if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/dashboard.php");
    } elseif ($_SESSION['role'] === 'pengendara') {
        header("Location: ../pengendara/dashboard.php");
    } elseif ($_SESSION['role'] === 'mitra') {
        header("Location: ../mitra/dashboard.php");
    }
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => 'Email wajib diisi!'
        ];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => 'Format email tidak valid!'
        ];
    } else {
        try {
            // Cek email di database
            $found = false;
            $user_type = '';
            $user_name = '';
            $user_id = 0;
            
            // Cek di tabel admin
            $stmt = $koneksi->prepare("SELECT id_admin, nama_admin, email FROM admin WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $found = true;
                $user_type = 'admin';
                $user_name = $user['nama_admin'];
                $user_id = $user['id_admin'];
            }
            
            // Cek di tabel pengendara
            if (!$found) {
                $stmt = $koneksi->prepare("SELECT id_pengendara, nama, email FROM pengendara WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->rowCount() > 0) {
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    $found = true;
                    $user_type = 'pengendara';
                    $user_name = $user['nama'];
                    $user_id = $user['id_pengendara'];
                }
            }
            
            // Cek di tabel mitra
            if (!$found) {
                $stmt = $koneksi->prepare("SELECT id_mitra, nama_mitra, email FROM mitra WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->rowCount() > 0) {
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    $found = true;
                    $user_type = 'mitra';
                    $user_name = $user['nama_mitra'];
                    $user_id = $user['id_mitra'];
                }
            }
            
            if ($found) {
                // Generate token reset password
                $token = bin2hex(random_bytes(32));
                $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Simpan token ke session
                $_SESSION['reset_token'] = $token;
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_type'] = $user_type;
                $_SESSION['reset_user_id'] = $user_id;
                $_SESSION['reset_expiry'] = $token_expiry;
                
                // Kirim email
                $email_sent = false;
                if ($email_available && function_exists('sendResetPasswordEmail')) {
                    $email_sent = sendResetPasswordEmail($email, $user_name, $token, $user_type);
                }
                
                if ($email_sent) {
                    $_SESSION['alert'] = [
                        'type' => 'success',
                        'message' => 'Link reset password telah dikirim ke email Anda! Silakan cek inbox atau folder spam.'
                    ];
                } else {
                    $_SESSION['alert'] = [
                        'type' => 'error',
                        'message' => 'Gagal mengirim email. Silakan coba lagi atau hubungi administrator.'
                    ];
                }
            } else {
                // Untuk keamanan, tampilkan pesan yang sama
                $_SESSION['alert'] = [
                    'type' => 'success',
                    'message' => 'Jika email terdaftar, link reset password akan dikirim ke email Anda.'
                ];
            }
            
        } catch (PDOException $e) {
            $_SESSION['alert'] = [
                'type' => 'error',
                'message' => 'Terjadi kesalahan sistem. Silakan coba lagi!'
            ];
            error_log("Error lupa password: " . $e->getMessage());
        }
    }
    
    header("Location: lupa_password.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>E-Station | Lupa Password</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/alert.css">
    <link rel="icon" type="image/png" href="../images/Logo_1.png">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            overflow-x: hidden;
        }

        .container {
            width: 100%;
            max-width: 100%;
            padding: 2rem 1rem;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
            padding: 2rem;
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            text-decoration: none;
            margin-bottom: 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            gap: 0.8rem;
            transform: translateX(-4px);
        }

        .title {
            font-size: clamp(1.5rem, 4vw, 2rem);
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .subtitle {
            font-size: clamp(0.875rem, 2vw, 1rem);
            color: var(--text-muted);
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .illustration {
            text-align: center;
            margin: 1.5rem 0;
        }

        .illustration img {
            max-width: min(120px, 30vw);
            height: auto;
        }

        .login-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: clamp(0.875rem, 2vw, 1rem);
        }

        .login-form input {
            width: 100%;
            padding: 0.875rem 1rem;
            font-size: clamp(0.875rem, 2vw, 1rem);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 1.25rem;
            transition: all 0.3s ease;
        }

        .login-form input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 0.875rem;
            font-size: clamp(0.875rem, 2vw, 1rem);
            font-weight: 600;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .info-box {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1.5rem;
            font-size: clamp(0.813rem, 2vw, 0.9rem);
        }

        .info-box ul {
            margin: 0.5rem 0 0 0;
            padding-left: 1.2rem;
        }

        .info-box li {
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }

        .register {
            text-align: center;
            margin-top: 1.5rem;
        }

        .register p {
            font-size: clamp(0.813rem, 2vw, 0.9rem);
            margin: 0.5rem 0;
        }

        .register a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .register a:hover {
            text-decoration: underline;
        }

        /* Responsive breakpoints */
        @media (max-width: 768px) {
            .container {
                padding: 1rem 0.75rem;
            }

            .login-card {
                padding: 1.5rem;
                border-radius: 12px;
            }

            .back-link {
                font-size: 0.875rem;
            }

            .login-form input {
                padding: 0.75rem;
            }

            .btn-login {
                padding: 0.75rem;
            }

            .info-box {
                padding: 0.875rem;
            }

            .info-box li {
                margin-bottom: 0.4rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0.75rem 0.5rem;
            }

            .login-card {
                padding: 1.25rem;
                border-radius: 10px;
            }

            .back-link {
                font-size: 0.813rem;
                margin-bottom: 0.75rem;
            }

            .title {
                margin-bottom: 0.375rem;
            }

            .subtitle {
                margin-bottom: 1rem;
            }

            .illustration {
                margin: 1rem 0;
            }

            .login-form input {
                padding: 0.688rem 0.875rem;
                margin-bottom: 1rem;
                border-radius: 6px;
            }

            .btn-login {
                padding: 0.688rem;
                border-radius: 6px;
            }

            .info-box {
                padding: 0.75rem;
                margin-top: 1.25rem;
                border-radius: 8px;
            }

            .info-box ul {
                padding-left: 1rem;
            }

            .register {
                margin-top: 1.25rem;
            }
        }

        @media (max-width: 360px) {
            .login-card {
                padding: 1rem;
            }

            .info-box {
                padding: 0.625rem;
            }
        }

        /* Landscape mobile fix */
        @media (max-height: 600px) and (orientation: landscape) {
            .container {
                padding: 1rem 0.5rem;
            }

            .login-card {
                padding: 1rem;
            }

            .illustration {
                margin: 0.75rem 0;
            }

            .illustration img {
                max-width: 80px;
            }

            .info-box {
                margin-top: 1rem;
            }

            .register {
                margin-top: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Loading screen -->
    <div id="loading-screen">
        <div class="loader">
            <div class="electric-circle"></div>  
            <img src="../images/Logo_1.png" alt="Logo E-Station">
            <h2>E-STATION</h2>
        </div>
    </div>

    <!-- Tombol toggle tema -->
    <div class="theme-toggle">
        <button id="toggleTheme" aria-label="Ganti Tema">🌙</button>
    </div>

    <!-- Kontainer lupa password -->
    <div class="container">
        <div class="login-card">
            <h1 class="title">Lupa Password?</h1>
            <p class="subtitle">Masukkan email Anda untuk reset password</p>

            <div class="illustration">
                <img src="../images/Logo_1.jpeg" alt="Logo E-Station">
            </div>
            
            <?php tampilkan_alert(); ?>
            
            <form action="" method="POST" class="login-form">
                <label for="email">Email Terdaftar</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="contoh@email.com" 
                    required
                    autofocus
                >

                <button type="submit" class="btn-login">Kirim Link Reset Password</button>
            </form>

            <div class="info-box">
                <strong>📧 Informasi:</strong>
                <ul>
                    <li>Link reset password akan dikirim ke email Anda</li>
                    <li>Link berlaku selama 1 jam</li>
                    <li>Jika tidak menerima email, cek folder spam</li>
                    <li>Pastikan email yang dimasukkan sudah terdaftar</li>
                </ul>
            </div>

            <div class="register">
                <p>Ingat password? <a href="login.php">Login di sini</a></p>
                <p>Belum punya akun? <a href="auth.php">Daftar di sini</a></p>
            </div>
        </div>
    </div>

    <script>
        // === Tema Manual ===
        const toggleBtn = document.getElementById("toggleTheme");
        const body = document.body;

        if (localStorage.getItem("theme") === "light") {
            body.classList.add("light");
            toggleBtn.textContent = "🌙";
        } else {
            toggleBtn.textContent = "☀️";
        }

        toggleBtn.addEventListener("click", () => {
            body.classList.toggle("light");
            if (body.classList.contains("light")) {
                toggleBtn.textContent = "🌙";
                localStorage.setItem("theme", "light");
            } else {
                toggleBtn.textContent = "☀️";
                localStorage.setItem("theme", "dark");
            }
        });

        // === Efek Loading ===
        window.addEventListener("load", () => {
            const loading = document.getElementById("loading-screen");
            if (loading) {
                setTimeout(() => {
                    loading.classList.add("hidden");
                }, 1000);
            }
        });

        // === Clean URL ===
        if (window.history.replaceState) {
            const url = new URL(window.location.href);
            if (url.search) {
                url.search = '';
                window.history.replaceState({}, document.title, url.toString());
            }
        }

        // === Auto dismiss alerts ===
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert) {
                    alert.classList.add('fade-out');
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);
    </script>
</body>
</html>
