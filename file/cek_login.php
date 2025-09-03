<?php
session_start();
include 'koneksi.php';

function bersihkan($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

$email = bersihkan($_POST['email']);
$pass_input = bersihkan($_POST['pass']);

$query = mysqli_query($koneksi, "SELECT * FROM users WHERE email='$email' LIMIT 1");
$user = mysqli_fetch_assoc($query);

if ($user && password_verify($pass_input, $user['pass'])) {
    // Login sukses
    $_SESSION['user'] = $user;

    // Cek role & redirect sesuai
    if ($user['role'] === 'admin') {
        header("Location: ../admin.php");
        exit;
    } elseif ($user['role'] === 'kasir') {
        header("Location: ../kasir.php");
        exit;
    } else {
        echo "<script>alert('Role tidak dikenali'); window.location.href='../login.php';</script>";
    }
    exit;
} else {
    echo "<script>alert('Email atau password salah'); window.location.href='../login.php';</script>";
}
?>
