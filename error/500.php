<?php
session_start();
$msg = $_SESSION['error_msg'] ?? 'Terjadi kesalahan tak terduga pada server.';
unset($_SESSION['error_msg']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>500 - Kesalahan Server</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8d7da; }
        .error-container {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="error-container text-center">
        <h1 class="display-1 text-danger">500</h1>
        <h4 class="mb-3">Kesalahan Server</h4>
        <p><?= htmlspecialchars($msg) ?></p>
        <a href="../file/admin.php" class="btn btn-danger mt-3">Kembali ke Admin</a>
    </div>
</body>
</html>
