<?php
session_start();
include 'config.php';

// Cek session
if (!isset($_SESSION['user_id'])) {
    header("Location: login_form.php?role=mitra");
    exit;
}

// Cek apakah user sudah mulai verifikasi
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM mitra_verifikasi WHERE id_user = $user_id";
$result = mysqli_query($conn, $query);
$verifikasi_data = mysqli_fetch_assoc($result);

// Status upload untuk tiap dokumen
$ktp_status = !empty($verifikasi_data['foto_ktp']) ? 'Terunggah' : 'Klik Untuk Mengunggah';
$foto_status = !empty($verifikasi_data['foto_diri']) ? 'Terunggah' : 'Klik Untuk Mengunggah';
$alamat_status = !empty($verifikasi_data['alamat_lengkap']) && !empty($verifikasi_data['provinsi']) && !empty($verifikasi_data['detail_alamat']) ? 'Terunggah' : 'Klik Untuk Mengunggah';
$pekerjaan_status = !empty($verifikasi_data['pekerjaan']) && !empty($verifikasi_data['instansi_pekerjaan']) && !empty($verifikasi_data['jabatan']) && !empty($verifikasi_data['penghasilan']) ? 'Terunggah' : 'Klik Untuk Mengunggah';

// Cek apakah sudah lengkap semua
$is_complete = ($ktp_status == 'Terunggah' && $foto_status == 'Terunggah' && $alamat_status == 'Terunggah' && $pekerjaan_status == 'Terunggah');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Mitra - WeKost</title>
    <style>
        * {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f0f4f8;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 123, 255, 0.15);
            max-width: 500px;
            margin: 0 auto;
            padding: 25px;
        }

        h2 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 20px;
        }

        .upload-item {
            background: #fff;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            border: 1px solid #e0e0e0;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-item:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .icon {
            width: 50px;
            height: 50px;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icon img {
            max-width: 100%;
            max-height: 100%;
        }

        .text {
            flex-grow: 1;
        }

        .text h3 {
            font-size: 18px;
            color: #3498db;
            margin-bottom: 5px;
        }

        .text p {
            font-size: 14px;
            color: #7f8c8d;
        }

        .btn {
            display: block;
            background: #3498db;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-size: 16px;
            margin: 20px auto 0;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            width: 100%;
        }

        .btn:hover {
            background: #2980b9;
        }

        .completed {
            background-color: #e8f5e9;
            border-color: #81c784;
        }

        .completed h3 {
            color: #2e7d32;
        }

        .status-text {
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 4px;
            background-color: #f1f1f1;
            color: #666;
        }

        .status-uploaded {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Verifikasi Data</h2>
        
        <a href="verifikasi_ktp.php" class="upload-item <?php echo $ktp_status == 'Terunggah' ? 'completed' : ''; ?>">
            <div class="icon">
                <img src="assets/ktp.png" alt="KTP Icon">
            </div>
            <div class="text">
                <h3>KTP-el</h3>
                <p><span class="status-text <?php echo $ktp_status == 'Terunggah' ? 'status-uploaded' : ''; ?>"><?php echo $ktp_status; ?></span></p>
            </div>
        </a>
        
        <a href="verifikasi_foto.php" class="upload-item <?php echo $foto_status == 'Terunggah' ? 'completed' : ''; ?>">
            <div class="icon">
                <img src="assets/foto-diri.png" alt="Foto Icon">
            </div>
            <div class="text">
                <h3>Foto Diri</h3>
                <p><span class="status-text <?php echo $foto_status == 'Terunggah' ? 'status-uploaded' : ''; ?>"><?php echo $foto_status; ?></span></p>
            </div>
        </a>
        
        <a href="verifikasi_alamat.php" class="upload-item <?php echo $alamat_status == 'Terunggah' ? 'completed' : ''; ?>">
            <div class="icon">
                <img src="assets/pngwing.com.png" alt="Alamat Icon">
            </div>
            <div class="text">
                <h3>Alamat</h3>
                <p><span class="status-text <?php echo $alamat_status == 'Terunggah' ? 'status-uploaded' : ''; ?>"><?php echo $alamat_status; ?></span></p>
            </div>
        </a>
        
        <a href="verifikasi_pekerjaan.php" class="upload-item <?php echo $pekerjaan_status == 'Terunggah' ? 'completed' : ''; ?>">
            <div class="icon">
                <img src="assets/job-search_2801829.png" alt="Pekerjaan Icon">
            </div>
            <div class="text">
                <h3>Pekerjaan</h3>
                <p><span class="status-text <?php echo $pekerjaan_status == 'Terunggah' ? 'status-uploaded' : ''; ?>"><?php echo $pekerjaan_status; ?></span></p>
            </div>
        </a>
        
        <?php if ($is_complete): ?>
            <a href="menunggu_verifikasi.php" class="btn">Selesai</a>
        <?php else: ?>
            <button class="btn" disabled style="background-color: #cccccc; cursor: not-allowed;">Lanjut</button>
            <p style="text-align: center; margin-top: 10px; font-size: 12px; color: #e74c3c;">Lengkapi semua persyaratan untuk melanjutkan</p>
        <?php endif; ?>
    </div>
</body>
</html>