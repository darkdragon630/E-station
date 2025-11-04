<?php
session_start();
require_once "koneksi.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    try {
        // Cek Admin dengan PDO
        $stmt = $koneksi->prepare("SELECT id_admin, username, password, nama_admin, email FROM `admin` WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row && password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id_admin'];
            $_SESSION['role'] = 'admin';
            $_SESSION['nama'] = $row['nama_admin'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['email'] = $row['email'];
            
            header("Location: admin/dashboard.php");
            exit();
        }
        
        // Cek Pengendara
        $stmt = $koneksi->prepare("SELECT * FROM `pengendara` WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row && password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id_pengendara'];
            $_SESSION['role'] = 'pengendara';
            $_SESSION['nama'] = $row['nama_pengendara'];
            
            header("Location: pengendara/dashboard.php");
            exit();
        }
        
        // Cek Mitra
        $stmt = $koneksi->prepare("SELECT * FROM `mitra` WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row && password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id_mitra'];
            $_SESSION['role'] = 'mitra';
            $_SESSION['nama'] = $row['nama_mitra'];
            
            header("Location: mitra/dashboard.php");
            exit();
        }
        
        // Jika semua gagal
        header("Location: login.php?error=1");
        exit();
        
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// Jika akses langsung tanpa POST
header("Location: login.php");
exit();
?>
