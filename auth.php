<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Registrasi</title>
    <style>
        .tab {cursor:pointer;padding:10px 20px;display:inline-block;background:#ddd;margin-right:5px;border-radius:5px}
        .active {background:#bbb;font-weight:bold}
        .form-box {display:none;margin-top:20px}
    </style>
</head>
<body>

<h2>Registrasi Akun</h2>

<div>
    <span class="tab active" onclick="switchTab('pengendara')">Pengendara</span>
    <span class="tab" onclick="switchTab('mitra')">Mitra</span>
</div>

<div id="pengendara" class="form-box" style="display:block;">
    <h3>Daftar Pengendara</h3>
    <form action="auth/process_register_pengendara.php" method="POST">
        <input type="text" name="nama" placeholder="Nama" required><br>
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Daftar Pengendara</button>
    </form>
</div>

<div id="mitra" class="form-box">
    <h3>Daftar Mitra</h3>
    <form action="auth/process_register_mitra.php" method="POST">
        <input type="text" name="nama" placeholder="Nama Mitra" required><br>
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Daftar Mitra</button>
    </form>
</div>

<p>Sudah punya akun? <a href="login.php">Login di sini</a></p>

<script>
function switchTab(tabId) {
    document.getElementById('pengendara').style.display = 'none';
    document.getElementById('mitra').style.display = 'none';
    document.getElementById(tabId).style.display = 'block';

    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    event.target.classList.add('active');
}
</script>

</body>
</html>
