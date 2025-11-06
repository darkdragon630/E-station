<?php
//memulai sesi
session_start();

// menghubungkan ke database
require_once "../config/koneksi.php";

// Cek apakah sudah login sebagai admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pengendara') {
    header("Location: ../auth/login.php");
    exit();
}

// kode dashboard pengendara di sini