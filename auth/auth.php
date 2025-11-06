<?php
session_start();
require_once "../pesan/alerts.php";

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    if ($role == 'admin') {
        header("Location: ../admin/dashboard.php");
    } elseif ($role == 'pengendara') {
        header("Location: ../pengendara/dashboard.php");
    } elseif ($role == 'mitra') {
        header("Location: ../mitra/dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - E-Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px 0;
        }
        .register-container {
            max-width: 500px;
            width: 100%;
            margin: 20px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .card-header {
            background: white;
            border-bottom: none;
            padding: 30px 30px 0;
            border-radius: 15px 15px 0 0;
        }
        .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 0;
        }
        .tab-btn {
            flex: 1;
            padding: 12px;
            border: 2px solid #e0e0e0;
            background: white;
            color: #666;
            cursor: pointer;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .tab-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }
        .tab-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }
        .form-box {
            display: none;
            padding: 30px;
        }
        .form-box.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .form-control.is-invalid {
            border-color: #dc3545;
        }
        .form-control.is-valid {
            border-color: #28a745;
        }
        .invalid-feedback, .valid-feedback {
            font-size: 12px;
            margin-top: 5px;
        }
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 8px;
            transition: all 0.3s;
        }
        .strength-weak { background: #dc3545; width: 33%; }
        .strength-medium { background: #ffc107; width: 66%; }
        .strength-strong { background: #28a745; width: 100%; }
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
            transition: transform 0.2s ease;
        }
        .btn-register:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-register:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        .login-link a {
            color: #fff;
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .position-relative {
            position: relative;
        }
    </style>
</head>
<body>

<div class="register-container">
    <div class="card">
        <div class="card-header">
            <h3 class="text-center mb-4">
                <i class="fas fa-user-plus me-2"></i>Registrasi Akun
            </h3>
            
            <!-- TAMPILKAN ALERT -->
            <?php tampilkan_alert(); ?>
            
            <!-- TAB BUTTONS -->
            <div class="tab-buttons">
                <button class="tab-btn active" onclick="switchTab('pengendara')">
                    <i class="fas fa-motorcycle me-2"></i>Pengendara
                </button>
                <button class="tab-btn" onclick="switchTab('mitra')">
                    <i class="fas fa-store me-2"></i>Mitra
                </button>
            </div>
        </div>
        
        <div class="card-body p-0">
            <!-- FORM PENGENDARA -->
            <div id="pengendara" class="form-box active">
                <h5 class="mb-3">
                    <i class="fas fa-motorcycle me-2 text-primary"></i>Daftar Sebagai Pengendara
                </h5>
                <form id="formPengendara" action="process_register_pengendara.php" method="POST" onsubmit="return validateForm('pengendara')">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-user me-1"></i>Nama Lengkap <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="nama" id="nama_pengendara" class="form-control" placeholder="Masukkan nama lengkap" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-envelope me-1"></i>Email <span class="text-danger">*</span>
                        </label>
                        <input type="email" name="email" id="email_pengendara" class="form-control" placeholder="contoh@email.com" required>
                        <div class="invalid-feedback"></div>
                        <div class="valid-feedback">Email tersedia!</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-phone me-1"></i>No. Telepon
                        </label>
                        <input type="tel" name="no_telepon" id="no_telepon_pengendara" class="form-control" placeholder="08xxxxxxxxxx" pattern="[0-9]{10,15}" maxlength="20">
                        <div class="invalid-feedback">Format nomor telepon tidak valid (10-15 digit).</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-map-marker-alt me-1"></i>Alamat
                        </label>
                        <textarea name="alamat" id="alamat_pengendara" class="form-control" rows="2" placeholder="Alamat lengkap (opsional)"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-lock me-1"></i>Password <span class="text-danger">*</span>
                        </label>
                        <div class="position-relative">
                            <input type="password" name="password" id="password_pengendara" class="form-control" placeholder="Minimal 8 karakter" required minlength="8" oninput="checkPasswordStrength(this, 'pengendara')">
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('password_pengendara')"></i>
                        </div>
                        <div class="password-strength" id="strength_pengendara"></div>
                        <small class="text-muted">Minimal 8 karakter.</small>
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-lock me-1"></i>Konfirmasi Password <span class="text-danger">*</span>
                        </label>
                        <div class="position-relative">
                            <input type="password" name="confirm_password" id="confirm_password_pengendara" class="form-control" placeholder="Ulangi password" required oninput="checkPasswordMatch('pengendara')">
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password_pengendara')"></i>
                        </div>
                        <div class="invalid-feedback"></div>
                        <div class="valid-feedback">Password cocok!</div>
                    </div>
                    
                    <button type="submit" id="btnPengendara" class="btn btn-primary btn-register w-100">
                        <i class="fas fa-user-plus me-2"></i>Daftar Sebagai Pengendara
                    </button>
                </form>
            </div>
            
            <!-- FORM MITRA -->
            <div id="mitra" class="form-box">
                <h5 class="mb-3">
                    <i class="fas fa-store me-2 text-primary"></i>Daftar Sebagai Mitra
                </h5>
                <form id="formMitra" action="process_register_mitra.php" method="POST" onsubmit="return validateForm('mitra')">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-store me-1"></i>Nama Mitra/Usaha <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="nama_mitra" id="nama_mitra" class="form-control" placeholder="Nama usaha/toko" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-envelope me-1"></i>Email <span class="text-danger">*</span>
                        </label>
                        <input type="email" name="email" id="email_mitra" class="form-control" placeholder="contoh@email.com" required>
                        <div class="invalid-feedback"></div>
                        <div class="valid-feedback">Email tersedia!</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-phone me-1"></i>No. Telepon
                        </label>
                        <input type="tel" name="no_telepon" id="no_telepon_mitra" class="form-control" placeholder="08xxxxxxxxxx" pattern="[0-9]{10,15}" maxlength="20">
                        <div class="invalid-feedback">Format nomor telepon tidak valid (10-15 digit).</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-map-marker-alt me-1"></i>Alamat
                        </label>
                        <textarea name="alamat" id="alamat_mitra" class="form-control" rows="2" placeholder="Alamat usaha (opsional)"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-lock me-1"></i>Password <span class="text-danger">*</span>
                        </label>
                        <div class="position-relative">
                            <input type="password" name="password" id="password_mitra" class="form-control" placeholder="Minimal 8 karakter" required minlength="8" oninput="checkPasswordStrength(this, 'mitra')">
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('password_mitra')"></i>
                        </div>
                        <div class="password-strength" id="strength_mitra"></div>
                        <small class="text-muted">Minimal 8 karakter.</small>
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-lock me-1"></i>Konfirmasi Password <span class="text-danger">*</span>
                        </label>
                        <div class="position-relative">
                            <input type="password" name="confirm_password" id="confirm_password_mitra" class="form-control" placeholder="Ulangi password" required oninput="checkPasswordMatch('mitra')">
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password_mitra')"></i>
                        </div>
                        <div class="invalid-feedback"></div>
                        <div class="valid-feedback">Password cocok!</div>
                    </div>
                    
                    <button type="submit" id="btnMitra" class="btn btn-primary btn-register w-100">
                        <i class="fas fa-store me-2"></i>Daftar Sebagai Mitra
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="login-link">
        <p class="text-white">Sudah punya akun? <a href="login.php">Login di sini</a></p>
    </div>
</div>

<script src="../js/clean-url.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Switch Tab Function
function switchTab(tabId) {
    document.querySelectorAll('.form-box').forEach(box => {
        box.classList.remove('active');
    });
    document.getElementById(tabId).classList.add('active');
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.closest('.tab-btn').classList.add('active');
}

// Toggle Password Visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = event.target;
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Check Password Strength
function checkPasswordStrength(field, type) {
    const password = field.value;
    const strengthBar = document.getElementById('strength_' + type);
    
    let strength = 0;
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    strengthBar.className = 'password-strength';
    if (strength < 3) {
        strengthBar.classList.add('strength-weak');
    } else if (strength < 5) {
        strengthBar.classList.add('strength-medium');
    } else {
        strengthBar.classList.add('strength-strong');
    }
}

// Check Password Match
function checkPasswordMatch(type) {
    const password = document.getElementById('password_' + type).value;
    const confirmPassword = document.getElementById('confirm_password_' + type).value;
    const confirmField = document.getElementById('confirm_password_' + type);
    
    if (confirmPassword === '') {
        confirmField.classList.remove('is-valid', 'is-invalid');
        return;
    }
    
    if (password === confirmPassword) {
        confirmField.classList.remove('is-invalid');
        confirmField.classList.add('is-valid');
    } else {
        confirmField.classList.remove('is-valid');
        confirmField.classList.add('is-invalid');
        const feedback = confirmField.parentElement.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.textContent = 'Password tidak cocok!';
        }
    }
}

// Form Validation Before Submit
function validateForm(type) {
    let valid = true;
    const password = document.getElementById('password_' + type).value;
    const confirmPassword = document.getElementById('confirm_password_' + type).value;
    
    // Check password match
    if (password !== confirmPassword) {
        alert('Password dan konfirmasi password tidak cocok!');
        valid = false;
    }
    
    if (valid) {
        const btnId = 'btn' + type.charAt(0).toUpperCase() + type.slice(1);
        document.getElementById(btnId).disabled = true;
        document.getElementById(btnId).innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
    }
    
    return valid;
}
</script>

</body>
</html>