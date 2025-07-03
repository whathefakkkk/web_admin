<?php
include 'koneksi.php';

$email = $_GET['email'];
$cek = mysqli_query($koneksi, "SELECT email FROM users WHERE email='$email'");

if (mysqli_num_rows($cek) > 0) {
    echo "Email sudah digunakan!";
} else {
    echo "";
}
?>