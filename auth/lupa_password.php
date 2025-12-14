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
                    // Email berhasil dikirim
                    $_SESSION['alert'] = [
                        'type' => 'success',
                        'message' => 'Link reset password telah dikirim ke email Anda! Silakan cek inbox atau folder spam.'
                    ];
                    // Juga tampilkan manual link sebagai backup
                    $_SESSION['reset_manual_link'] = true;
                } else {
                    // Email gagal - tampilkan manual link
                    $_SESSION['reset_manual_link'] = true;
                    $_SESSION['alert'] = [
                        'type' => 'warning',
                        'message' => 'Email gagal dikirim, tapi Anda masih bisa reset password menggunakan link di bawah.'
                    ];
                }
                
                // JANGAN redirect otomatis, biarkan user di halaman ini
                // User bisa klik link manual atau tunggu email
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
        }
        .info-box {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }
        .info-box ul {
            margin: 0.5rem 0 0 0;
            padding-left: 1.2rem;
        }
        .info-box li {
            margin-bottom: 0.3rem;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            .login-card {
                padding: 1.5rem;
                margin: 1rem auto;
            }
            .illustration img {
                max-width: 100px !important;
            }
            .title {
                font-size: 1.5rem;
            }
            .subtitle {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 1rem;
            }
            .info-box {
                font-size: 0.85rem;
                padding: 0.75rem;
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
            <a href="login.php" class="back-link">
                ← Kembali ke Login
            </a>
            
            <h1 class="title">Lupa Password?</h1>
            <p class="subtitle">Masukkan email Anda untuk reset password</p>

            <div class="illustration">
                <img src="../images/Logo_1.jpeg" alt="Logo E-Station" style="max-width: 120px;">
            </div>
            
            <?php tampilkan_alert(); ?>
            
            <?php if (isset($_SESSION['reset_manual_link']) && $_SESSION['reset_manual_link']): ?>
            <!-- Manual Reset Link Box -->
            <div class="manual-reset-box" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.15)); border: 2px solid rgba(102, 126, 234, 0.4); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem;">
                <h6 style="margin: 0 0 1rem 0; color: var(--text-color); font-size: 1rem;">
                    <i style="color: #667eea;">🔗</i> Link Reset Password
                </h6>
                <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1rem;">
                    Klik tombol di bawah untuk reset password Anda:
                </p>
                <a href="reset_password.php?token=<?= $_SESSION['reset_token'] ?? '' ?>" 
                   class="btn-login" 
                   style="display: block; text-align: center; margin-bottom: 1rem;">
                    🔑 Reset Password Sekarang
                </a>
                <div style="background: rgba(255,255,255,0.5); padding: 0.75rem; border-radius: 8px; font-size: 0.85rem; word-break: break-all; font-family: monospace; color: #666;">
                    <?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?><?= dirname($_SERVER['PHP_SELF']) ?>/reset_password.php?token=<?= $_SESSION['reset_token'] ?? '' ?>
                </div>
                <p style="font-size: 0.85rem; color: #ff9800; margin: 1rem 0 0 0;">
                    ⏰ Link berlaku selama 1 jam
                </p>
            </div>
            <?php unset($_SESSION['reset_manual_link']); ?>
            <?php endif; ?>
            
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
                    <li>Jika email tidak masuk, gunakan link manual yang muncul di halaman ini</li>
                </ul>
            </div>

            <div class="register" style="margin-top: 1.5rem;">
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