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

// Cek apakah user sudah mulai verifikasi
$query = "SELECT * FROM mitra_verifikasi WHERE id_user = $user_id";
$result = mysqli_query($conn, $query);
$verifikasi_exists = mysqli_num_rows($result) > 0;
$verifikasi_data = mysqli_fetch_assoc($result);

// Jika form di-submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pekerjaan = mysqli_real_escape_string($conn, $_POST['pekerjaan']);
    $instansi = mysqli_real_escape_string($conn, $_POST['instansi']);
    $jabatan = mysqli_real_escape_string($conn, $_POST['jabatan']);
    $penghasilan = filter_var($_POST['penghasilan'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    if (empty($pekerjaan) || empty($instansi) || empty($jabatan) || empty($penghasilan)) {
        $message = '<div class="alert error">Semua field harus diisi!</div>';
    } else {
        // Jika data verifikasi belum ada, buat baru
        if (!$verifikasi_exists) {
            $query = "INSERT INTO mitra_verifikasi (id_user, pekerjaan, instansi_pekerjaan, jabatan, penghasilan) 
                      VALUES ($user_id, '$pekerjaan', '$instansi', '$jabatan', $penghasilan)";
        } else {
            // Jika sudah ada, update
            $query = "UPDATE mitra_verifikasi 
                      SET pekerjaan = '$pekerjaan', 
                          instansi_pekerjaan = '$instansi', 
                          jabatan = '$jabatan',
                          penghasilan = $penghasilan
                      WHERE id_user = $user_id";
        }

        if (mysqli_query($conn, $query)) {
            $message = '<div class="alert success">Data pekerjaan berhasil disimpan!</div>';
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
    <title>Data Pekerjaan - WeKost</title>
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
        input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
        }

        .currency-input {
            position: relative;
        }

        .currency-input span {
            position: absolute;
            left: 10px;
            top: 12px;
            color: #7f8c8d;
            font-weight: bold;
        }

        .currency-input input {
            padding-left: 30px;
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
        <h2>Data Pekerjaan</h2>

        <?php echo $message; ?>

        <div class="info-box">
            <h3>Informasi Pekerjaan:</h3>
            <p>Masukkan detail pekerjaan Anda saat ini. Data ini diperlukan untuk verifikasi identitas dan kemampuan finansial sebagai mitra WeKost.</p>
            <p>Informasi pekerjaan Anda akan membantu kami memahami profil Anda sebagai mitra kost.</p>
        </div>

        <form action="" method="post">
            <div class="form-group">
                <label for="pekerjaan">Pekerjaan</label>
                <input type="text" id="pekerjaan" name="pekerjaan" value="<?php echo isset($verifikasi_data['pekerjaan']) ? $verifikasi_data['pekerjaan'] : ''; ?>" placeholder="Contoh: Pengusaha, Karyawan Swasta, PNS, dll" required>
            </div>

            <div class="form-group">
                <label for="instansi">Instansi/Perusahaan</label>
                <input type="text" id="instansi" name="instansi" value="<?php echo isset($verifikasi_data['instansi_pekerjaan']) ? $verifikasi_data['instansi_pekerjaan'] : ''; ?>" placeholder="Nama instansi atau perusahaan tempat Anda bekerja" required>
            </div>

            <div class="form-group">
                <label for="jabatan">Jabatan</label>
                <input type="text" id="jabatan" name="jabatan" value="<?php echo isset($verifikasi_data['jabatan']) ? $verifikasi_data['jabatan'] : ''; ?>" placeholder="Posisi atau jabatan Anda saat ini" required>
            </div>

            <div class="form-group">
                <label for="penghasilan">Penghasilan per Bulan (Rp)</label>
                <div class="currency-input">
                    <span>Rp</span>
                    <input type="number" id="penghasilan" name="penghasilan" value="<?php echo isset($verifikasi_data['penghasilan']) ? $verifikasi_data['penghasilan'] : ''; ?>" placeholder="Contoh: 5000000" min="0" step="100000" required>
                </div>
            </div>

            <button type="submit" class="btn">Simpan Data Pekerjaan</button>
            <a href="verifikasi_mitra.php" class="btn back-btn">Kembali</a>
        </form>
    </div>
</body>
</html>