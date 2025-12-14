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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scalable=1.0, user-scalable=no">
    <title>Registrasi - E-Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/auth.css">
    <link rel="stylesheet" href="../css/alert.css">
    <link rel="icon" type="image/png" href="../images/Logo_1.png">
</head>
<body>

<div class="register-container">
    <?php tampilkan_alert(); ?>
    
    <div class="page-header">
        <h1>E-STATION</h1>
        <p class="subtitle">Layanan Pengisian Kendaraan Listrik</p>
        <img src="../images/ev-station.png" alt="EV Station">
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="text-center mb-4">Registrasi Akun</h3>
            
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
                <form id="formPengendara" action="process_register_pengendara.php" method="POST" onsubmit="return validateForm('pengendara')">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-user me-1"></i>Nama Lengkap <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="nama" id="nama_pengendara" class="form-control" placeholder="Masukkan nama lengkap" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-envelope me-1"></i>Email <span class="text-danger">*</span>
                        </label>
                        <input type="email" name="email" id="email_pengendara" class="form-control" placeholder="contoh@email.com" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-phone me-1"></i>No. Telepon
                        </label>
                        <input type="tel" name="no_telepon" id="no_telepon_pengendara" class="form-control" placeholder="08xxxxxxxxxx" pattern="[0-9]{10,15}" maxlength="20" oninput="validatePhone('pengendara')">
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
                        <div class="password-input-wrapper">
                            <input type="password" name="password" id="password_pengendara" class="form-control" placeholder="Minimal 8 karakter" required minlength="8" oninput="checkPasswordStrength(this, 'pengendara')">
                            <span class="password-toggle" onclick="togglePassword('password_pengendara')">👁️</span>
                        </div>
                        <div class="password-strength" id="strength_pengendara"></div>
                        
                        <!-- Password Requirements -->
                        <div class="password-requirements" id="requirements_pengendara">
                            <small class="text-muted d-block mb-1">Password harus mengandung:</small>
                            <small class="requirement" id="req_length_pengendara">
                                <i class="fas fa-circle"></i> Minimal 8 karakter
                            </small>
                            <small class="requirement" id="req_lowercase_pengendara">
                                <i class="fas fa-circle"></i> Huruf kecil (a-z)
                            </small>
                            <small class="requirement" id="req_uppercase_pengendara">
                                <i class="fas fa-circle"></i> Huruf besar (A-Z)
                            </small>
                            <small class="requirement" id="req_number_pengendara">
                                <i class="fas fa-circle"></i> Angka (0-9)
                            </small>
                            <small class="requirement" id="req_special_pengendara">
                                <i class="fas fa-circle"></i> Simbol (!@#$%^&*)
                            </small>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-lock me-1"></i>Konfirmasi Password <span class="text-danger">*</span>
                        </label>
                        <div class="password-input-wrapper">
                            <input type="password" name="confirm_password" id="confirm_password_pengendara" class="form-control" placeholder="Ulangi password" required oninput="checkPasswordMatch('pengendara')">
                            <span class="password-toggle" onclick="togglePassword('confirm_password_pengendara')">👁️</span>
                        </div>
                        <small class="invalid-feedback" id="invalid_pengendara">❌ Password tidak cocok!</small>
                        <small class="valid-feedback" id="valid_pengendara">✅ Password cocok!</small>
                    </div>
                    
                    <button type="submit" id="btnPengendara" class="btn btn-primary btn-register w-100">
                        <i class="fas fa-user-plus me-2"></i>Daftar Sebagai Pengendara
                    </button>
                </form>
            </div>
            
            <!-- FORM MITRA -->
            <div id="mitra" class="form-box">
                <form id="formMitra" action="process_register_mitra.php" method="POST" onsubmit="return validateForm('mitra')">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-store me-1"></i>Nama Mitra/Usaha <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="nama_mitra" id="nama_mitra" class="form-control" placeholder="Nama usaha/toko" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-envelope me-1"></i>Email <span class="text-danger">*</span>
                        </label>
                        <input type="email" name="email" id="email_mitra" class="form-control" placeholder="contoh@email.com" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-phone me-1"></i>No. Telepon
                        </label>
                        <input type="tel" name="no_telepon" id="no_telepon_mitra" class="form-control" placeholder="08xxxxxxxxxx" pattern="[0-9]{10,15}" maxlength="20" oninput="validatePhone('mitra')">
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
                        <div class="password-input-wrapper">
                            <input type="password" name="password" id="password_mitra" class="form-control" placeholder="Minimal 8 karakter" required minlength="8" oninput="checkPasswordStrength(this, 'mitra')">
                            <span class="password-toggle" onclick="togglePassword('password_mitra')">👁️</span>
                        </div>
                        <div class="password-strength" id="strength_mitra"></div>
                        
                        <!-- Password Requirements -->
                        <div class="password-requirements" id="requirements_mitra">
                            <small class="text-muted d-block mb-1">Password harus mengandung:</small>
                            <small class="requirement" id="req_length_mitra">
                                <i class="fas fa-circle"></i> Minimal 8 karakter
                            </small>
                            <small class="requirement" id="req_lowercase_mitra">
                                <i class="fas fa-circle"></i> Huruf kecil (a-z)
                            </small>
                            <small class="requirement" id="req_uppercase_mitra">
                                <i class="fas fa-circle"></i> Huruf besar (A-Z)
                            </small>
                            <small class="requirement" id="req_number_mitra">
                                <i class="fas fa-circle"></i> Angka (0-9)
                            </small>
                            <small class="requirement" id="req_special_mitra">
                                <i class="fas fa-circle"></i> Simbol (!@#$%^&*)
                            </small>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-lock me-1"></i>Konfirmasi Password <span class="text-danger">*</span>
                        </label>
                        <div class="password-input-wrapper">
                            <input type="password" name="confirm_password" id="confirm_password_mitra" class="form-control" placeholder="Ulangi password" required oninput="checkPasswordMatch('mitra')">
                            <span class="password-toggle" onclick="togglePassword('confirm_password_mitra')">👁️</span>
                        </div>
                        <small class="invalid-feedback" id="invalid_mitra">❌ Password tidak cocok!</small>
                        <small class="valid-feedback" id="valid_mitra">✅ Password cocok!</small>
                    </div>
                    
                    <button type="submit" id="btnMitra" class="btn btn-primary btn-register w-100">
                        <i class="fas fa-store me-2"></i>Daftar Sebagai Mitra
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="login-link">
        <p>Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
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

// Toggle Password Visibility - UPDATED dengan Emoji
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const btn = event.target;
    
    if (field.type === 'password') {
        field.type = 'text';
        btn.textContent = '🙈';
    } else {
        field.type = 'password';
        btn.textContent = '👁️';
    }
}

// Validate Phone Number
function validatePhone(type) {
    const phoneField = document.getElementById('no_telepon_' + type);
    const feedback = phoneField.nextElementSibling;
    const phoneValue = phoneField.value;
    
    if (phoneValue === '') {
        phoneField.classList.remove('is-valid', 'is-invalid');
        if (feedback) feedback.classList.remove('show');
        return;
    }
    
    if (/^[0-9]{10,15}$/.test(phoneValue)) {
        phoneField.classList.remove('is-invalid');
        phoneField.classList.add('is-valid');
        if (feedback) feedback.classList.remove('show');
    } else {
        phoneField.classList.remove('is-valid');
        phoneField.classList.add('is-invalid');
        if (feedback) feedback.classList.add('show');
    }
}

// Check Password Strength with Requirements
function checkPasswordStrength(field, type) {
    const password = field.value;
    const strengthBar = document.getElementById('strength_' + type);
    
    // Requirements elements
    const reqLength = document.getElementById('req_length_' + type);
    const reqLowercase = document.getElementById('req_lowercase_' + type);
    const reqUppercase = document.getElementById('req_uppercase_' + type);
    const reqNumber = document.getElementById('req_number_' + type);
    const reqSpecial = document.getElementById('req_special_' + type);
    
    if (password.length === 0) {
        strengthBar.className = 'password-strength';
        [reqLength, reqLowercase, reqUppercase, reqNumber, reqSpecial].forEach(req => {
            req.classList.remove('met');
        });
        return;
    }
    
    let strength = 0;
    
    // Check length
    if (password.length >= 8) {
        strength++;
        reqLength.classList.add('met');
    } else {
        reqLength.classList.remove('met');
    }
    
    // Check lowercase
    if (/[a-z]/.test(password)) {
        strength++;
        reqLowercase.classList.add('met');
    } else {
        reqLowercase.classList.remove('met');
    }
    
    // Check uppercase
    if (/[A-Z]/.test(password)) {
        strength++;
        reqUppercase.classList.add('met');
    } else {
        reqUppercase.classList.remove('met');
    }
    
    // Check number
    if (/[0-9]/.test(password)) {
        strength++;
        reqNumber.classList.add('met');
    } else {
        reqNumber.classList.remove('met');
    }
    
    // Check special character
    if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
        strength++;
        reqSpecial.classList.add('met');
    } else {
        reqSpecial.classList.remove('met');
    }
    
    // Update strength bar
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
    
    // Get feedback elements by ID (lebih reliable)
    const invalidFeedback = document.getElementById('invalid_' + type);
    const validFeedback = document.getElementById('valid_' + type);
    
    if (confirmPassword === '') {
        confirmField.classList.remove('is-valid', 'is-invalid');
        if (validFeedback) validFeedback.style.display = 'none';
        if (invalidFeedback) invalidFeedback.style.display = 'none';
        return;
    }
    
    // Debug log
    console.log('Password:', password);
    console.log('Confirm:', confirmPassword);
    console.log('Match:', password === confirmPassword);
    
    if (password === confirmPassword && password !== '') {
        confirmField.classList.remove('is-invalid');
        confirmField.classList.add('is-valid');
        if (validFeedback) validFeedback.style.display = 'block';
        if (invalidFeedback) invalidFeedback.style.display = 'none';
    } else {
        confirmField.classList.remove('is-valid');
        confirmField.classList.add('is-invalid');
        if (validFeedback) validFeedback.style.display = 'none';
        if (invalidFeedback) invalidFeedback.style.display = 'block';
    }
}

// Form Validation Before Submit
function validateForm(type) {
    let valid = true;
    const password = document.getElementById('password_' + type).value;
    const confirmPassword = document.getElementById('confirm_password_' + type).value;
    
    // Check password requirements
    const hasLength = password.length >= 8;
    const hasLowercase = /[a-z]/.test(password);
    const hasUppercase = /[A-Z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
    
    if (!hasLength || !hasLowercase || !hasUppercase || !hasNumber || !hasSpecial) {
        alert('Password harus memenuhi semua persyaratan:\n- Minimal 8 karakter\n- Huruf kecil (a-z)\n- Huruf besar (A-Z)\n- Angka (0-9)\n- Simbol (!@#$%^&*)');
        valid = false;
        return valid;
    }
    
    // Check password match
    if (password !== confirmPassword) {
        alert('Password dan konfirmasi password tidak cocok!');
        valid = false;
        return valid;
    }
    
    if (valid) {
        const btnId = 'btn' + type.charAt(0).toUpperCase() + type.slice(1);
        const btn = document.getElementById(btnId);
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
    }
    
    return valid;
}
</script>

</body>
</html>