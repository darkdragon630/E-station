<?php
session_start();
require_once '../config/koneksi.php';
require_once "../pesan/alerts.php";

// Validasi token
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($token) || !isset($_SESSION['reset_token']) || $token !== $_SESSION['reset_token']) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'message' => 'Token tidak valid atau sudah kadaluarsa!'
    ];
    header("Location: lupa_password.php");
    exit;
}

// Cek expiry token
if (isset($_SESSION['reset_expiry'])) {
    $expiry = strtotime($_SESSION['reset_expiry']);
    if (time() > $expiry) {
        unset($_SESSION['reset_token'], $_SESSION['reset_email'], $_SESSION['reset_type'], $_SESSION['reset_user_id'], $_SESSION['reset_expiry']);
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => 'Token sudah kadaluarsa! Silakan kirim ulang permintaan reset password.'
        ];
        header("Location: lupa_password.php");
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_baru = $_POST['password'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    
    // Validasi
    $errors = [];
    
    if (empty($password_baru) || empty($konfirmasi_password)) {
        $errors[] = 'Password wajib diisi!';
    }
    
    if (strlen($password_baru) < 8) {
        $errors[] = 'Password minimal 8 karakter!';
    }
    
    if (!preg_match('/[a-z]/', $password_baru)) {
        $errors[] = 'Password harus mengandung huruf kecil (a-z)!';
    }
    
    if (!preg_match('/[A-Z]/', $password_baru)) {
        $errors[] = 'Password harus mengandung huruf besar (A-Z)!';
    }
    
    if (!preg_match('/[0-9]/', $password_baru)) {
        $errors[] = 'Password harus mengandung angka (0-9)!';
    }
    
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password_baru)) {
        $errors[] = 'Password harus mengandung simbol (!@#$%^&*)!';
    }
    
    if ($password_baru !== $konfirmasi_password) {
        $errors[] = 'Password dan konfirmasi password tidak cocok!';
    }
    
    if (!empty($errors)) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => implode('<br>', $errors)
        ];
    } else {
        try {
            $email = $_SESSION['reset_email'];
            $user_type = $_SESSION['reset_type'];
            $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
            
            // Update password berdasarkan tipe user
            if ($user_type === 'admin') {
                $stmt = $koneksi->prepare("UPDATE admin SET password = ? WHERE email = ?");
            } elseif ($user_type === 'pengendara') {
                $stmt = $koneksi->prepare("UPDATE pengendara SET password = ? WHERE email = ?");
            } elseif ($user_type === 'mitra') {
                $stmt = $koneksi->prepare("UPDATE mitra SET password = ? WHERE email = ?");
            }
            
            $stmt->execute([$password_hash, $email]);
            
            // Hapus session reset
            unset($_SESSION['reset_token'], $_SESSION['reset_email'], $_SESSION['reset_type'], $_SESSION['reset_user_id'], $_SESSION['reset_expiry']);
            
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Password berhasil diubah! Silakan login dengan password baru Anda.'
            ];
            
            header("Location: login.php");
            exit;
            
        } catch (PDOException $e) {
            $_SESSION['alert'] = [
                'type' => 'error',
                'message' => 'Terjadi kesalahan sistem. Silakan coba lagi!'
            ];
            error_log("Error reset password: " . $e->getMessage());
        }
    }
    
    header("Location: reset_password.php?token=" . $token);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scalable=1.0, user-scalable=no">
    <title>E-Station | Reset Password</title>
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
        .password-requirements {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        .password-requirements ul {
            margin: 0.5rem 0 0 0;
            padding-left: 1.2rem;
        }
        .password-requirements li {
            margin-bottom: 0.3rem;
        }
        .requirement-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .requirement-item.valid {
            color: #4CAF50;
        }
        .requirement-item.invalid {
            color: #999;
        }
        .password-toggle {
            position: relative;
        }
        .password-toggle button {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            padding: 5px;
            color: var(--text-color);
        }
        .password-toggle input {
            padding-right: 40px;
        }
        .password-strength {
            height: 5px;
            background: #e0e0e0;
            border-radius: 3px;
            margin: 0.5rem 0;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 3px;
        }
        .strength-weak { width: 33%; background: #f44336; }
        .strength-medium { width: 66%; background: #ff9800; }
        .strength-strong { width: 100%; background: #4CAF50; }
        
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
            .password-requirements {
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 1rem;
            }
            .password-requirements {
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

    <!-- Kontainer reset password -->
    <div class="container">
        <div class="login-card">
            <a href="login.php" class="back-link">
                ← Kembali ke Login
            </a>
            
            <h1 class="title">Reset Password</h1>
            <p class="subtitle">Masukkan password baru yang kuat dan aman</p>

            <div class="illustration">
                <img src="../images/Logo_1.jpeg" alt="Logo E-Station" style="max-width: 120px;">
            </div>
            
            <?php tampilkan_alert(); ?>
            
            <form action="" method="POST" class="login-form" id="resetForm">
                <label for="password">Password Baru</label>
                <div class="password-toggle">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Minimal 8 karakter" 
                        required
                        minlength="8"
                        autofocus
                    >
                    <button type="button" onclick="togglePassword('password')" aria-label="Toggle Password">
                        👁️
                    </button>
                </div>
                
                <!-- Password Strength Indicator -->
                <div class="password-strength">
                    <div class="password-strength-bar" id="strengthBar"></div>
                </div>
                <small id="strengthText" style="color: #999;"></small>

                <label for="konfirmasi_password">Konfirmasi Password Baru</label>
                <div class="password-toggle">
                    <input 
                        type="password" 
                        id="konfirmasi_password" 
                        name="konfirmasi_password" 
                        placeholder="Masukkan ulang password" 
                        required
                        minlength="8"
                    >
                    <button type="button" onclick="togglePassword('konfirmasi_password')" aria-label="Toggle Password">
                        👁️
                    </button>
                </div>
                <small id="validFeedback" class="valid-feedback" style="display: none; color: #10b981; margin-top: 6px; font-weight: 600;">
                    ✅ Password cocok!
                </small>
                <small id="invalidFeedback" class="invalid-feedback" style="display: none; color: #ef4444; margin-top: 6px;">
                    ❌ Password tidak cocok!
                </small>

                <button type="submit" class="btn-login">Reset Password</button>
            </form>

            <div class="password-requirements">
                <strong>🔒 Syarat Password:</strong>
                <div style="margin-top: 0.75rem;">
                    <div class="requirement-item" id="req-length">
                        <span>❌</span> Minimal 8 karakter
                    </div>
                    <div class="requirement-item" id="req-lowercase">
                        <span>❌</span> Huruf kecil (a-z)
                    </div>
                    <div class="requirement-item" id="req-uppercase">
                        <span>❌</span> Huruf besar (A-Z)
                    </div>
                    <div class="requirement-item" id="req-number">
                        <span>❌</span> Angka (0-9)
                    </div>
                    <div class="requirement-item" id="req-symbol">
                        <span>❌</span> Simbol (!@#$%^&*)
                    </div>
                </div>
            </div>

            <div class="register" style="margin-top: 1.5rem;">
                <p>Ingat password? <a href="login.php">Login di sini</a></p>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const btn = field.nextElementSibling;
            
            if (field.type === 'password') {
                field.type = 'text';
                btn.textContent = '🙈';
            } else {
                field.type = 'password';
                btn.textContent = '👁️';
            }
        }

        // Password validation realtime
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        
        const requirements = {
            length: { regex: /.{8,}/, element: document.getElementById('req-length') },
            lowercase: { regex: /[a-z]/, element: document.getElementById('req-lowercase') },
            uppercase: { regex: /[A-Z]/, element: document.getElementById('req-uppercase') },
            number: { regex: /[0-9]/, element: document.getElementById('req-number') },
            symbol: { regex: /[!@#$%^&*(),.?":{}|<>]/, element: document.getElementById('req-symbol') }
        };

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let validCount = 0;
            
            // Check each requirement
            for (const [key, req] of Object.entries(requirements)) {
                const isValid = req.regex.test(password);
                if (isValid) {
                    req.element.classList.add('valid');
                    req.element.classList.remove('invalid');
                    req.element.querySelector('span').textContent = '✅';
                    validCount++;
                } else {
                    req.element.classList.remove('valid');
                    req.element.classList.add('invalid');
                    req.element.querySelector('span').textContent = '❌';
                }
            }
            
            // Update strength bar
            strengthBar.className = 'password-strength-bar';
            if (validCount <= 2) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Lemah';
                strengthText.style.color = '#f44336';
            } else if (validCount <= 4) {
                strengthBar.classList.add('strength-medium');
                strengthText.textContent = 'Sedang';
                strengthText.style.color = '#ff9800';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Kuat';
                strengthText.style.color = '#4CAF50';
            }
        });

        // Validasi form
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const konfirmasi = document.getElementById('konfirmasi_password').value;
            
            // Cek semua requirements
            let allValid = true;
            for (const [key, req] of Object.entries(requirements)) {
                if (!req.regex.test(password)) {
                    allValid = false;
                    break;
                }
            }
            
            if (!allValid) {
                e.preventDefault();
                alert('Password belum memenuhi semua syarat yang ditentukan!');
                return false;
            }
            
            if (password !== konfirmasi) {
                e.preventDefault();
                alert('Password dan konfirmasi password tidak cocok!');
                return false;
            }
        });
        
        // Real-time password match validation
        document.getElementById('konfirmasi_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const konfirmasi = this.value;
            const validFeedback = document.getElementById('validFeedback');
            const invalidFeedback = document.getElementById('invalidFeedback');
            
            if (konfirmasi === '') {
                validFeedback.style.display = 'none';
                invalidFeedback.style.display = 'none';
                return;
            }
            
            if (password === konfirmasi) {
                validFeedback.style.display = 'block';
                invalidFeedback.style.display = 'none';
            } else {
                validFeedback.style.display = 'none';
                invalidFeedback.style.display = 'block';
            }
        });

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
            const params = new URLSearchParams(url.search);
            if (params.has('success') || params.has('error')) {
                params.delete('success');
                params.delete('error');
                url.search = params.toString();
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