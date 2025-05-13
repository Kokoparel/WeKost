<?php
session_start();
include 'config.php';

// Cek session user mitra
if (!isset($_SESSION['user_id'])) {
    header("Location: login_form.php?role=mitra");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data verifikasi mitra
$query = "SELECT status_verifikasi FROM mitra_verifikasi WHERE id_user = $user_id";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    // Jika belum mengisi data verifikasi, arahkan ke halaman verifikasi_mitra.php
    header("Location: verifikasi_mitra.php");
    exit;
}

$data = mysqli_fetch_assoc($result);
$status = $data['status_verifikasi'];

// Jika status sudah diterima, arahkan ke dashboard mitra
if ($status === 'diterima') {
    header("Location: dashboard_mitra.php");
    exit;
}

// Jika status ditolak, arahkan ke halaman info penolakan atau verifikasi ulang
if ($status === 'ditolak') {
    header("Location: verifikasi_mitra.php?status=ditolak");
    exit;
}

// Jika status pending, tampilkan halaman menunggu verifikasi
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Menunggu Verifikasi - WeKost</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f4f8;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 123, 255, 0.15);
            max-width: 450px;
            text-align: center;
        }
        h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        p {
            font-size: 16px;
            color: #34495e;
            margin-bottom: 30px;
        }
        .btn {
            background: #3498db;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Verifikasi Akun Anda Sedang Diproses</h2>
        <p>Terima kasih telah mengisi data verifikasi. Akun Anda sedang menunggu persetujuan dari admin. Mohon bersabar dan coba login secara berkala.</p>
        <a href="index.php" class="btn">Keluar</a>
    </div>
</body>
</html>
