Form login dengan field email dan password tersedia

- Sistem validasi kredensial dan tampilkan pesan error jika salah

- Redirect ke dashboard setelah login berhasil

- Session tersimpan hingga user melakukan logout

- Fitur "Lupa Password" dan link ke halaman registrasi tersedia

<?php
// memeulai sesi
session_start();
// menghubungkan ke database
require_once "../config/koneksi.php";

// mengatasi SQL injection


// menangani data dari form login
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $email = trim($_POST['email']); 
    $password = trim($_POST['password']);
}

// Validasi: Field kosong
if (empty($email) || empty($password)) {
    header("Location: login.php?error=empty_fields");
    exit();
}

// cek apakah admin ada di database
$stmt = $koneksi->prepare("SELECT * FROM admin WHERE email = ? LIMIT 1");
$stmt->execute([$email]);

if ($stmt->rowCount() == 1) {
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    // verifikasi password
    if (password_verify($password, $user['password'])) {
        // set session
        $_SESSION['user_id'] = $user['id_admin'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['role'] = 'admin';
        // arahkan ke dashboard admin
        header("Location: ../admin/dashboard.php?success=login");
        exit();
    }
}


// cek apakah pengendara ada di database
$stmt = $koneksi->prepare("SELECT * FROM pengendara WHERE email = ? LIMIT 1");
$stmt->execute([$email]);

if ($stmt->rowCount() == 1) {
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    // verifikasi password
    if (password_verify($password, $user['password'])) {
        // set session
        $_SESSION['user_id'] = $user['id_pengendara'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['role'] = 'pengendara';
        // arahkan ke dashboard pengendara
        header("Location: ../pengendara/dashboard.php?success=login");
        exit();
    }
}

// cek apakah mitra ada di database
$stmt = $koneksi->prepare("SELECT * FROM mitra WHERE email = ? LIMIT 1");
$stmt->execute([$email]);


if ($stmt->rowCount() == 1) {
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    // verifikasi password
    if (password_verify($password, $user['password'])) {
        // set session
        $_SESSION['user_id'] = $user['id_mitra'];
        $_SESSION['nama'] = $user['nama_mitra'];
        $_SESSION['role'] = 'mitra';
        // arahkan ke dashboard pengendara
        header("Location: ../mitra/dashboard.php?success=login");
        exit();
    }

    // jika gagal, kembali ke halaman login dengan pesan error
    header("Location: login.php?error=invalid_credentials");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>loading proses</title>
</head>
<body>
    <h2>Memproses Login...</h2>
</body>
</html>  <?php
// memulai sesi
session_start();
// menghubungkan ke database
require_once '../config/koneksi.php';
// menghubungkan fungsi pesan
require_once "../pesan/alerts.php";

// Redirect jika sudah login
if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } elseif ($_SESSION['role'] === 'pengendara') {
        header("Location: pengendara/dashboard.php");
    } elseif ($_SESSION['role'] === 'mitra') {
        header("Location: mitra/dashboard.php");
    } else {
        session_destroy(); // role tidak dikenal, paksa login ulang
        header("Location: login.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>E-Station | Login</title>
  <link rel="stylesheet" href="../style.css">
  <link rel="icon" type="image/png" href="../images/Logo_1.jpeg">
</head>
<body>
  <!-- Loading screen -->
  <div id="loading-screen">
    <div class="loader">
      <div class="electric=circle"></div>  
      <img src="../images/Logo_1.jpeg" alt="Logo E-Station">
      <h2>E-STATION</h2>
    </div>
  </div>

  <!-- Tombol toggle tema -->
  <div class="theme-toggle">
    <button id="toggleTheme" aria-label="Ganti Tema">🌙</button>
  </div>

  <!-- Kontainer login -->
  <div class="container">
    <div class="login-card">
      <h1 class="title">E-STATION</h1>
      <p class="subtitle">(Layanan Pengisian Kendaraan Listrik)</p>

      <div class="illustration">
        <img src="../images/Logo_1.jpeg" alt="Logo E-Station">
      </div>
      <?php tampilkan_alert(); ?>
      <form action="login_process.php" method="POST" class="login-form">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Masukkan email" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Masukkan kata sandi" required>

        <div class="options">
          <label class="remember">
            <input type="checkbox" name="remember"> Ingat saya
          </label>
          <a href="#" class="forgot-link">Lupa kata sandi?</a>
        </div>

        <button type="submit" class="btn-login">Masuk</button>
      </form>

      <div class="register">
        <p>Belum punya akun? <a href="auth.php">Daftar di sini</a></p>
      </div>
    </div>
  </div>

  <script src="../script.js"></script>
</body>
</html>

