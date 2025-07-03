<?php
function catatAktivitas($koneksi, $aksi, $pengguna) {
    $stmt = $koneksi->prepare("INSERT INTO log_aktivitas (waktu, aksi, pengguna) VALUES (NOW(), ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ss", $aksi, $pengguna);
        $stmt->execute();
        $stmt->close();
    } else {
        // Opsional: log ke file jika gagal
        error_log("Gagal mencatat log aktivitas: " . $koneksi->error);
    }
}
?>
