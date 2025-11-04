<?php
$password = "admin123";
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Hash untuk 'admin123': " . $hash;
?>