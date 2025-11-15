<?php
/**
 * File: pesan/alerts.php
 * Fungsi: Menampilkan pesan alert (success, error, warning, info)
 */

// fungsi untuk menampilkan alert
function tampilkan_alert() {
    // cek apakah ada pesan sukses
    if (isset($_GET['success'])) {
        $pesan = '';
        switch ($_GET['success']) {
            case 'register':
                $pesan = "Pendaftaran berhasil!";
                break;
            case 'login':
                $pesan = "Login berhasil! Selamat datang kembali.";
                break;
            case 'logout':
                $pesan = "Anda telah berhasil Logout.";
                break;
            case 'update_profile':
                $pesan = "Profil berhasil diperbarui.";
                break;
            case 'foto_uploaded':
                $pesan = "Foto profil berhasil diupload!";
                break;
            case 'change_password':
                $pesan = "Kata sandi berhasil diubah.";
                break;
            case 'upload_success':
                $pesan = "File berhasil diunggah.";
                break;
            case 'data_saved':
                $pesan = "Data berhasil disimpan.";
                break;
            case 'data_deleted':
                $pesan = "Data berhasil dihapus.";
                break;
            case 'check_email':
                $pesan = "Silahkan cek email Anda untuk verifikasi.";
                break;
            case 'email_verified':
                $pesan = "Email berhasil diverifikasi! Silahkan login.";
                break;
            case 'resend_verification':
                $pesan = "Link verifikasi telah dikirim ulang ke email Anda.";
                break;
            case 'user_aktivated' :
                $pesan = "Akun pengendara berhasil diaktifkan.";
                break;
            case 'user_deactivated':
                $pesan = "Akun pengendara berhasil dinonaktifkan";
                break;
            case 'maintenance_created':
                $pesan = "Jadwal maintenance berhasil ditambahkan!";
                break;
            case 'maintenance_updated':
                $pesan = "Status maintenance berhasil diperbarui!";
                break;
            case 'maintenance_deleted':
                $pesan = "Jadwal maintenance berhasil dihapus!";
                break;
            default:
                $pesan = ucfirst($_GET['success']) . " berhasil.";
        }
        echo alert_success($pesan);
    }
    
    // cek apakah ada pesan error
    if (isset($_GET['error'])) {
        $pesan = '';
        switch ($_GET['error']) {
            case 'invalid_credentials':
                $pesan = "Email atau kata sandi salah.";
                break;
            case 'account_locked':
                $pesan = "Akun Anda terkunci. Silahkan hubungi admin.";
                break;
            case 'unauthorized_access':
                $pesan = "Akses tidak sah. Silahkan login terlebih dahulu.";
                break;
            case 'session_expired':
                $pesan = "Sesi anda telah berakhir. Silahkan Login ulang.";
                break;
            case 'empty_fields':
                $pesan = "Harap isi semua bidang yang diperlukan.";
                break;
            case 'password_mismatch':
                $pesan = "Kata sandi tidak sesuai.";
                break;
            case 'email_exists':
                $pesan = "Email sudah terdaftar.";
                break;
            case 'nama_mitra_exists':
                $pesan = "Nama mitra sudah ada silahkan masukan nama mitra anda yang benar";
                break;
            case 'nama_pengendara_exists':
                $pesan = "Nama pengendara sudah ada silahkan masukan nama pengendara yang benar";
                break;
            case 'invalid_email':
                $pesan = "Format email tidak valid.";
                break;
            case 'weak_password':
                $pesan = "Kata sandi terlalu lemah. Gunakan kombinasi huruf, angka, dan simbol.";
                break;
            case 'file_too_large':
                $pesan = "Ukuran file terlalu besar.";
                break;
            case 'upload_error':
                $pesan = "Terjadi kesalahan saat mengunggah file.";
                break;
            case 'not_found':
                $pesan = "Data yang diminta tidak ditemukan.";
                break;
            case 'update_failed':
                $pesan = "Gagal memperbarui data. Silahkan coba lagi.";
                break;
            case 'email_not_verified':
                $pesan = "Email belum diverifikasi. silahkan cek inbox email Anda.";
                break;
            case 'account_pending':
                $pesan = "Akun Anda masih dalam proses verifikasi oleh admin. Mohon tunggu konfirmasi melalui email.";
                break;
            case 'account_rejected':
                $pesan = "Pendaftaran Anda telah ditolak oleh admin. Silakan hubungi admin untuk informasi lebih lanjut.";
                break;
            case 'account_inactive':
                $pesan = "Akun Anda sedang tidak aktif. Silakan hubungi admin.";
                break;
            case 'email_failed':
                $pesan = "Gagal mengirim email verifikasi. silahkan coba lagi.";
                break;
            case 'invalid_token':
                $pesan = "Link verifikasi tidak valid atau sudah kadaluarsa.";
                break;
            case 'verification_failed':
                $pesan = "Verifikasi email gagal. Silakan coba lagi atau hubungi admin.";
                break;
            case 'already_verified':
                $pesan = "Email sudah diverifikasi sebelumnya.";
                break;
            case 'database_error':
                $pesan = "Terjadi kesalahan database. Silakan coba lagi.";
                break;
            case 'invalid_input':
                $pesan = "Input tidak valid!";
                break;
            case 'invalid_request':
                $pesan = "Request tidak valid!";
                break;
            case 'unknown_action':
                $pesan = "Aksi tidak dikenali!";
                break;
            case 'maintenance_not_found':
                $pesan = "Jadwal maintenance tidak ditemukan.";
                break;
            case 'invalid_date':
                $pesan = "Tanggal tidak valid. Tanggal selesai harus setelah tanggal mulai.";
                break;
            case 'maintenance_conflict':
                $pesan = "Sudah ada jadwal maintenance untuk stasiun ini pada waktu tersebut.";
                break;
            case 'foto_error':
                $pesan = "Gagal memproses gambar!";
                break;
            case 'table_error':
                $pesan = "Tabel foto_profil belum dibuat!";
                break;
            case 'save_error':
                $pesan = "Gagal menyimpan foto ke database.";
                break;
            case 'invalid_file':
                $pesan = "File tidak valid! Hanya JPG, PNG, GIF (Max 2MB)";
                break;
            default:
                $pesan = "Terjadi kesalahan: " . htmlspecialchars($_GET['error']);
        }
        echo alert_error($pesan);
    }

    // cek pesan warning
    if (isset($_GET['warning'])) {
        $pesan = htmlspecialchars($_GET['warning']);
        echo alert_warning($pesan);
    }

    // cek pesan info
    if (isset($_GET['info'])) {
        $pesan = htmlspecialchars($_GET['info']);
        echo alert_info($pesan);
    }

    // cek pesan dari session (untuk flash message)
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        switch ($flash['type']) {
            case 'success':
                echo alert_success($flash['message']);
                break;
            case 'error':
                echo alert_error($flash['message']);
                break;
            case 'warning':
                echo alert_warning($flash['message']);
                break;
            case 'info':
                echo alert_info($flash['message']);
                break;
        }
        // hapus pesan flash setelah ditampilkan
        unset($_SESSION['flash_message']);
    }
}

