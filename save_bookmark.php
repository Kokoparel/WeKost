<?php
// Start session and include configuration
session_start();
include 'config.php';

// Set header untuk response JSON
header('Content-Type: application/json');

// Cek apakah user sudah login sebagai mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'mahasiswa') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Cek apakah ada POST data
if (!isset($_POST['id_kost']) || !isset($_POST['action'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

// Ambil data dari POST
$id_kost = mysqli_real_escape_string($conn, $_POST['id_kost']);
$action = mysqli_real_escape_string($conn, $_POST['action']);
$id_mahasiswa = $_SESSION['user_id'];

// Lakukan action berdasarkan request
if ($action === 'add') {
    // Cek apakah bookmark sudah ada
    $check_query = "SELECT * FROM bookmark WHERE id_mahasiswa = '$id_mahasiswa' AND id_kost = '$id_kost'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Bookmark sudah ada
        echo json_encode([
            'success' => true,
            'message' => 'Kost sudah tersimpan sebelumnya'
        ]);
        exit;
    }
    
    // Tambah bookmark baru
    $query = "INSERT INTO bookmark (id_mahasiswa, id_kost, tanggal_simpan) VALUES ('$id_mahasiswa', '$id_kost', NOW())";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Kost berhasil disimpan'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menyimpan kost'
        ]);
    }
} elseif ($action === 'remove') {
    // Hapus bookmark
    $query = "DELETE FROM bookmark WHERE id_mahasiswa = '$id_mahasiswa' AND id_kost = '$id_kost'";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Kost berhasil dihapus dari tersimpan'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menghapus kost dari tersimpan'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action'
    ]);
}