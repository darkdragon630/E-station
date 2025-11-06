<?php
// memulai sesi
session_start();
// menghapus semua variabel sesi
$_SESSION = [];
// menghancurkan sesi
session_destroy();
// mengarahkan pengguna ke halaman login setelah logout
header("Location: login.php?success=logout");
exit();
?>