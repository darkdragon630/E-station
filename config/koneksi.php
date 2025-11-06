<?php
$host = 'db.fr-pari1.bengt.wasmernet.com';
$db = 'db3DFnrkuWH3yj3sG6vTywBz';
$user = '9bc090467c688000d63631589908';
$pass = '06909bc0-9046-7e21-8000-540f6e7ee1a1';
$port = '10272';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;port=$port;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $koneksi = new PDO($dsn, $user, $pass, $options);
    $koneksi->exec("SET NAMES 'utf8'");
} catch (PDOException $e) {
    die('koneksi gagal: ' . $e->getMessage());
}

?>