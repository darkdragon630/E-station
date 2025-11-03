<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>E-Station | Login</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <!-- Loading screen -->
  <div id="loading-screen">
    <div class="loader">
      <div class="electric=circle"></div>  
      <img src="images/Logo_1.jpeg" alt="Logo E-Station">
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
        <img src="images/Logo_1.jpeg" alt="Logo E-Station">
      </div>

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
        <p>Belum punya akun? <a href="#">Daftar di sini</a></p>
      </div>
    </div>
  </div>

  <script src="script.js"></script>
</body>
</html>