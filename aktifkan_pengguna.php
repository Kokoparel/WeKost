<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? null;

    if ($user_id) {
        // Update status_pengguna menjadi 'aktif'
        $stmt = $conn->prepare("UPDATE users SET status_pengguna = 'aktif' WHERE id_user = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        header("Location: data_pengguna.php?message=user_aktif");
        exit;
    } else {
        echo "ID pengguna tidak valid.";
    }
} else {
    echo "Metode tidak diizinkan.";
}
?>