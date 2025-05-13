<?php
// Start session and include configuration
session_start();
include 'config.php';

// Cek apakah user sudah login sebagai mitra
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'mitra') {
    header("Location: login_form.php");
    exit;
}

$id_mitra = $_SESSION['user_id'];
$error_msg = "";
$success_msg = "";

// Proses form jika disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $nama_kost = mysqli_real_escape_string($conn, $_POST['nama_kost']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $koordinat = mysqli_real_escape_string($conn, $_POST['koordinat']);
    $harga = (int)$_POST['harga'];
    $fasilitas = mysqli_real_escape_string($conn, $_POST['fasilitas']);
    $ketersediaan = (int)$_POST['ketersediaan'];
    $status_kost = mysqli_real_escape_string($conn, $_POST['status_kost']);
    
    // Variabel untuk menyimpan foto utama
    $foto_utama = "";
    
    // Mulai transaksi
    mysqli_begin_transaction($conn);
    
    try {
        // Simpan data kost ke database tanpa foto dulu
        $query = "INSERT INTO kost (id_mitra, nama_kost, alamat, koordinat_lokasi, harga_sewa, fasilitas, ketersediaan_kamar, status_kost) 
                  VALUES ('$id_mitra', '$nama_kost', '$alamat', '$koordinat', '$harga', '$fasilitas', '$ketersediaan', '$status_kost')";
        
        if (!mysqli_query($conn, $query)) {
            throw new Exception("Terjadi kesalahan: " . mysqli_error($conn));
        }
        
        // Ambil ID kost yang baru saja dibuat
        $id_kost = mysqli_insert_id($conn);
        
        // Proses upload multiple foto
        if (isset($_FILES['foto_kost']) && !empty($_FILES['foto_kost']['name'][0])) {
            $foto_count = count($_FILES['foto_kost']['name']);
            $allowed = array('jpg', 'jpeg', 'png');
            $upload_dir = 'uploads/kost/';
            
            // Buat direktori jika belum ada
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            for ($i = 0; $i < $foto_count; $i++) {
                if ($_FILES['foto_kost']['error'][$i] == 0) {
                    $filename = $_FILES['foto_kost']['name'][$i];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    
                    if (in_array($ext, $allowed)) {
                        $new_filename = uniqid() . '.' . $ext;
                        $foto_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['foto_kost']['tmp_name'][$i], $foto_path)) {
                            // Simpan data foto ke tabel foto_kost
                            $urutan = $i + 1;
                            $foto_query = "INSERT INTO foto_kost (id_kost, file_foto, urutan) 
                                          VALUES ('$id_kost', '$foto_path', '$urutan')";
                            
                            if (!mysqli_query($conn, $foto_query)) {
                                throw new Exception("Gagal menyimpan data foto: " . mysqli_error($conn));
                            }
                            
                            // Gunakan foto pertama sebagai foto utama
                            if ($i == 0) {
                                $foto_utama = $foto_path;
                            }
                        } else {
                            throw new Exception("Gagal mengupload foto ke server.");
                        }
                    } else {
                        throw new Exception("Format file foto tidak didukung. Gunakan JPG, JPEG, atau PNG.");
                    }
                }
            }
            
            // Update foto utama ke tabel kost
            $update_foto_query = "UPDATE kost SET foto_kost = '$foto_utama' WHERE id_kost = '$id_kost'";
            if (!mysqli_query($conn, $update_foto_query)) {
                throw new Exception("Gagal mengupdate foto utama: " . mysqli_error($conn));
            }
        } else {
            throw new Exception("Minimal satu foto kost harus diupload.");
        }
        
        // Proses dokumen kepemilikan
        if (isset($_FILES['dokumen']) && $_FILES['dokumen']['error'] == 0) {
            $jenis_dokumen = mysqli_real_escape_string($conn, $_POST['jenis_dokumen']);
            $allowed_doc = array('pdf', 'jpg', 'jpeg', 'png');
            $doc_filename = $_FILES['dokumen']['name'];
            $doc_ext = strtolower(pathinfo($doc_filename, PATHINFO_EXTENSION));
            
            if (in_array($doc_ext, $allowed_doc)) {
                $new_doc_filename = uniqid() . '.' . $doc_ext;
                $doc_upload_dir = 'uploads/dokumen/';
                
                // Buat direktori jika belum ada
                if (!file_exists($doc_upload_dir)) {
                    mkdir($doc_upload_dir, 0777, true);
                }
                
                $doc_path = $doc_upload_dir . $new_doc_filename;
                if (move_uploaded_file($_FILES['dokumen']['tmp_name'], $doc_path)) {
                    // Simpan dokumen ke database
                    $doc_query = "INSERT INTO dokumen_kepemilikan (id_kost, id_mitra, jenis_dokumen, file_dokumen) 
                                  VALUES ('$id_kost', '$id_mitra', '$jenis_dokumen', '$doc_path')";
                    if (!mysqli_query($conn, $doc_query)) {
                        throw new Exception("Gagal menyimpan dokumen: " . mysqli_error($conn));
                    }
                } else {
                    throw new Exception("Gagal mengupload dokumen ke server.");
                }
            } else {
                throw new Exception("Format file dokumen tidak didukung. Gunakan PDF, JPG, JPEG, atau PNG.");
            }
        } else {
            throw new Exception("Dokumen kepemilikan harus diupload.");
        }
        
        // Commit transaksi jika semua proses berhasil
        mysqli_commit($conn);
        $success_msg = "Data kost berhasil ditambahkan dan menunggu verifikasi dari admin.";
        
    } catch (Exception $e) {
        // Rollback transaksi jika terjadi error
        mysqli_rollback($conn);
        $error_msg = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kost</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 480px;
            margin: 0 auto;
            min-height: 100vh;
            position: relative;
            background-color: #fff;
            padding-bottom: 70px;
        }
        .header {
            background-color: #0074E4;
            color: white;
            padding: 15px;
            display: flex;
            align-items: center;
        }
        .header h1 {
            font-size: 18px;
            margin-left: 15px;
        }
        .back-button {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
        }
        .form-container {
            padding: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-textarea {
            height: 100px;
            resize: vertical;
        }
        .form-file {
            margin-top: 5px;
        }
        .error-message {
            color: #e74c3c;
            margin-top: 20px;
            padding: 10px;
            background-color: #fadbd8;
            border-radius: 5px;
        }
        .success-message {
            color: #27ae60;
            margin-top: 20px;
            padding: 10px;
            background-color: #d4efdf;
            border-radius: 5px;
        }
        .submit-button {
            background-color: #0074E4;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 20px;
        }
        .submit-button:hover {
            background-color: #0056a6;
        }
        .info-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .section-title {
            margin: 30px 0 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            color: #0074E4;
        }
        .preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .img-preview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .preview-item {
            position: relative;
        }
        .preview-remove {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #e74c3c;
            color: white;
            border: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        .add-more-photos {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100px;
            height: 100px;
            border: 2px dashed #ddd;
            border-radius: 5px;
            cursor: pointer;
            color: #0074E4;
        }
        .add-more-photos i {
            font-size: 30px;
        }
        .file-input-hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <a href="dashboard_mitra.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1>Tambah Informasi Kost</h1>
        </div>
        
        <!-- Form Container -->
        <div class="form-container">
            <?php if(!empty($error_msg)): ?>
                <div class="error-message"><?php echo $error_msg; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($success_msg)): ?>
                <div class="success-message">
                    <?php echo $success_msg; ?>
                    <p><a href="dashboard_mitra.php">Kembali ke dashboard</a></p>
                </div>
            <?php else: ?>
                <form action="tambah_kost.php" method="post" enctype="multipart/form-data" id="formKost">
                    <h3 class="section-title">Informasi Kost</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Nama Kost *</label>
                        <input type="text" name="nama_kost" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Alamat Lengkap *</label>
                        <textarea name="alamat" class="form-textarea" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Koordinat Lokasi (Latitude, Longitude)</label>
                        <input type="text" name="koordinat" class="form-input" placeholder="Contoh: -7.123456, 110.123456">
                        <p class="info-text">Kosongkan jika tidak tahu. Admin akan membantu mengisi koordinat lokasi.</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Harga Sewa per Bulan (Rp) *</label>
                        <input type="number" name="harga" class="form-input" step="100000" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Fasilitas *</label>
                        <textarea name="fasilitas" class="form-textarea" placeholder="Contoh: AC, Kamar mandi dalam, Wifi, dll." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Jumlah Kamar Tersedia *</label>
                        <input type="number" name="ketersediaan" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Status Kost *</label>
                        <select name="status_kost" class="form-select" required>
                            <option value="tersedia">Tersedia</option>
                            <option value="penuh">Penuh</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Foto Kost * (Minimal 1 foto)</label>
                        <input type="file" name="foto_kost[]" class="form-file file-input-hidden" id="fotoInput" accept="image/jpeg, image/jpg, image/png" multiple>
                        <p class="info-text">Format yang didukung: JPG, JPEG, PNG</p>
                        
                        <div class="preview-container" id="previewContainer">
                            <div class="add-more-photos" id="addMorePhotos">
                                <i class="fas fa-plus"></i>
                            </div>
                        </div>
                    </div>
                    
                    <h3 class="section-title">Dokumen Kepemilikan</h3>
                    <p class="info-text">Dokumen ini diperlukan untuk verifikasi kepemilikan kost. Contoh dokumen: Sertifikat tanah, Izin usaha, PBB, atau dokumen legal lainnya.</p>
                    
                    <div class="form-group">
                        <label class="form-label">Jenis Dokumen *</label>
                        <select name="jenis_dokumen" class="form-select" required>
                            <option value="">Pilih Jenis Dokumen</option>
                            <option value="Sertifikat Tanah">Sertifikat Tanah</option>
                            <option value="Izin Usaha">Izin Usaha</option>
                            <option value="PBB">PBB</option>
                            <option value="Akta Jual Beli">Akta Jual Beli</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Upload Dokumen *</label>
                        <input type="file" name="dokumen" class="form-file" id="dokumenInput" required>
                        <p class="info-text">Format yang didukung: PDF, JPG, JPEG, PNG</p>
                        <div id="dokumenPreview" style="margin-top:10px;"></div>
                    </div>
                    
                    <button type="submit" class="submit-button">SIMPAN & AJUKAN VERIFIKASI</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript untuk preview foto -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addMorePhotos = document.getElementById('addMorePhotos');
            const fotoInput = document.getElementById('fotoInput');
            const previewContainer = document.getElementById('previewContainer');
            const dokumenInput = document.getElementById('dokumenInput');
            const dokumenPreview = document.getElementById('dokumenPreview');
            
            let selectedFiles = [];
            
            // Tambah foto saat klik tombol tambah
            addMorePhotos.addEventListener('click', function() {
                fotoInput.click();
            });
            
            // Preview foto saat dipilih
            fotoInput.addEventListener('change', function(e) {
                const files = Array.from(e.target.files);
                
                files.forEach(file => {
                    // Cek jika file belum dipilih sebelumnya
                    if (!selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                        selectedFiles.push(file);
                        
                        // Buat element preview
                        const previewItem = document.createElement('div');
                        previewItem.className = 'preview-item';
                        
                        const preview = document.createElement('img');
                        preview.className = 'img-preview';
                        preview.file = file;
                        
                        const removeBtn = document.createElement('button');
                        removeBtn.className = 'preview-remove';
                        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                        removeBtn.addEventListener('click', function() {
                            selectedFiles = selectedFiles.filter(f => f !== file);
                            previewItem.remove();
                            updateFileInput();
                        });
                        
                        previewItem.appendChild(preview);
                        previewItem.appendChild(removeBtn);
                        previewContainer.insertBefore(previewItem, addMorePhotos);
                        
                        // Tampilkan preview
                        const reader = new FileReader();
                        reader.onload = (function(image) { 
                            return function(e) { 
                                image.src = e.target.result; 
                            }; 
                        })(preview);
                        reader.readAsDataURL(file);
                    }
                });
                
                updateFileInput();
            });
            
            // Preview dokumen
            dokumenInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                dokumenPreview.innerHTML = '';
                
                if (file) {
                    const fileExt = file.name.split('.').pop().toLowerCase();
                    
                    if (['jpg', 'jpeg', 'png'].includes(fileExt)) {
                        const img = document.createElement('img');
                        img.className = 'img-preview';
                        dokumenPreview.appendChild(img);
                        
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            img.src = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    } else if (fileExt === 'pdf') {
                        const pdfIcon = document.createElement('div');
                        pdfIcon.innerHTML = '<i class="fas fa-file-pdf" style="font-size: 48px; color: #e74c3c;"></i><p>' + file.name + '</p>';
                        dokumenPreview.appendChild(pdfIcon);
                    }
                }
            });
            
            // Update file input dengan file yang dipilih
            function updateFileInput() {
                const dataTransfer = new DataTransfer();
                selectedFiles.forEach(file => {
                    dataTransfer.items.add(file);
                });
                fotoInput.files = dataTransfer.files;
            }
        });
    </script>
</body>
</html>