<?php

$host      ="localhost";
$user      ="root";
$pass      ="";
$db        ="db_estation";

$koneksi   = mysqli_connect($host,$user,$pass,$db);
if (!$koneksi){
    die("Gagal terhubung");
}