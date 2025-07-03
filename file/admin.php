<?php
session_start();
if (!isset($_SESSION['nama_pengguna']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../error/403.php");
    exit;
}

include 'koneksi.php';
include 'log_aktivitas.php';

function bersihkan($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

$hal = $_GET['hal'] ?? '';

// Tambah atau Edit Menu
if (isset($_POST['addmenu'])) {
    $id_menu = bersihkan($_POST['id_menu']);
    $nama_menu = bersihkan($_POST['nama_menu']);
    $harga = bersihkan($_POST['harga']);
    $kategori = bersihkan($_POST['kategori']);
    $gambar = bersihkan($_POST['gambar']);

    if ($hal == 'edit') {
        $ubah = mysqli_query($koneksi, "UPDATE menu SET nama_menu = '$nama_menu', harga = '$harga', kategori = '$kategori', gambar = '$gambar' WHERE id_menu = '$id_menu'") or die(mysqli_error($koneksi));
        if ($ubah) {
            catatAktivitas($koneksi, "Mengedit menu: $nama_menu");
            echo "<script>alert('Ubah Data Sukses!'); window.location.href='admin.php#menu';</script>";
        }
    } else {
        $simpan = mysqli_query($koneksi, "INSERT INTO menu (nama_menu, harga, kategori, gambar) VALUES ('$nama_menu', '$harga', '$kategori', '$gambar')") or die(mysqli_error($koneksi));
        if ($simpan) {
            catatAktivitas($koneksi, "Menambahkan menu baru: $nama_menu");
            echo "<script>alert('Simpan Data Sukses!'); window.location.href='admin.php#menu';</script>";
        }
    }
}

// Tambah atau Edit Pengguna
if (isset($_POST['adduser'])) {
    $id_user = bersihkan($_POST['id_user']);
    $nama_pengguna = bersihkan($_POST['nama_pengguna']);
    $email = bersihkan($_POST['email']);
    $role = bersihkan($_POST['role']);
    $pass = bersihkan($_POST['pass']);
    $status = bersihkan($_POST['status']);

    if ($hal == 'edit') {
        if (!empty($pass)) {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $query = "UPDATE users SET nama_pengguna = '$nama_pengguna', email = '$email', role = '$role', pass = '$hash', status = '$status' WHERE id_user = '$id_user'";
        } else {
            $query = "UPDATE users SET nama_pengguna = '$nama_pengguna', email = '$email', role = '$role', status = '$status' WHERE id_user = '$id_user'";
        }
        $ubah = mysqli_query($koneksi, $query) or die(mysqli_error($koneksi));
        if ($ubah) {
            catatAktivitas($koneksi, "Mengedit pengguna: $nama_pengguna ($email)");
            echo "<script>alert('Ubah Data Sukses!'); window.location.href='admin.php#pengguna';</script>";
        }
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $simpan = mysqli_query($koneksi, "INSERT INTO users (nama_pengguna, email, role, pass, status) VALUES ('$nama_pengguna', '$email', '$role', '$hash', '$status')") or die(mysqli_error($koneksi));
        if ($simpan) {
            catatAktivitas($koneksi, "Menambahkan pengguna baru: $nama_pengguna ($email)");
            echo "<script>alert('Simpan Data Sukses!'); window.location.href='admin.php#pengguna';</script>";
        }
    }
}

$vid_menu = $vid_user = $vnama_menu = $vharga = $vkategori = $vgambar = "";
$vnama_pengguna = $vemail = $vrole = $vpass = $vstatus = "";

if ($hal == 'edit' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $menu = mysqli_query($koneksi, "SELECT * FROM menu WHERE id_menu = '$id'");
    if ($data_menu = mysqli_fetch_array($menu)) {
        $vid_menu = $data_menu['id_menu'];
        $vnama_menu = $data_menu['nama_menu'];
        $vharga = $data_menu['harga'];
        $vkategori = $data_menu['kategori'];
        $vgambar = $data_menu['gambar'];
    } else {
        $user = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id'");
        if ($data_user = mysqli_fetch_array($user)) {
            $vid_user = $data_user['id_user'];
            $vnama_pengguna = $data_user['nama_pengguna'];
            $vemail = $data_user['email'];
            $vrole = $data_user['role'];
            $vpass = '';
            $vstatus = $data_user['status'];
        }
    }
} elseif ($hal == 'hapus' && isset($_GET['id'])) {
    try {
        $id = mysqli_real_escape_string($koneksi, $_GET['id']);
        $query = "DELETE FROM menu WHERE id_menu = '$id'";
        
        if (!mysqli_query($koneksi, $query)) {
            throw new Exception("Menu masih digunakan di transaksi lain.");
        }

        catatAktivitas($koneksi, "Menghapus menu ID: $id");
        echo "<script>alert('Hapus Data Sukses!'); window.location.href='admin.php#menu';</script>";
    } catch (Exception $e) {
        // Simpan pesan error ke session (opsional)
        $_SESSION['error_msg'] = $e->getMessage();
        header("Location: ../error/500.php");
        exit;
    }
} elseif ($hal == 'hapususer' && isset($_GET['id'])) {
    mysqli_query($koneksi, "DELETE FROM users WHERE id_user = '{$_GET['id']}'") or die(mysqli_error($koneksi));
    catatAktivitas($koneksi, "Menghapus pengguna ID: {$_GET['id']}");
    echo "<script>alert('Hapus Pengguna Sukses!'); window.location.href='admin.php#pengguna';</script>";
} elseif ($hal == 'arsipkan' && isset($_GET['id'])) {
    $id = bersihkan($_GET['id']);
    $arsipkan = mysqli_query($koneksi, "UPDATE menu SET status='arsip' WHERE id_menu='$id'");
    if ($arsipkan) {
        catatAktivitas($koneksi, "Mengarsipkan menu ID: $id");
        echo "<script>alert('Menu berhasil diarsipkan!'); window.location.href='admin.php#menu';</script>";
    }
// Aktifkan Kembali Menu
} elseif ($hal == 'aktifkan' && isset($_GET['id'])) {
    $id = bersihkan($_GET['id']);
    $aktifkan = mysqli_query($koneksi, "UPDATE menu SET status='aktif' WHERE id_menu='$id'");
    if ($aktifkan) {
        catatAktivitas($koneksi, "Mengaktifkan kembali menu ID: $id");
        echo "<script>alert('Menu berhasil diaktifkan kembali!'); window.location.href='admin.php#menu';</script>";
    }
}

date_default_timezone_set('Asia/Singapore');
$tanggal_hari_ini = date('Y-m-d');
$jam = date('H');
$salam = ($jam >= 5 && $jam < 12) ? "Selamat pagi" : (($jam >= 12 && $jam < 15) ? "Selamat siang" : (($jam >= 15 && $jam < 18) ? "Selamat sore" : "Selamat malam"));

// Statistik dashboard
$pendapatan_today = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT IFNULL(SUM(total),0) as total FROM transaksi WHERE status='selesai' AND DATE(waktu_bayar) = '$tanggal_hari_ini'"))['total'];
$jumlah_pesanan_today = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM transaksi WHERE DATE(waktu_pesan) = '$tanggal_hari_ini'"))['total'];
$jumlah_kasir = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role='kasir' AND status='aktif'"))['total'];
$jumlah_menu = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM menu"))['total'];

$pesanan_terbaru = mysqli_query($koneksi, "SELECT * FROM transaksi ORDER BY waktu_pesan DESC LIMIT 5");
$semua_transaksi = mysqli_query($koneksi, "SELECT t.*, u.nama_pengguna FROM transaksi t LEFT JOIN users u ON t.dibuat_oleh = u.id_user ORDER BY waktu_pesan DESC");

// Ambil transaksi dan kelompokkan per minggu
$grouped_transaksi = [];
$transaksi_result = mysqli_query($koneksi, "SELECT t.*, u.nama_pengguna FROM transaksi t LEFT JOIN users u ON t.dibuat_oleh = u.id_user ORDER BY waktu_pesan DESC");

while ($row = mysqli_fetch_assoc($transaksi_result)) {
  $tanggal = new DateTime($row['waktu_pesan']);
  $minggu_ke = $tanggal->format("W-Y"); // Minggu ke-n tahun

  if (!isset($grouped_transaksi[$minggu_ke])) {
    $start = clone $tanggal;
    $start->modify('Monday this week');
    $end = clone $tanggal;
    $end->modify('Sunday this week');
    $grouped_transaksi[$minggu_ke] = [
      'periode' => $start->format('d-m-Y') . ' - ' . $end->format('d-m-Y'),
      'data' => []
    ];
  }
  $grouped_transaksi[$minggu_ke]['data'][] = $row;
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title> Admin </title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <script src="https://unpkg.com/feather-icons"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <div class="sidebar">
    <h4 class="text-center mb-4">☕ Logo</h4>
    <a href="#dashboard" class="active" onclick="showSection('dashboard')"><i data-feather="home"></i> <span>Dashboard</span></a>
    <a href="#menu" onclick="showSection('menu')"><i data-feather="coffee"></i> <span>Menu</span></a>
    <a href="#pengguna" onclick="showSection('pengguna')"><i data-feather="users"></i> <span>Pengguna</span></a>
    <a href="#laporan" onclick="showSection('laporan')"><i data-feather="file-text"></i> <span>Laporan</span></a>
    <a href="logout.php"><i data-feather="log-out"></i><span>Logout</span></a>
  </div>

  <div class="main-content">
    <div id="dashboard" class="content-section active">
      <h2 class="my-4"><?php echo "$salam, ".ucwords($_SESSION['nama_pengguna'])." 👋"; ?></h2>
      <div class="row g-3 mb-4">
        <div class="col-md-3">
          <div class="metric-box">
            <div>
              <small>Total Pendapatan Hari Ini</small>
              <h4>Rp <?= number_format($pendapatan_today, 0, ',', '.') ?></h4>
            </div>
            <i data-feather="wallet" class="text-secondary"></i>
          </div>
        </div>
        <div class="col-md-3">
          <div class="metric-box">
            <div>
              <small>Total Pesanan Hari Ini</small>
              <h4><?= $jumlah_pesanan_today ?></h4>
            </div>
            <i data-feather="shopping-bag" class="text-success"></i>
          </div>
        </div>
        <div class="col-md-3">
          <div class="metric-box">
            <div>
              <small>Kasir Aktif</small>
              <h4><?= $jumlah_kasir ?></h4>
            </div>
            <i data-feather="user-check" class="text-warning"></i>
          </div>
        </div>
        <div class="col-md-3">
          <div class="metric-box">
            <div>
              <small>Jumlah Menu</small>
              <h4><?= $jumlah_menu ?></h4>
            </div>
            <i data-feather="list" class="text-danger"></i>
          </div>
        </div>
      </div>

      <div class="card-custom">
        <div class="d-flex justify-content-between mb-3">
          <h5>Pesanan Terbaru</h5>
        </div>
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Pelanggan</th>
              <th>Tanggal</th>
              <th>Total</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = mysqli_fetch_assoc($pesanan_terbaru)): ?>
            <tr>
              <td>#CAF-<?= $row['id_transaksi'] ?></td>
              <td><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
              <td><?= date('d-m-Y H:i', strtotime($row['waktu_pesan'])) ?></td>
              <td>Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
              <td><span class="badge bg-<?= $row['status'] == 'selesai' ? 'success' : ($row['status'] == 'diproses' ? 'warning text-dark' : 'danger') ?>"><?= ucfirst($row['status']) ?></span></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- menu -->
    <div id="menu" class="content-section">
      <h2 class="my-4">Manajemen Menu</h2>
      <div class="card-custom mb-4">
        <form method="post" class="row g-3" autocomplete="off">
          <div class="col-md-4">
            <input type="hidden" name="id_menu" value="<?= $vid_menu ?>">

            <label class="form-label">Nama Menu</label>
            <input type="text" class="form-control" name="nama_menu" value="<?=$vnama_menu ?>" placeholder="Contoh: Latte" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Harga</label>
            <input type="number" class="form-control" name="harga" value="<?=$vharga ?>" placeholder="Contoh: 20000" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Kategori</label>
            <select class="form-select" name="kategori" required>
              <option value="">--- Pilih Kategori ---</option>
              <option value="minuman" <?= $vkategori == 'minuman' ? 'selected' : '' ?>>Minuman</option>
              <option value="makanan" <?= $vkategori == 'makanan' ? 'selected' : '' ?>>Makanan</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Gambar</label>
            <input type="text" class="form-control" name="gambar" value="<?=$vgambar ?>" placeholder="https://example.com/gambar.jpg" required>
          </div>
          <div class="col-12">
            <button type="submit" name="addmenu" class="btn btn-primary">Simpan</button>
            <button type="button" class="btn btn-danger" onclick="resetForm(this)">Kosongkan</button>
          </div>
        </form>
      </div>

      <div class="card-custom">
        <h5>Daftar Menu</h5>
        <table class="table table-bordered mt-3">
            <thead class="table-light">
                <tr style="text-align: center;">
                    <th>Gambar</th>
                    <th>Nama Menu</th>
                    <th>Harga</th>
                    <th>Kategori</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query_menu = mysqli_query($koneksi, "SELECT* FROM menu ORDER BY id_menu Desc");
                while ($data_menu = mysqli_fetch_array($query_menu)) {
                ?>
                <tr>
                    <td><img src="<?= $data_menu['gambar'] ?>" alt="<?= $data_menu['nama_menu'] ?>" width="60" height="60" style="object-fit:cover;"></td>
                    <td><?= $data_menu['nama_menu'] ?></td>
                    <td><?= $data_menu['harga'] ?></td>
                    <td><?= $data_menu['kategori'] ?></td>
                    <td><?= $data_menu['status'] ?></td>

                    <td>
                        <center>
                            <a href="?hal=edit&id=<?= $data_menu['id_menu']?>#menu" class="btn btn-sm btn-outline-primary"><i data-feather="edit"></i></a>

                            <?php if ($data_menu['status'] == 'aktif'): ?>
                              <a href="?hal=arsipkan&id=<?= $data_menu['id_menu']?>#menu" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Arsipkan menu ini?')"><i data-feather="archive"></i></a>
                            <?php else: ?>
                              <a href="?hal=aktifkan&id=<?= $data_menu['id_menu']?>#menu" class="btn btn-sm btn-outline-success" onclick="return confirm('Aktifkan kembali menu ini?')"><i data-feather="refresh-ccw"></i></a>
                            <?php endif; ?>

                            <a href="?hal=hapus&id=<?= $data_menu['id_menu']?>#menu" onclick="return confirm('Yakin Akan Menghapus Menu Ini??')" class="btn btn-sm btn-outline-danger"><i data-feather="trash-2"></i></a>
                        </center>
                    </td>
                </tr>
                <?php }
                ?>
            </tbody>
        </table>
      </div>
    </div>

    <!-- pengguna -->
    <div id="pengguna" class="content-section">
      <h2 class="my-4">Manajemen Pengguna</h2>

      <div class="card-custom mb-4">
        <form method="post" class="row g-3" autocomplete="off">
          <div class="col-md-4">
            <input type="hidden" name="id_user" value="<?= $vid_user ?>">

            <label class="form-label">Nama Lengkap</label>
            <input type="text" class="form-control" name="nama_pengguna" value="<?=$vnama_pengguna ?>" placeholder="Nama Karyawan" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" value="<?=$vemail ?>" placeholder="email@contoh.com" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Role</label>
            <select class="form-select" name="role" required>
              <option value="">--- Pilih Role ---</option>
              <option value="admin" <?= $vrole == 'admin' ? 'selected' : '' ?>>Admin</option>
              <option value="kasir" <?= $vrole == 'kasir' ? 'selected' : '' ?>>Kasir</option>
            </select>
          </div>
          <div class="col-md-4">
            <label for="" class="form-label">Password</label>
            <input type="text" class="form-control" name="pass" value="<?=$vpass ?>" placeholder="Password">
          </div>
          <div class="col-md-4">
            <label class="form-label">Status</label>
            <select class="form-select" name="status" required>
              <option value="">--- Pilih Status ---</option>
              <option value="aktif" <?= $vstatus == 'aktif' ? 'selected' : '' ?>>Aktif</option>
              <option value="nonaktif" <?= $vstatus == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
              <option value="blokir" <?= $vstatus == 'blokir' ? 'selected' : '' ?>>Diblokir</option>
            </select>
          </div>
          <div class="col-12">
            <button type="submit" name="adduser" class="btn btn-primary">Simpan</button>
            <button type="button" class="btn btn-danger" onclick="resetForm(this)">Kosongkan</button>
          </div>
        </form>
      </div>

      <div class="card-custom">
        <h5>Daftar Pengguna</h5>
        <table class="table table-bordered mt-3">
          <thead class="table-light">
            <tr style="text-align: center;">
              <th>Nama</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $query_user = mysqli_query($koneksi, "SELECT* FROM users ORDER BY id_user Desc");
            while ($data_user = mysqli_fetch_array($query_user)) {
            ?>
            <tr>
                <td><?= $data_user['nama_pengguna'] ?></td>
                <td><?= $data_user['email'] ?></td>
                <td><?= $data_user['role'] ?></td>
                <td><?= $data_user['status'] ?></td>

                <td>
                    <center>
                        <a href="?hal=edit&id=<?= $data_user['id_user']?>#pengguna" class="btn btn-sm btn-outline-primary"><i data-feather="edit"></i></a>
                        <a href="?hal=hapususer&id=<?= $data_user['id_user']?>#pengguna" onclick="return confirm('Yakin Akan Menghapus Pengguna Ini??')" class="btn btn-sm btn-outline-danger"><i data-feather="trash-2"></i></a>
                    </center>
                </td>
            </tr>
            <?php }
            ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- laporan -->
    <div id="laporan" class="content-section">
  <h2 class="my-4">Laporan Transaksi</h2>
  <div class="card-custom mb-4">
    <h5>10 Transaksi Terbaru</h5>
    <table class="table table-striped mt-3">
      <thead class="table-dark">
        <tr>
          <th>No</th>
          <th>Tanggal</th>
          <th>Pelanggan</th>
          <th>Jenis</th>
          <th>Total</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $terbaru_result = mysqli_query($koneksi, "SELECT * FROM transaksi ORDER BY waktu_pesan DESC LIMIT 10");
        $no = 1;
        while ($trx = mysqli_fetch_assoc($terbaru_result)) {
        ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= date('d-m-Y H:i', strtotime($trx['waktu_pesan'])) ?></td>
          <td><?= htmlspecialchars($trx['nama_pelanggan']) ?></td>
          <td><?= ucfirst($trx['jenis']) ?></td>
          <td>Rp <?= number_format($trx['total'], 0, ',', '.') ?></td>
          <td><span class="badge bg-<?= $trx['status'] == 'selesai' ? 'success' : ($trx['status'] == 'diproses' ? 'warning text-dark' : 'danger') ?>"><?= ucfirst($trx['status']) ?></span></td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>

  <?php foreach ($grouped_transaksi as $key => $group): ?>
  <div class="card-custom mb-3">
    <div class="d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Minggu: <?= $group['periode'] ?></h5>
      <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $key ?>">
        <i data-feather="eye"></i> Lihat Detail
      </button>
    </div>
  </div>

  <!-- Modal Detail Minggu -->
  <div class="modal fade" id="modalDetail<?= $key ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Detail Transaksi: <?= $group['periode'] ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Pelanggan</th>
                <th>Jenis</th>
                <th>Total</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php $i = 1; foreach ($group['data'] as $t): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><?= date('d-m-Y H:i', strtotime($t['waktu_pesan'])) ?></td>
                <td><?= htmlspecialchars($t['nama_pelanggan']) ?></td>
                <td><?= ucfirst($t['jenis']) ?></td>
                <td>Rp <?= number_format($t['total'], 0, ',', '.') ?></td>
                <td><span class="badge bg-<?= $t['status'] == 'selesai' ? 'success' : ($t['status'] == 'diproses' ? 'warning text-dark' : 'danger') ?>"><?= ucfirst($t['status']) ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          <button type="button" class="btn btn-success" onclick="printDiv('modalDetail<?= $key ?>')">Print</button>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
    <!-- Section lain bisa dilanjutkan di sini -->
  </div>

<script src="../js/bootstrap.min.js"></script>
<script>
    feather.replace();
    function showSection(id) {
      // Tampilkan hanya section yg sesuai
        document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
        document.getElementById(id).classList.add('active');
        document.querySelectorAll('.sidebar a').forEach(a => {
            a.classList.remove('active');
            if (a.getAttribute('href').includes('#' + id)) {
                a.classList.add('active');
            }
        });
    }

    // Deteksi hash dari URL saat reload dan aktifkan menu + section yang sesuai
    window.addEventListener('load', function () {
        const hash = window.location.hash.substring(1);
        if (hash) {
            showSection(hash);
            document.querySelectorAll('.sidebar a').forEach(a => {
                a.classList.remove('active');
                if (a.getAttribute('href').includes('#' + hash)) {
                    a.classList.add('active');
                }
            });
        }
    });

    function printDiv(modalId) {
      const modal = document.getElementById(modalId);
      const printContents = modal.querySelector('.modal-body').innerHTML;
      const originalContents = document.body.innerHTML;
      document.body.innerHTML = printContents;
      window.print();
      document.body.innerHTML = originalContents;
      location.reload();
    }

    function resetForm(button) {
      const form = button.closest('form');
      if (!form) return;

      form.querySelectorAll('input, select, textarea').forEach(el => {
        if (el.type === 'hidden') return;

        if (el.tagName === 'SELECT') {
          el.selectedIndex = 0; // pilih option pertama (yaitu kosong)
        } else if (el.type === 'checkbox' || el.type === 'radio') {
          el.checked = false;
        } else {
          el.value = '';
        }
      });

      // Optional: Hapus mode edit di URL
      const url = new URL(window.location.href);
      url.searchParams.delete('hal');
      url.searchParams.delete('id');
      window.history.replaceState({}, '', url.toString());
    }

</script>
</body>
</html>
