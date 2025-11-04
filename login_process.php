<?php
session_start();
require_once "koneksi.php";

// Log untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    echo "<h3>🔍 DEBUG LOGIN PROCESS</h3>";
    echo "Email: " . htmlspecialchars($email) . "<br>";
    echo "Password: " . htmlspecialchars($password) . "<br><br>";
    
    // Cek Admin
    $stmt = mysqli_prepare($koneksi, "SELECT id_admin, username, password, nama_admin, email FROM `admin` WHERE email = ? LIMIT 1");
    
    if (!$stmt) {
        die("❌ Prepare failed: " . mysqli_error($koneksi));
    }
    
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    echo "Rows found: " . mysqli_num_rows($result) . "<br><br>";
    
    if ($row = mysqli_fetch_assoc($result)) {
        echo "✅ Data admin ditemukan!<br>";
        echo "- ID: " . $row['id_admin'] . "<br>";
        echo "- Username: " . $row['username'] . "<br>";
        echo "- Nama: " . $row['nama_admin'] . "<br>";
        echo "- Email: " . $row['email'] . "<br>";
        echo "- Password hash: " . substr($row['password'], 0, 30) . "...<br><br>";
        
        $verify = password_verify($password, $row['password']);
        echo "Password verify: " . ($verify ? "✅ TRUE" : "❌ FALSE") . "<br><br>";
        
        if ($verify) {
            echo "🎉 <strong>PASSWORD COCOK! Setting session...</strong><br>";
            
            $_SESSION['user_id'] = $row['id_admin'];
            $_SESSION['role'] = 'admin';
            $_SESSION['nama'] = $row['nama_admin'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['email'] = $row['email'];
            
            echo "Session set:<br>";
            echo "- user_id: " . $_SESSION['user_id'] . "<br>";
            echo "- role: " . $_SESSION['role'] . "<br>";
            echo "- nama: " . $_SESSION['nama'] . "<br><br>";
            
            echo "🚀 <strong>Redirecting to admin/dashboard.php...</strong><br>";
            echo "<br><a href='admin/dashboard.php' style='padding: 10px 20px; background: green; color: white; text-decoration: none; border-radius: 5px;'>Click here jika tidak auto redirect</a>";
            
            // Coba redirect
            header("Location: admin/dashboard.php");
            exit();
        } else {
            echo "❌ <strong>PASSWORD TIDAK COCOK!</strong><br>";
        }
    } else {
        echo "❌ <strong>Email tidak ditemukan di database!</strong><br>";
    }
    
    mysqli_stmt_close($stmt);
    
    echo "<br><br>❌ Login gagal - akan redirect ke login.php?error=1";
    echo "<br><a href='login.php?error=1'>Back to login</a>";
    exit();
}
?>