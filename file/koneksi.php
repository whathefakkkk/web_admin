<?php
$server = "";     // Host database (default: localhost)
$user   = "";     // Username database (contoh: root)
$pass   = "";     // Password database (biarkan kosong jika tidak ada)
$db     = "";     // Nama database


$koneksi = mysqli_connect($server, $user, $pass, $db);
mysqli_query($koneksi, "SET time_zone = '+08:00'"); //sesuaikan dengan time zone anda
date_default_timezone_set('Asia/Singapore');
if (!$koneksi) {
	die("Koneksi Gagal : " . mysqli_connect_error());
}
?>