// fungsi pembantu untuk membuat alert HTML dengan unique ID
function alert_success($message) {
    $id = 'alert-' . uniqid();
    return "<div id='{$id}' class='alert alert-success' role='alert'>
                <span class='alert-icon'>✓</span>
                <span class='alert-message'>{$message}</span>
                <button class='alert-close' onclick='this.parentElement.remove()'>×</button>
            </div>
            <script>
                (function() {
                    const alert = document.getElementById('{$id}');
                    if (alert) {
                        setTimeout(function() {
                            alert.classList.add('hiding');
                            setTimeout(() => alert.remove(), 300);
                        }, 5000);
                    }
                })();
            </script>";
}

function alert_error($message) {
    $id = 'alert-' . uniqid();
    return "<div id='{$id}' class='alert alert-error' role='alert'>
                <span class='alert-icon'>✕</span>
                <span class='alert-message'>{$message}</span>
                <button class='alert-close' onclick='this.parentElement.remove()'>×</button>
            </div>
            <script>
                (function() {
                    const alert = document.getElementById('{$id}');
                    if (alert) {
                        setTimeout(function() {
                            alert.classList.add('hiding');
                            setTimeout(() => alert.remove(), 300);
                        }, 5000);
                    }
                })();
            </script>";
}

function alert_warning($message) {
    $id = 'alert-' . uniqid();
    return "<div id='{$id}' class='alert alert-warning' role='alert'>
                <span class='alert-icon'>⚠</span>
                <span class='alert-message'>{$message}</span>
                <button class='alert-close' onclick='this.parentElement.remove()'>×</button>
            </div>
            <script>
                (function() {
                    const alert = document.getElementById('{$id}');
                    if (alert) {
                        setTimeout(function() {
                            alert.classList.add('hiding');
                            setTimeout(() => alert.remove(), 300);
                        }, 5000);
                    }
                })();
            </script>";
}

function alert_info($message) {
    $id = 'alert-' . uniqid();
    return "<div id='{$id}' class='alert alert-info' role='alert'>
                <span class='alert-icon'>ℹ</span>
                <span class='alert-message'>{$message}</span>
                <button class='alert-close' onclick='this.parentElement.remove()'>×</button>
            </div>
            <script>
                (function() {
                    const alert = document.getElementById('{$id}');
                    if (alert) {
                        setTimeout(function() {
                            alert.classList.add('hiding');
                            setTimeout(() => alert.remove(), 300);
                        }, 5000);
                    }
                })();
            </script>";
}

// fungsi untuk mengatur pesan flash
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

// cara penggunaan:
// contoh: ?success=register
// contoh: ?error=invalid_credentials
// seperti: login.php?success=logout


?>