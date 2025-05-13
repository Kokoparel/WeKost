<?php
include 'config.php';

// Cek apakah ada request POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : '';
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Validasi data
    if (empty($user_id) || empty($action)) {
        die("Data tidak lengkap");
    }
    
    // Sanitasi data
    $user_id = mysqli_real_escape_string($conn, $user_id);
    
    // Tentukan status verifikasi berdasarkan aksi
    $status_verifikasi = ($action === 'approve') ? 'diterima' : 'ditolak';
    
    // Update status_verifikasi di tabel mitra_verifikasi
    $update_query = "UPDATE mitra_verifikasi SET status_verifikasi = '$status_verifikasi' WHERE id_user = '$user_id'";
    $update_result = mysqli_query($conn, $update_query);
    
    // Update status_pengguna di tabel users
    $status_pengguna = ($action === 'approve') ? 'aktif' : 'ditolak';
    $update_user_query = "UPDATE users SET status_pengguna = '$status_pengguna' WHERE id_user = '$user_id'";
    $update_user_result = mysqli_query($conn, $update_user_query);
    
    if ($update_result && $update_user_result) {
        // Redirect kembali ke halaman verifikasi dengan pesan sukses
        header("Location: verifikasi_akun_mitra.php?status=success&action=$action");
        exit;
    } else {
        // Redirect kembali ke halaman verifikasi dengan pesan error
        header("Location: verifikasi_akun_mitra.php?status=error&message=" . urlencode(mysqli_error($conn)));
        exit;
    }
} else {
    // Jika bukan POST request, redirect ke halaman verifikasi
    header("Location: verifikasi_akun_mitra.php");
    exit;
}
?>