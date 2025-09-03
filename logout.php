<?php
include './file/koneksi.php';
include './file/log_aktivitas.php';
session_start();

$nama = $_SESSION['nama_pengguna'] ?? 'Unknown';
catatAktivitas($koneksi, "Logout", $nama);

session_unset();
session_destroy();
header("Location: login.php");
exit;
?>
