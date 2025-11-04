<?php
require_once "koneksi.php";

// Data yang akan ditest
$email = "admin@transport.com";
$password = "admin123";

echo "<h2>🔍 Login Debug Test</h2>";
echo "<hr>";

// Cek koneksi database
if (!$koneksi) {
    die("❌ Koneksi database GAGAL: " . mysqli_connect_error());
}
echo "✅ Koneksi database: <strong>BERHASIL</strong><br><br>";

// Query data admin
$query = "SELECT * FROM `admin` WHERE email = '$email' LIMIT 1";
echo "<strong>Query:</strong> $query<br><br>";

$result = mysqli_query($koneksi, $query);

if (!$result) {
    die("❌ Query ERROR: " . mysqli_error($koneksi));
}

$row_count = mysqli_num_rows($result);
echo "<strong>Jumlah data ditemukan:</strong> $row_count<br><br>";

if ($row_count > 0) {
    $row = mysqli_fetch_assoc($result);
    
    echo "<div style='background: #e8f5e9; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>📊 Data Admin dari Database:</h3>";
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>ID Admin</td><td>" . $row['id_admin'] . "</td></tr>";
    echo "<tr><td>Username</td><td>" . $row['username'] . "</td></tr>";
    echo "<tr><td>Email</td><td>" . $row['email'] . "</td></tr>";
    echo "<tr><td>Nama Admin</td><td>" . $row['nama_admin'] . "</td></tr>";
    echo "<tr><td>Password Hash (60 char)</td><td style='font-family: monospace; font-size: 11px;'>" . substr($row['password'], 0, 60) . "...</td></tr>";
    echo "<tr><td>Password Length</td><td>" . strlen($row['password']) . " characters</td></tr>";
    echo "</table>";
    echo "</div>";
    
    echo "<hr>";
    echo "<h3>🔐 Test Password Verification:</h3>";
    echo "<div style='background: #fff3e0; padding: 15px; border-radius: 5px;'>";
    echo "<strong>Password yang ditest:</strong> <code>$password</code><br>";
    echo "<strong>Hash di database:</strong> <code style='font-size: 11px;'>" . $row['password'] . "</code><br><br>";
    
    // Test password_verify
    $verify_result = password_verify($password, $row['password']);
    
    if ($verify_result) {
        echo "<div style='color: green; font-size: 18px; font-weight: bold;'>✅ PASSWORD COCOK! Login seharusnya BERHASIL</div>";
    } else {
        echo "<div style='color: red; font-size: 18px; font-weight: bold;'>❌ PASSWORD TIDAK COCOK!</div>";
        echo "<br><strong>Kemungkinan penyebab:</strong><br>";
        echo "1. Hash password di database salah<br>";
        echo "2. Password 'admin123' bukan password yang benar<br>";
        echo "3. Ada karakter tambahan (spasi, dll) di database<br>";
    }
    echo "</div>";
    
    echo "<hr>";
    echo "<h3>🔧 Generate Hash Baru:</h3>";
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
    $new_hash = password_hash($password, PASSWORD_DEFAULT);
    echo "<strong>Hash baru untuk password '$password':</strong><br>";
    echo "<textarea style='width: 100%; height: 80px; font-family: monospace; font-size: 11px;'>$new_hash</textarea>";
    echo "<br><br><strong>Update dengan query ini:</strong><br>";
    echo "<textarea style='width: 100%; height: 100px; font-family: monospace; font-size: 12px;'>UPDATE `admin` 
SET `password` = '$new_hash' 
WHERE username = 'admin';</textarea>";
    echo "</div>";
    
} else {
    echo "<div style='color: red; font-weight: bold;'>❌ Email '$email' TIDAK DITEMUKAN di database!</div>";
    echo "<br><strong>Cek data admin yang ada:</strong><br>";
    
    $all_admin = mysqli_query($koneksi, "SELECT id_admin, username, email, nama_admin FROM `admin`");
    if (mysqli_num_rows($all_admin) > 0) {
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; margin-top: 10px;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Nama</th></tr>";
        while ($admin = mysqli_fetch_assoc($all_admin)) {
            echo "<tr>";
            echo "<td>" . $admin['id_admin'] . "</td>";
            echo "<td>" . $admin['username'] . "</td>";
            echo "<td>" . ($admin['email'] ?: '<em>NULL</em>') . "</td>";
            echo "<td>" . $admin['nama_admin'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

mysqli_close($koneksi);
?>
