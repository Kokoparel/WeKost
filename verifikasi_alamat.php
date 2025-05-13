<?php
session_start();
include 'config.php';

// Cek session
if (!isset($_SESSION['user_id'])) {
    header("Location: login_form.php?role=mitra");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Daftar provinsi di Indonesia
$provinsi = [
    'Aceh', 'Sumatera Utara', 'Sumatera Barat', 'Riau', 'Kepulauan Riau',
    'Jambi', 'Sumatera Selatan', 'Bengkulu', 'Lampung', 'Bangka Belitung',
    'DKI Jakarta', 'Jawa Barat', 'Banten', 'Jawa Tengah', 'DI Yogyakarta',
    'Jawa Timur', 'Bali', 'Nusa Tenggara Barat', 'Nusa Tenggara Timur',
    'Kalimantan Barat', 'Kalimantan Tengah', 'Kalimantan Selatan', 'Kalimantan Timur',
    'Kalimantan Utara', 'Sulawesi Utara', 'Gorontalo', 'Sulawesi Tengah',
    'Sulawesi Barat', 'Sulawesi Selatan', 'Sulawesi Tenggara', 'Maluku',
    'Maluku Utara', 'Papua', 'Papua Barat', 'Papua Selatan', 'Papua Tengah', 'Papua Pegunungan'
];

// Cek apakah user sudah mulai verifikasi
$query = "SELECT * FROM mitra_verifikasi WHERE id_user = $user_id";
$result = mysqli_query($conn, $query);
$verifikasi_exists = mysqli_num_rows($result) > 0;
$verifikasi_data = mysqli_fetch_assoc($result);

// Jika form di-submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $alamat_lengkap = mysqli_real_escape_string($conn, $_POST['alamat_lengkap']);
    $provinsi_pilihan = mysqli_real_escape_string($conn, $_POST['provinsi']);
    $detail_alamat = mysqli_real_escape_string($conn, $_POST['detail_alamat']);

    if (empty($alamat_lengkap) || empty($provinsi_pilihan) || empty($detail_alamat)) {
        $message = '<div class="alert error">Semua field harus diisi!</div>';
    } else {
        // Jika data verifikasi belum ada, buat baru
        if (!$verifikasi_exists) {
            $query = "INSERT INTO mitra_verifikasi (id_user, alamat_lengkap, provinsi, detail_alamat) 
                      VALUES ($user_id, '$alamat_lengkap', '$provinsi_pilihan', '$detail_alamat')";
        } else {
            // Jika sudah ada, update
            $query = "UPDATE mitra_verifikasi 
                      SET alamat_lengkap = '$alamat_lengkap', 
                          provinsi = '$provinsi_pilihan', 
                          detail_alamat = '$detail_alamat' 
                      WHERE id_user = $user_id";
        }

        if (mysqli_query($conn, $query)) {
            $message = '<div class="alert success">Data alamat berhasil disimpan!</div>';
            // Redirect setelah 2 detik
            header("refresh:2;url=verifikasi_mitra.php");
        } else {
            $message = '<div class="alert error">Gagal menyimpan data: ' . mysqli_error($conn) . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Alamat - WeKost</title>
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

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #34495e;
        }

        input[type="text"], 
        select, 
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
        }

        textarea {
            height: 100px;
            resize: vertical;
        }

        select {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
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

        .back-btn {
            background: #95a5a6;
            margin-top: 10px;
        }

        .back-btn:hover {
            background: #7f8c8d;
        }

        .alert {
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .info-box {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .info-box h3 {
            color: #0d47a1;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .info-box p {
            margin-bottom: 5px;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Data Alamat</h2>

        <?php echo $message; ?>

        <div class="info-box">
            <h3>Informasi Alamat:</h3>
            <p>Masukkan alamat tempat tinggal tetap Anda saat ini. Data ini diperlukan untuk verifikasi identitas sebagai mitra WeKost.</p>
            <p>Pastikan data alamat yang Anda masukkan sesuai dengan KTP atau dokumen resmi lainnya.</p>
        </div>

        <form action="" method="post">
            <div class="form-group">
                <label for="alamat_lengkap">Alamat Lengkap</label>
                <textarea id="alamat_lengkap" name="alamat_lengkap" required><?php echo isset($verifikasi_data['alamat_lengkap']) ? $verifikasi_data['alamat_lengkap'] : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label for="provinsi">Provinsi</label>
                <select id="provinsi" name="provinsi" required>
                    <option value="">Pilih Provinsi</option>
                    <?php foreach ($provinsi as $p): ?>
                        <option value="<?php echo $p; ?>" <?php echo (isset($verifikasi_data['provinsi']) && $verifikasi_data['provinsi'] == $p) ? 'selected' : ''; ?>><?php echo $p; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="detail_alamat">Detail Alamat (RT/RW, Kelurahan, Kecamatan)</label>
                <input type="text" id="detail_alamat" name="detail_alamat" value="<?php echo isset($verifikasi_data['detail_alamat']) ? $verifikasi_data['detail_alamat'] : ''; ?>" required>
            </div>

            <button type="submit" class="btn">Simpan Data Alamat</button>
            <a href="verifikasi_mitra.php" class="btn back-btn">Kembali</a>
        </form>
    </div>
</body>
</html>