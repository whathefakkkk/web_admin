<?php
session_start();
include './file/koneksi.php';
include './file/log_aktivitas.php';

function bersihkan($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Handle Login
if (isset($_POST['login'])) {
    $email = bersihkan($_POST['email']);
    $pass = $_POST['pass'];

    $cek = mysqli_query($koneksi, "SELECT * FROM users WHERE email='$email' LIMIT 1");
    $data = mysqli_fetch_assoc($cek);

    if ($data && password_verify($pass, $data['pass'])) {
        $status = strtolower($data['status']);
        $role = strtolower($data['role']);

        if ($status === 'aktif') {
            $_SESSION['id_user'] = $data['id_user'];
            $_SESSION['nama_pengguna'] = $data['nama_pengguna'];
            $_SESSION['role'] = $data['role'];

            // Log login berhasil
            catatAktivitas($koneksi, "Login berhasil sebagai {$data['nama_pengguna']}", $data['nama_pengguna']);

            if ($data['role'] === 'admin') {
                header('Location:admin.php');
                exit;
            } elseif ($data['role'] === 'kasir') {
                header('Location:kasir.php');
                exit;
            } else {
                $error = "Role tidak dikenal.";
            }
        } elseif ($status === 'nonaktif') {
            $error = "Akun Anda belum diaktifkan. Silakan hubungi admin.";
        } elseif ($status === 'diblokir') {
            $error = "Akun Anda diblokir. Hubungi administrator.";
        } else {
            $error = "Status akun tidak dikenali.";
        }
    } else {
        $error = "Email atau Password salah!";
        // Log login gagal
        catatAktivitas($koneksi, "Gagal login dengan email $email", $email);
    }
}

// Handle Signup
if (isset($_POST['signup'])) {
    $nama = bersihkan($_POST['nama_pengguna']);
    $email = bersihkan($_POST['email']);
    $pass = password_hash($_POST['pass'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $cek_email = mysqli_query($koneksi, "SELECT * FROM users WHERE email='$email'");
    if (mysqli_num_rows($cek_email) > 0) {
        $error = "Email sudah terdaftar!";
    } else {
        $simpan = mysqli_query($koneksi, "INSERT INTO users (nama_pengguna, email, pass, role, status) VALUES ('$nama','$email','$pass','$role','aktif')");
        if ($simpan) {
            $success = "Akun berhasil dibuat! Silakan login.";
            // Log signup berhasil
            catatAktivitas($koneksi, "Signup berhasil untuk $nama", $nama);
        } else {
            $error = "Gagal membuat akun!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login & Sign Up</title>
    <link href="./css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f4f4;
        }
        .card {
            margin-top: 60px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .form-switcher {
            text-align: center;
            margin-top: 1rem;
        }
        .form-switcher a {
            cursor: pointer;
            color: #0d6efd;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
            <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>

            <div class="card">
                <div class="card-body">
                    <!-- Login Form -->
                    <form id="form-login" method="post" <?= isset($_POST['signup']) ? 'style="display:none;"' : '' ?> autocomplete="off">
                        <h3 class="text-center mb-4">Login</h3>
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="text" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="pass" class="form-control" required>
                        </div>
                        <button type="submit" name="login" class="btn btn-primary w-100">Masuk</button>
                        <div class="form-switcher mt-3">
                            Belum punya akun? <a onclick="toggleForm()">Daftar</a>
                        </div>
                    </form>

                    <!-- Signup Form -->
                    <form id="form-signup" method="post" <?= isset($_POST['signup']) ? '' : 'style="display:none;"' ?> autocomplete="off">
                        <h3 class="text-center mb-4">Sign Up</h3>
                        <div class="mb-3">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama_pengguna" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="text" name="email" class="form-control" required onkeyup="cekEmail(this.value)">
                            <div id="cek-email" class="form-text text-danger"></div>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="pass" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Role</label>
                            <select name="role" class="form-select" required>
                                <option value="">-- Pilih Role --</option>
                                <option value="admin">Admin</option>
                                <option value="kasir">Kasir</option>
                            </select>
                        </div>
                        <button type="submit" name="signup" class="btn btn-success w-100">Daftar</button>
                        <div class="form-switcher mt-3">
                            Sudah punya akun? <a onclick="toggleForm()">Login</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="./js/bootstrap.min.js"></script>
<script>
function toggleForm() {
    document.getElementById("form-login").style.display =
        document.getElementById("form-login").style.display === "none" ? "block" : "none";
    document.getElementById("form-signup").style.display =
        document.getElementById("form-signup").style.display === "none" ? "block" : "none";
}

function cekEmail(email) {
    if (email.length === 0) {
        document.getElementById('cek-email').innerText = "";
        return;
    }
    fetch("./file/cek_email.php?email=" + email)
        .then(res => res.text())
        .then(res => {
            document.getElementById('cek-email').innerText = res;
        });
}
</script>
</body>
</html>
