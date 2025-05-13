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
    if (isset($_FILES['foto_ktp']) && $_FILES['foto_ktp']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['foto_ktp']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        if (in_array(strtolower($ext), $allowed)) {
            // Cek ukuran file maksimal 2MB
            if ($_FILES['foto_ktp']['size'] > 2 * 1024 * 1024) {
                $message = '<div class="alert error">Ukuran file terlalu besar. Maksimal 2MB.</div>';
            } else {
                // Buat direktori jika belum ada
                $upload_dir = 'uploads/verifikasi/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                // Buat nama file unik
                $new_filename = 'ktp_' . $user_id . '_' . time() . '.' . $ext;
                $destination = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['foto_ktp']['tmp_name'], $destination)) {
                    // Jika data verifikasi belum ada, buat baru
                    if (!$verifikasi_exists) {
                        $query = "INSERT INTO mitra_verifikasi (id_user, foto_ktp) VALUES ($user_id, '$destination')";
                    } else {
                        // Jika sudah ada, update
                        $query = "UPDATE mitra_verifikasi SET foto_ktp = '$destination' WHERE id_user = $user_id";
                    }

                    if (mysqli_query($conn, $query)) {
                        $message = '<div class="alert success">KTP berhasil diunggah!</div>';
                        // Redirect setelah 2 detik
                        header("refresh:2;url=verifikasi_mitra.php");
                    } else {
                        $message = '<div class="alert error">Gagal menyimpan data: ' . mysqli_error($conn) . '</div>';
                    }
                } else {
                    $message = '<div class="alert error">Gagal mengunggah file!</div>';
                }
            }
        } else {
            $message = '<div class="alert error">Format file tidak diizinkan. Hanya JPG, JPEG, dan PNG yang diperbolehkan.</div>';
        }
    } else {
        $message = '<div class="alert error">Silakan pilih file KTP terlebih dahulu!</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Upload KTP - WeKost</title>
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

        .preview-container {
            margin-top: 20px;
            text-align: center;
        }

        #imagePreview {
            max-width: 100%;
            max-height: 300px;
            border: 2px dashed #3498db;
            border-radius: 8px;
            padding: 5px;
            display: none;
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

        .info-box ul {
            padding-left: 20px;
        }

        .info-box li {
            margin-bottom: 5px;
        }

        .upload-box {
            border: 2px dashed #3498db;
            padding: 30px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 20px;
            background-color: #f8f9fa;
            cursor: pointer;
        }

        .upload-box i {
            font-size: 48px;
            color: #3498db;
            margin-bottom: 15px;
            display: block;
        }

        .upload-box p {
            color: #7f8c8d;
            margin-bottom: 10px;
        }

        .file-input {
            display: none;
        }

        .current-file {
            margin-top: 15px;
            padding: 10px;
            background-color: #f1f1f1;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Upload KTP-el</h2>

        <?php echo $message; ?>

        <div class="info-box">
            <h3>Panduan Upload KTP:</h3>
            <ul>
                <li>Pastikan KTP masih berlaku</li>
                <li>Foto KTP dalam kondisi terang dan jelas</li>
                <li>Semua informasi pada KTP harus terbaca</li>
                <li>Format file: JPG, JPEG, atau PNG</li>
                <li>Ukuran maksimal: 2MB</li>
            </ul>
        </div>

        <form action="" method="post" enctype="multipart/form-data">
            <!-- Upload box with preview -->
            <div class="upload-box" id="uploadBox" onclick="document.getElementById('fotoKTP').click();">
                <i>📄</i>
                <p>Klik untuk mengunggah foto KTP Anda</p>
            </div>
            
            <input type="file" name="foto_ktp" id="fotoKTP" class="file-input" accept="image/jpeg,image/png" onchange="previewImage(this)">
            
            <!-- Image preview -->
            <div class="preview-container">
                <img id="imagePreview" src="<?php echo !empty($verifikasi_data['foto_ktp']) ? $verifikasi_data['foto_ktp'] : ''; ?>" 
                    <?php echo !empty($verifikasi_data['foto_ktp']) ? 'style="display:block;"' : ''; ?>>
                
                <?php if (!empty($verifikasi_data['foto_ktp'])): ?>
                <div class="current-file">
                    <p>KTP yang sudah diunggah</p>
                </div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn">Upload KTP</button>
            <a href="verifikasi_mitra.php" class="btn back-btn">Kembali</a>
        </form>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const file = input.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            }
        }

        // Drag and drop functionality
        const uploadBox = document.getElementById('uploadBox');
        
        uploadBox.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.backgroundColor = '#e3f2fd';
        });
        
        uploadBox.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.backgroundColor = '#f8f9fa';
        });
        
        uploadBox.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.backgroundColor = '#f8f9fa';
            
            const fileInput = document.getElementById('fotoKTP');
            fileInput.files = e.dataTransfer.files;
            
            if (fileInput.files.length > 0) {
                previewImage(fileInput);
            }
        });
    </script>
</body>
</html>
