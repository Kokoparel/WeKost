<?php
include 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    $query_admin = "SELECT * FROM admins WHERE email = '$email'";
    $result_admin = mysqli_query($conn, $query_admin);

    if ($result_admin && mysqli_num_rows($result_admin) > 0) {
        $admin = mysqli_fetch_assoc($result_admin);
        if (password_verify($password, $admin['password'])) {
            // Login sebagai admin berhasil
            $_SESSION['admin_id'] = $admin['id_admin'];
            $_SESSION['admin_email'] = $admin['email'];
            header("Location: dashboard_admin.php");
            exit;
        } else {
            // Password admin salah
            header("Location: login_form.php?role=admin&error=password_salah");
            exit;
        }
    }

    // Jika bukan admin, cek di tabel users sesuai role
    $tabel = 'users'; // semua user ada di tabel users
    
    // Pemetaan role dari form ke tipe_pengguna di database
    $db_role = $role;
    if ($role == 'pengguna') {
        // Jika role adalah 'pengguna', cari tanpa filter tipe_pengguna
        $query_user = "SELECT * FROM $tabel WHERE email = '$email'";
    } else {
        // Jika role adalah mahasiswa atau mitra, cari dengan filter tipe_pengguna
        $query_user = "SELECT * FROM $tabel WHERE email = '$email' AND tipe_pengguna = '$role'";
    }
    $result_user = mysqli_query($conn, $query_user);

    if ($result_user && mysqli_num_rows($result_user) > 0) {
        $user = mysqli_fetch_assoc($result_user);
        if (password_verify($password, $user['password'])) {
            // Cek status pengguna
            if ($user['status_pengguna'] == 'nonaktif') {
                header("Location: login_form.php?role=$role&error=akun_nonaktif");
                exit;
            }
            
            // Cek jika user adalah mitra dan status masih pending
            if ($user['tipe_pengguna'] == 'mitra' && $user['status_pengguna'] == 'pending') {
                header("Location: login_form.php?role=$role&error=akun_pending");
                exit;
            }
            
            // Login sebagai user berhasil
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['tipe_pengguna'];

            // Redirect berdasarkan tipe pengguna
            $tipe = $user['tipe_pengguna'];
            if ($tipe == 'mahasiswa') {
                header("Location: dashboard_mhs.php");
                exit;
            } elseif ($tipe == 'mitra') {
                header("Location: dashboard_mitra.php");
                exit;
            } elseif ($tipe == 'pengguna') {
                header("Location: dashboard_pengguna.php");
                exit;
            } else {
                // Jika tipe pengguna tidak dikenali, arahkan ke halaman login dengan error
                header("Location: login_form.php?role=$role&error=tipe_user_tidak_valid");
                exit;
            }
        } else {
            // Password user salah
            header("Location: login_form.php?role=$role&error=password_salah");
            exit;
        }
    } else {
        // Email tidak ditemukan
        header("Location: login_form.php?role=$role&error=email_tidak_ditemukan");
        exit;
    }
} else {
    // Jika bukan method POST, redirect ke halaman login
    header("Location: login_form.php");
    exit;
}
?>