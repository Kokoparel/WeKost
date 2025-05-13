<?php
include 'config.php';

// Data admin baru
$email = 'admin@wekost.com';
$password_plain = 'Admin12345!';

// Hash password
$password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

// Query insert admin
$query = "INSERT INTO admins (email, password) VALUES (?, ?)";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ss", $email, $password_hashed);

if (mysqli_stmt_execute($stmt)) {
    echo "Admin berhasil dibuat dengan email: $email dan password: $password_plain";
} else {
    echo "Gagal membuat admin: " . mysqli_error($conn);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
