<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tipe = $_POST['tipe']; // mitra atau pengguna
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $foto = '';

    // Upload foto profil jika ada
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $upload_dir = 'uploads/profile/';
        
        // Cek direktori, buat jika tidak ada
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $filename = basename($_FILES['foto']['name']);
        $target_file = $upload_dir . time() . '_' . $filename;
        
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
            $foto = $target_file;
        }
    }

    // Set tipe pengguna untuk database
    $tipe_pengguna = ($tipe == 'mitra') ? 'mitra' : 'mahasiswa';
    
    // Set status pengguna berdasarkan tipe
    $status_pengguna = ($tipe == 'mitra') ? 'pending' : 'aktif';

    // Insert data pengguna
    $query = "INSERT INTO users (nama_lengkap, email, no_hp, password, foto_profil, tipe_pengguna, status_pengguna) 
              VALUES ('$nama', '$email', '$no_hp', '$password', '$foto', '$tipe_pengguna', '$status_pengguna')";
    
    if (mysqli_query($conn, $query)) {
        $user_id = mysqli_insert_id($conn);
        
        // Simpan user_id di session
        session_start();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['role'] = $tipe_pengguna;        

        // Redirect berdasarkan tipe pengguna
        if ($tipe == 'mitra') {
            // Mitra perlu verifikasi
            header("Location: verifikasi_mitra.php");
        } else {
            // Mahasiswa langsung ke login
            header("Location: login_form.php?role=pengguna");
        }
        exit;
    } else {
        // Jika gagal mendaftar
        echo "Pendaftaran gagal: " . mysqli_error($conn);
        echo "<br><a href='register_form.php?role=$tipe'>Kembali ke form pendaftaran</a>";
    }
} else {
    // Jika bukan POST, redirect ke halaman utama
    header("Location: index.php");
    exit;
}