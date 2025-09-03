<?php
session_start();
include './file/koneksi.php';
include './file/log_aktivitas.php';

function rupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

$menu = mysqli_query($koneksi, "SELECT * FROM menu WHERE status = 'aktif' ORDER BY id_menu DESC");

if (isset($_POST['kirim'])) {
    $nama    = htmlspecialchars(trim($_POST['nama']));
    $jenis   = $_POST['jenis'];
    $meja    = ($jenis === 'dine-in' && isset($_POST['meja']) && is_numeric($_POST['meja'])) ? intval($_POST['meja']) : null;
    $tanggal = date('Y-m-d H:i:s');

    $pesanan = [];
    $total   = 0;

    foreach ($_POST['pesanan'] as $id => $jumlah) {
        $jumlah = intval($jumlah);
        if ($jumlah > 0) {
            $data       = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM menu WHERE id_menu=$id"));
            $harga      = intval($data['harga']);
            $subtotal   = $harga * $jumlah;
            $total     += $subtotal;

            $pesanan[$id] = [
                'jumlah'     => $jumlah,
                'nama_menu'  => $data['nama_menu'],
                'subtotal'   => $subtotal
            ];
        }
    }

    if (!empty($pesanan)) {
        $pajak        = $total * 0.1;
        $total_bayar  = $total + $pajak;
        $meja_sql     = is_null($meja) ? 'NULL' : $meja;

        mysqli_query($koneksi, "INSERT INTO transaksi 
            (nama_pelanggan, jenis, no_meja, waktu_pesan, total, status, dibuat_oleh) 
            VALUES 
            ('$nama', '$jenis', $meja_sql, '$tanggal', $total_bayar, 'diproses', 'pelanggan')");

        $id_transaksi = mysqli_insert_id($koneksi);

        foreach ($pesanan as $id => $item) {
            $jumlah     = $item['jumlah'];
            $nama_menu  = mysqli_real_escape_string($koneksi, $item['nama_menu']);
            $subtotal   = $item['subtotal'];

            mysqli_query($koneksi, "INSERT INTO detail_transaksi 
                (id_transaksi, id_menu, nama_menu, jumlah, subtotal) 
                VALUES 
                ($id_transaksi, $id, '$nama_menu', $jumlah, $subtotal)");
        }

        // ✅ Catat aktivitas pelanggan
        catatAktivitas($koneksi, "Pelanggan atas nama $nama mengirim pesanan ($jenis" . ($meja ? " Meja $meja" : "") . ") senilai Rp$total_bayar", null);

        $_SESSION['struk'] = [
            'nama'        => $nama,
            'jenis'       => $jenis,
            'meja'        => $meja,
            'tanggal'     => $tanggal,
            'pesanan'     => $pesanan,
            'total'       => $total,
            'pajak'       => $pajak,
            'total_bayar' => $total_bayar
        ];

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title> Pesanan </title>
    <link href="./css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: url('web-background-silahkan-diganti') no-repeat center center fixed;
            background-size: cover;
        }
        .card-menu {
            width: 180px;
            margin: 10px;
        }
        .card-img-top {
            height: 100px;
            object-fit: cover;
        }
        .menu-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }
    </style>
</head>
<body>
<div class="container py-4">
    <h2 class="text-center mb-4 bg-light p-2 rounded">🛒 Pilih Menu Anda</h2>

    <?php if (!isset($_SESSION['struk'])): ?>
    <form method="post" autocomplete="off">
        <div class="mb-3 row">
            <div class="col-md-4">
                <input name="nama" class="form-control" placeholder="Nama Anda" required>
            </div>
            <div class="col-md-4">
                <select name="jenis" class="form-select" onchange="toggleMeja(this.value)" required>
                    <option value="">Pilih Jenis</option>
                    <option value="dine-in">Dine-in</option>
                    <option value="take-away">Take-away</option>
                </select>
            </div>
            <div class="col-md-4" id="input-meja" style="display:none;">
                <input name="meja" class="form-control" placeholder="Nomor Meja" id="meja">
            </div>
        </div>

        <div class="menu-container">
            <?php while ($row = mysqli_fetch_assoc($menu)): ?>
                <div class="card card-menu">
                    <img src="<?= $row['gambar'] ?>" class="card-img-top" alt="<?= $row['nama_menu'] ?>">
                    <div class="card-body p-2">
                        <h6 class="card-title mb-1"><?= $row['nama_menu'] ?></h6>
                        <p class="card-text mb-1 text-muted"><?= rupiah($row['harga']) ?></p>
                        <input type="number" min="0" name="pesanan[<?= $row['id_menu'] ?>]" class="form-control form-control-sm" placeholder="Jumlah">
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <div class="text-center mt-4">
            <button name="kirim" class="btn btn-success">Kirim Pesanan</button>
        </div>
    </form>
    <?php endif; ?>

    <?php if (isset($_SESSION['struk'])): 
        $struk = $_SESSION['struk'];
        unset($_SESSION['struk']);
    ?>
        <div class="card mt-5 mx-auto" style="max-width:500px">
            <div class="card-header bg-primary text-white">🧾 Struk Pesanan</div>
            <div class="card-body">
                <p><strong>Nama:</strong> <?= $struk['nama'] ?></p>
                <p><strong>Jenis:</strong> <?= ucfirst($struk['jenis']) ?></p>
                <?php if ($struk['meja']): ?>
                    <p><strong>Meja:</strong> <?= $struk['meja'] ?></p>
                <?php endif; ?>
                <p><strong>Tanggal:</strong> <?= $struk['tanggal'] ?></p>
                <hr>
                <ul class="list-group mb-2">
                    <?php foreach ($struk['pesanan'] as $id => $item): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <?= htmlspecialchars($item['nama_menu']) ?> x<?= $item['jumlah'] ?>
                            <span><?= rupiah($item['subtotal']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p><strong>Subtotal:</strong> <?= rupiah($struk['total']) ?></p>
                <p><strong>Pajak (10%):</strong> <?= rupiah($struk['pajak']) ?></p>
                <p><strong>Total Bayar:</strong> <?= rupiah($struk['total_bayar']) ?></p>

                <div class="text-center mt-3">
                    <button class="btn btn-success" onclick="showPopupThenRedirect()">Selesai</button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Popup -->
<div id="popup" class="position-fixed top-50 start-50 translate-middle bg-light shadow rounded text-center p-4" style="display:none; z-index:999;">
    <h4 class="text-success">✅ Terima kasih!</h4>
    <p>Pesanan Anda sedang diproses...</p>
</div>

<script src="./js/bootstrap.min.js"></script>
<script>
function toggleMeja(val) {
    const mejaInput = document.getElementById('input-meja');
    const mejaField = document.getElementById('meja');

    if (val === 'dine-in') {
        mejaInput.style.display = 'block';
        mejaField.setAttribute('required', 'required');
    } else {
        mejaInput.style.display = 'none';
        mejaField.removeAttribute('required');
        mejaField.value = '';
    }
}

function showPopupThenRedirect() {
    const popup = document.getElementById('popup');
    popup.style.display = 'block';
    setTimeout(() => {
        window.location.href = "<?= $_SERVER['PHP_SELF'] ?>";
    }, 2000);
}

</script>
</body>
</html>
