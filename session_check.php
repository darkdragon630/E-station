<?php
session_start();
echo "<h3>🔍 Check Session</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['role'])) {
    echo "<br>Role detected: " . $_SESSION['role'];
    echo "<br><a href='admin/dashboard.php'>Go to admin dashboard</a>";
} else {
    echo "<br>No session found. <a href='../auth/login.php'>Login</a>";
}
?>