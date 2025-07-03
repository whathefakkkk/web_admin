<?php
session_start();
if (!isset($_SESSION['nama_pengguna'])) {
    header("Location: login.php");
    exit;
}
$nama = $_SESSION['nama_pengguna'];

include 'koneksi.php';
include 'log_aktivitas.php';

function bersih($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

date_default_timezone_set('Asia/Singapore');
function salamWaktu() {
    $jam = date("H");
    if ($jam >= 5 && $jam < 11) {
        return "Selamat pagi";
    } elseif ($jam >= 11 && $jam < 15) {
        return "Selamat siang";
    } elseif ($jam >= 15 && $jam < 18) {
        return "Selamat sore";
    } else {
        return "Selamat malam";
    }
}

// Handle pembayaran dari kasir
if (isset($_POST['bayar'])) {
    $id_transaksi = (int) $_POST['id_transaksi'];
    $total = (int) $_POST['total'];

    // Update status & waktu_bayar
    mysqli_query($koneksi, "UPDATE transaksi SET 
        status = 'selesai', 
        waktu_bayar = NOW(),
        total = $total
        WHERE id_transaksi = $id_transaksi") or die(mysqli_error($koneksi));

    catatAktivitas($koneksi, "Kasir membayar transaksi ID: $id_transaksi dengan total Rp$total");
    echo "<script>alert('Transaksi Berhasil Dibayar!'); window.location.href='kasir.php';</script>";
    exit;
}

// Handle pembatalan transaksi
if (isset($_POST['batal'])) {
    $id_transaksi = (int) $_POST['id_transaksi'];
    mysqli_query($koneksi, "UPDATE transaksi SET status = 'batal' WHERE id_transaksi = $id_transaksi") or die(mysqli_error($koneksi));
    catatAktivitas($koneksi, "Kasir membatalkan transaksi ID: $id_transaksi");
    echo "<script>alert('Transaksi Dibatalkan.'); window.location.href='kasir.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title> Kasir </title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f4f4;
        }
        .sidebar {
            width: 220px;
            height: 100vh;
            position: fixed;
            background: #343a40;
            color: #fff;
            padding-top: 20px;
        }
        .sidebar a {
            color: #fff;
            display: block;
            padding: 10px 20px;
            text-decoration: none;
        }
        .sidebar a.active,
        .sidebar a:hover {
            background: #495057;
        }
        .main-content {
            margin-left: 220px;
            padding: 30px;
        }
        .menu-card {
            border: 1px solid #ccc;
            border-radius: 10px;
            text-align: center;
            padding: 10px;
            background: #fff;
            height: 100%;
        }
        .menu-card img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body>
<div class="sidebar">
    <h5 class="text-center mb-4"> Kasir </h5>
    <a href="kasir.php" class="active"><i data-feather="shopping-cart"></i> Transaksi</a>
    <a href="logout.php"><i data-feather="log-out"></i> Keluar</a>
</div>

<div class="main-content">
    <h4 class="mb-4"><?= salamWaktu() . ', ' . $nama ?> 👋</h4>
    <h5 class="mb-4 text-muted">Daftar Pesanan Masuk</h5>

    <div class="row">
        <?php
        $q = mysqli_query($koneksi, "SELECT * FROM transaksi WHERE status='diproses' ORDER BY waktu_pesan ASC");
        while ($trx = mysqli_fetch_array($q)) {
            $detail = mysqli_query($koneksi, "SELECT * FROM detail_transaksi WHERE id_transaksi={$trx['id_transaksi']}");
        ?>
        <div class="col-md-4 mb-3">
            <div class="menu-card">
                <h6>#<?= $trx['id_transaksi'] ?> - <?= ucfirst($trx['jenis']) ?></h6>
                <small><?= $trx['nama_pelanggan'] ?> 
                    <?php if ($trx['jenis'] === 'dine-in') echo '(Meja ' . $trx['no_meja'] . ')'; ?>
                </small>
                <ul class="mt-2" style="font-size: 14px; text-align: left;">
                    <?php while ($d = mysqli_fetch_array($detail)) {
                        echo "<li>{$d['nama_menu']} x{$d['jumlah']} - Rp" . number_format($d['subtotal']) . "</li>";
                    } ?>
                </ul>
                <form method="post">
                    <input type="hidden" name="id_transaksi" value="<?= $trx['id_transaksi'] ?>">
                    <input type="hidden" name="total" value="<?= $trx['total'] ?>">
                    <div class="text-end mt-2">
                        <small>Total: <strong>Rp<?= number_format($trx['total']) ?></strong></small><br>
                        <button type="submit" name="bayar" class="btn btn-success btn-sm mt-2">Bayar</button>
                        <button type="submit" name="batal" class="btn btn-outline-danger btn-sm mt-2" onclick="return confirm('Yakin ingin membatalkan transaksi ini?')">Batal</button>
                    </div>
                </form>
            </div>
        </div>
        <?php } ?>
    </div>
</div>

<script src="../js/bootstrap.min.js"></script>
<script>feather.replace();</script>
</body>
</html>
