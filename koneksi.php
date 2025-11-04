<?php

$host      ="localhost";
$user      ="root";
$pass      ="";
$db        ="db_e-station";


$koneksi   = mysqli_connect($host,$user,$pass,$db);
if (!$koneksi){
    die("Gagal terhubung");
}
?>
