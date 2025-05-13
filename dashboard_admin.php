<?php
session_start();
include 'config.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login_form.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Dashboard Admin - WeKost</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        nav { margin-bottom: 20px; }
        nav a {
            margin-right: 15px;
            text-decoration: none;
            color: #3498db;
            font-weight: bold;
        }
        nav a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Dashboard Admin</h1>
    <p>Selamat datang di dashboard admin WeKost!</p>
    <nav>
        <a href="data_pengguna.php">Daftar Pengguna</a>
        <a href="verifikasi_akun_mitra.php">Verifikasi Akun Mitra</a>
        <a href="daftar_kost.php">Daftar Kost</a>
        <a href="verifikasi_kost.php">Verifikasi Kost</a>
        <a href="logout.php">Logout</a>
    </nav>
</body>
</html>