<?php

$host      ="localhost";
$user      ="root";
$pass      ="";
$db        ="db_e-station";

$koneksi   = mysqli_connect($host,$user,$pass,$db);
if (!$koneksi){
    die("Gagal terhubung");
}

// ✅ Cek & buat admin otomatis jika belum ada
$admin_email     = "admin@estation.com";
$admin_username  = "admin";
$admin_nama      = "Administrator";
$admin_password  = password_hash("admin123", PASSWORD_DEFAULT);

$cek = $koneksi->query("SELECT * FROM admin WHERE email = '$admin_email'");

if ($cek->num_rows == 0) {
    $koneksi->query("INSERT INTO admin (username, nama_admin, email, password, created_at) 
                  VALUES ('$admin_username', '$admin_nama', '$admin_email', '$admin_password', NOW())");
}
?>