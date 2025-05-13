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

// Cek apakah ada ID kost yang diberikan
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: dashboard_mitra.php");
    exit;
}

$id_kost = (int)$_GET['id'];

// Cek apakah kost ini milik mitra yang login
$check_query = "SELECT * FROM kost WHERE id_kost = $id_kost AND id_mitra = $id_mitra";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) == 0) {
    // Kost tidak ditemukan atau bukan milik mitra ini
    header("Location: dashboard_mitra.php");
    exit;
}

// Ambil data kost
$kost_data = mysqli_fetch_assoc($check_result);

// Ambil foto-foto kost
$foto_query = "SELECT * FROM foto_kost WHERE id_kost = $id_kost ORDER BY urutan";
$foto_result = mysqli_query($conn, $foto_query);
$foto_kost = [];
while ($foto = mysqli_fetch_assoc($foto_result)) {
    $foto_kost[] = $foto;
}

// Ambil dokumen kepemilikan
$dokumen_query = "SELECT * FROM dokumen_kepemilikan WHERE id_kost = $id_kost";
$dokumen_result = mysqli_query($conn, $dokumen_query);
$dokumen = mysqli_fetch_assoc($dokumen_result);

// Proses form jika disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Cek jenis aksi
    if (isset($_POST['action'])) {
        // Jika aksi adalah hapus kost
        if ($_POST['action'] == 'delete') {
            try {
                // Mulai transaksi
                mysqli_begin_transaction($conn);
                
                // Hapus foto-foto kost
                $delete_foto_query = "DELETE FROM foto_kost WHERE id_kost = $id_kost";
                if (!mysqli_query($conn, $delete_foto_query)) {
                    throw new Exception("Gagal menghapus foto kost: " . mysqli_error($conn));
                }
                
                // Hapus dokumen kepemilikan
                $delete_dokumen_query = "DELETE FROM dokumen_kepemilikan WHERE id_kost = $id_kost";
                if (!mysqli_query($conn, $delete_dokumen_query)) {
                    throw new Exception("Gagal menghapus dokumen kepemilikan: " . mysqli_error($conn));
                }
                
                // Hapus kost
                $delete_kost_query = "DELETE FROM kost WHERE id_kost = $id_kost";
                if (!mysqli_query($conn, $delete_kost_query)) {
                    throw new Exception("Gagal menghapus kost: " . mysqli_error($conn));
                }
                
                // Commit transaksi
                mysqli_commit($conn);
                
                // Redirect ke dashboard
                header("Location: dashboard_mitra.php?deleted=1");
                exit;
                
            } catch (Exception $e) {
                // Rollback transaksi jika terjadi error
                mysqli_rollback($conn);
                $error_msg = $e->getMessage();
            }
        } else {
            // Proses update data kost
            // Ambil data dari form
            $nama_kost = mysqli_real_escape_string($conn, $_POST['nama_kost']);
            $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
            $koordinat = mysqli_real_escape_string($conn, $_POST['koordinat']);
            $harga = (int)$_POST['harga'];
            $fasilitas = mysqli_real_escape_string($conn, $_POST['fasilitas']);
            $ketersediaan = (int)$_POST['ketersediaan'];
            
            // Mulai transaksi
            mysqli_begin_transaction($conn);
            
            try {
                // Update data kost
                $update_query = "UPDATE kost SET 
                                nama_kost = '$nama_kost', 
                                alamat = '$alamat', 
                                koordinat_lokasi = '$koordinat', 
                                harga_sewa = $harga, 
                                fasilitas = '$fasilitas', 
                                ketersediaan_kamar = $ketersediaan 
                                WHERE id_kost = $id_kost";
                
                if (!mysqli_query($conn, $update_query)) {
                    throw new Exception("Terjadi kesalahan: " . mysqli_error($conn));
                }
                
                // Proses upload foto baru jika ada
                if (isset($_FILES['foto_kost']) && !empty($_FILES['foto_kost']['name'][0])) {
                    $foto_count = count($_FILES['foto_kost']['name']);
                    $allowed = array('jpg', 'jpeg', 'png');
                    $upload_dir = 'uploads/kost/';
                    
                    // Buat direktori jika belum ada
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Ambil urutan tertinggi dari foto yang sudah ada
                    $max_urutan_query = "SELECT MAX(urutan) as max_urutan FROM foto_kost WHERE id_kost = $id_kost";
                    $max_urutan_result = mysqli_query($conn, $max_urutan_query);
                    $max_urutan = mysqli_fetch_assoc($max_urutan_result)['max_urutan'] ?? 0;
                    
                    $first_new_foto = '';
                    
                    for ($i = 0; $i < $foto_count; $i++) {
                        if ($_FILES['foto_kost']['error'][$i] == 0) {
                            $filename = $_FILES['foto_kost']['name'][$i];
                            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                            
                            if (in_array($ext, $allowed)) {
                                $new_filename = uniqid() . '.' . $ext;
                                $foto_path = $upload_dir . $new_filename;
                                
                                if (move_uploaded_file($_FILES['foto_kost']['tmp_name'][$i], $foto_path)) {
                                    // Simpan data foto ke tabel foto_kost
                                    $urutan = $max_urutan + $i + 1;
                                    $foto_query = "INSERT INTO foto_kost (id_kost, file_foto, urutan) 
                                                VALUES ('$id_kost', '$foto_path', '$urutan')";
                                    
                                    if (!mysqli_query($conn, $foto_query)) {
                                        throw new Exception("Gagal menyimpan data foto: " . mysqli_error($conn));
                                    }
                                    
                                    // Simpan foto pertama yang baru diupload
                                    if ($i == 0) {
                                        $first_new_foto = $foto_path;
                                    }
                                } else {
                                    throw new Exception("Gagal mengupload foto ke server.");
                                }
                            } else {
                                throw new Exception("Format file foto tidak didukung. Gunakan JPG, JPEG, atau PNG.");
                            }
                        }
                    }
                    
                    // Update foto utama jika tidak ada foto sebelumnya
                    if (empty($kost_data['foto_kost']) && !empty($first_new_foto)) {
                        $update_foto_query = "UPDATE kost SET foto_kost = '$first_new_foto' WHERE id_kost = $id_kost";
                        if (!mysqli_query($conn, $update_foto_query)) {
                            throw new Exception("Gagal mengupdate foto utama: " . mysqli_error($conn));
                        }
                    }
                }
                
                // Proses dokumen kepemilikan baru jika ada
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
                            // Cek apakah sudah ada dokumen
                            if ($dokumen) {
                                // Update dokumen
                                $update_doc_query = "UPDATE dokumen_kepemilikan 
                                                    SET jenis_dokumen = '$jenis_dokumen', file_dokumen = '$doc_path' 
                                                    WHERE id_kost = $id_kost";
                                if (!mysqli_query($conn, $update_doc_query)) {
                                    throw new Exception("Gagal mengupdate dokumen: " . mysqli_error($conn));
                                }
                            } else {
                                // Simpan dokumen baru
                                $doc_query = "INSERT INTO dokumen_kepemilikan (id_kost, id_mitra, jenis_dokumen, file_dokumen) 
                                            VALUES ($id_kost, $id_mitra, '$jenis_dokumen', '$doc_path')";
                                if (!mysqli_query($conn, $doc_query)) {
                                    throw new Exception("Gagal menyimpan dokumen: " . mysqli_error($conn));
                                }
                            }
                        } else {
                            throw new Exception("Gagal mengupload dokumen ke server.");
                        }
                    } else {
                        throw new Exception("Format file dokumen tidak didukung. Gunakan PDF, JPG, JPEG, atau PNG.");
                    }
                }
                
                // Hapus foto yang dipilih untuk dihapus
                if (isset($_POST['hapus_foto']) && is_array($_POST['hapus_foto'])) {
                    foreach ($_POST['hapus_foto'] as $id_foto) {
                        $id_foto = (int)$id_foto;
                        
                        // Ambil info foto
                        $get_foto_query = "SELECT file_foto FROM foto_kost WHERE id_foto = $id_foto AND id_kost = $id_kost";
                        $get_foto_result = mysqli_query($conn, $get_foto_query);
                        $foto_info = mysqli_fetch_assoc($get_foto_result);
                        
                        if ($foto_info) {
                            // Hapus file fisik jika ada
                            if (file_exists($foto_info['file_foto'])) {
                                unlink($foto_info['file_foto']);
                            }
                            
                            // Hapus dari database
                            $delete_foto_query = "DELETE FROM foto_kost WHERE id_foto = $id_foto";
                            if (!mysqli_query($conn, $delete_foto_query)) {
                                throw new Exception("Gagal menghapus foto: " . mysqli_error($conn));
                            }
                            
                            // Jika foto ini adalah foto utama, update foto utama dengan foto lain jika ada
                            if ($kost_data['foto_kost'] == $foto_info['file_foto']) {
                                $new_main_foto_query = "SELECT file_foto FROM foto_kost WHERE id_kost = $id_kost ORDER BY urutan LIMIT 1";
                                $new_main_foto_result = mysqli_query($conn, $new_main_foto_query);
                                
                                if (mysqli_num_rows($new_main_foto_result) > 0) {
                                    $new_main_foto = mysqli_fetch_assoc($new_main_foto_result)['file_foto'];
                                    $update_main_foto_query = "UPDATE kost SET foto_kost = '$new_main_foto' WHERE id_kost = $id_kost";
                                    mysqli_query($conn, $update_main_foto_query);
                                } else {
                                    // Tidak ada foto tersisa, kosongkan foto utama
                                    $update_main_foto_query = "UPDATE kost SET foto_kost = '' WHERE id_kost = $id_kost";
                                    mysqli_query($conn, $update_main_foto_query);
                                }
                            }
                        }
                    }
                }
                
                // Commit transaksi jika semua proses berhasil
                mysqli_commit($conn);
                $success_msg = "Data kost berhasil diperbarui.";
                
                // Ambil data kost yang sudah diupdate
                $kost_data_query = "SELECT * FROM kost WHERE id_kost = $id_kost";
                $kost_data_result = mysqli_query($conn, $kost_data_query);
                $kost_data = mysqli_fetch_assoc($kost_data_result);
                
                // Ambil foto-foto kost yang sudah diupdate
                $foto_query = "SELECT * FROM foto_kost WHERE id_kost = $id_kost ORDER BY urutan";
                $foto_result = mysqli_query($conn, $foto_query);
                $foto_kost = [];
                while ($foto = mysqli_fetch_assoc($foto_result)) {
                    $foto_kost[] = $foto;
                }
                
                // Ambil dokumen kepemilikan yang sudah diupdate
                $dokumen_query = "SELECT * FROM dokumen_kepemilikan WHERE id_kost = $id_kost";
                $dokumen_result = mysqli_query($conn, $dokumen_query);
                $dokumen = mysqli_fetch_assoc($dokumen_result);
                
            } catch (Exception $e) {
                // Rollback transaksi jika terjadi error
                mysqli_rollback($conn);
                $error_msg = $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kost</title>
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
        .cancel-button {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 10px;
            text-align: center;
            display: block;
            text-decoration: none;
        }
        .delete-button {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 10px;
        }
        .submit-button:hover {
            background-color: #0056a6;
        }
        .cancel-button:hover {
            background-color: #5a6268;
        }
        .delete-button:hover {
            background-color: #c82333;
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
        .existing-image {
            position: relative;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .checkbox-overlay {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 3px;
            padding: 3px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .status-verified {
            background-color: #d4edda;
            color: #155724;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border-radius: 5px;
            width: 80%;
            max-width: 400px;
        }
        .modal-title {
            margin-bottom: 15px;
            font-size: 18px;
            color: #dc3545;
        }
        .modal-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .modal-cancel {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        .modal-confirm {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
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
            <h1>Edit Informasi Kost</h1>
        </div>
        
        <!-- Form Container -->
        <div class="form-container">
            <?php if(!empty($error_msg)): ?>
                <div class="error-message"><?php echo $error_msg; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($success_msg)): ?>
                <div class="success-message"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            
            <!-- Status Badge -->
            <?php if ($kost_data['status_verifikasi'] == 'verified'): ?>
                <div class="status-badge status-verified">
                    <i class="fas fa-check-circle"></i> Terverifikasi
                </div>
            <?php elseif ($kost_data['status_verifikasi'] == 'pending'): ?>
                <div class="status-badge status-pending">
                    <i class="fas fa-clock"></i> Menunggu Verifikasi
                </div>
            <?php elseif ($kost_data['status_verifikasi'] == 'rejected'): ?>
                <div class="status-badge status-rejected">
                    <i class="fas fa-times-circle"></i> Ditolak
                </div>
            <?php endif; ?>
            
            <form action="edit_kost.php?id=<?php echo $id_kost; ?>" method="post" enctype="multipart/form-data" id="formKost">
                <input type="hidden" name="action" value="update">
                
                <h3 class="section-title">Informasi Kost</h3>
                
                <div class="form-group">
                    <label class="form-label">Nama Kost *</label>
                    <input type="text" name="nama_kost" class="form-input" value="<?php echo htmlspecialchars($kost_data['nama_kost']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Alamat Lengkap *</label>
                    <textarea name="alamat" class="form-textarea" required><?php echo htmlspecialchars($kost_data['alamat']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Koordinat Lokasi (Latitude, Longitude)</label>
                    <input type="text" name="koordinat" class="form-input" placeholder="Contoh: -7.123456, 110.123456" value="<?php echo htmlspecialchars($kost_data['koordinat_lokasi']); ?>">
                    <p class="info-text">Kosongkan jika tidak tahu. Admin akan membantu mengisi koordinat lokasi.</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Harga Sewa per Bulan (Rp) *</label>
                    <input type="number" name="harga" class="form-input" step="100000" value="<?php echo $kost_data['harga_sewa']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Fasilitas *</label>
                    <textarea name="fasilitas" class="form-textarea" placeholder="Contoh: AC, Kamar mandi dalam, Wifi, dll." required><?php echo htmlspecialchars($kost_data['fasilitas']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Jumlah Kamar Tersedia *</label>
                    <input type="number" name="ketersediaan" class="form-input" value="<?php echo $kost_data['ketersediaan_kamar']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Foto Kost</label>
                    
                    <?php if (count($foto_kost) > 0): ?>
                        <p>Foto saat ini:</p>
                        <div class="preview-container" id="existingPhotos">
                            <?php foreach ($foto_kost as $foto): ?>
                                <div class="existing-image">
                                    <img src="<?php echo $foto['file_foto']; ?>" class="img-preview">
                                    <div class="checkbox-overlay">
                                        <input type="checkbox" name="hapus_foto[]" value="<?php echo $foto['id_foto']; ?>" id="hapus_<?php echo $foto['id_foto']; ?>">
                                        <label for="hapus_<?php echo $foto['id_foto']; ?>">Hapus</label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>Belum ada foto yang diupload.</p>
                    <?php endif; ?>
                    
                    <p class="info-text">Tambah foto baru:</p>
                    <input type="file" name="foto_kost[]" class="form-file file-input-hidden" id="fotoInput" accept="image/jpeg, image/jpg, image/png" multiple>
                    <p class="info-text">Format yang didukung: JPG, JPEG, PNG</p>
                    
                    <div class="preview-container" id="previewContainer">
                        <div class="add-more-photos" id="addMorePhotos">
                            <i class="fas fa-plus"></i>
                        </div>
                    </div>
                </div>
                
                <h3 class="section-title">Dokumen Kepemilikan</h3>
                <p class="info-text">Dokumen ini diperlukan untuk verifikasi kepemilikan kost.</p>
                
                <div class="form-group">
                    <label class="form-label">Jenis Dokumen *</label>
                    <select name="jenis_dokumen" class="form-select" required>
                        <option value="">Pilih Jenis Dokumen</option>
                        <option value="Sertifikat Tanah" <?php echo ($dokumen && $dokumen['jenis_dokumen'] == 'Sertifikat Tanah') ? 'selected' : ''; ?>>Sertifikat Tanah</option>
                        <option value="Izin Usaha" <?php echo ($dokumen && $dokumen['jenis_dokumen'] == 'Izin Usaha') ? 'selected' : ''; ?>>Izin Usaha</option>
                        <option value="PBB" <?php echo ($dokumen && $dokumen['jenis_dokumen'] == 'PBB') ? 'selected' : ''; ?>>PBB</option>
                        <option value="Akta Jual Beli" <?php echo ($dokumen && $dokumen['jenis_dokumen'] == 'Akta Jual Beli') ? 'selected' : ''; ?>>Akta Jual Beli</option>
                        <option value="Lainnya" <?php echo ($dokumen && $dokumen['jenis_dokumen'] == 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <?php if ($dokumen): ?>
                        <p>Dokumen saat ini:
                        <?php 
                            $doc_ext = pathinfo($dokumen['file_dokumen'], PATHINFO_EXTENSION);
                            if (in_array($doc_ext, ['jpg', 'jpeg', 'png'])) {
                                echo '<br><img src="' . $dokumen['file_dokumen'] . '" style="max-width: 200px; margin-top: 10px;">';
                            } else {
                                echo '<br><i class="fas fa-file-pdf" style="font-size: 40px; color: #e74c3c; margin-top: 10px;"></i> ' . basename($dokumen['file_dokumen']);
                            }
                        ?>
                        </p>
                        <p class="info-text">Upload dokumen baru untuk mengganti dokumen saat ini</p>
                    <?php endif; ?>
                    
                    <label class="form-label"><?php echo $dokumen ? 'Ganti Dokumen' : 'Upload Dokumen *'; ?></label>
                    <input type="file" name="dokumen" class="form-file" id="dokumenInput" <?php echo $dokumen ? '' : 'required'; ?>>
                    <p class="info-text">Format yang didukung: PDF, JPG, JPEG, PNG</p>
                    <div id="dokumenPreview" style="margin-top:10px;"></div>
                </div>
                
                <button type="submit" class="submit-button">SIMPAN PERUBAHAN</button>
                <a href="dashboard_mitra.php" class="cancel-button">BATALKAN</a>
                <button type="button" class="delete-button" id="deleteButton">HAPUS KOST</button>
            </form>
            
            <!-- Form untuk hapus kost -->
            <form action="edit_kost.php?id=<?php echo $id_kost; ?>" method="post" id="deleteForm" style="display: none;">
                <input type="hidden" name="action" value="delete">
            </form>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-title">
                <i class="fas fa-exclamation-triangle"></i> Konfirmasi Hapus
            </div>
            <p>Apakah Anda yakin ingin menghapus kost ini? Tindakan ini tidak dapat dibatalkan.</p>
            <div class="modal-buttons">
                <button class="modal-cancel" id="cancelDelete">Batal</button>
                <button class="modal-confirm" id="confirmDelete">Hapus</button>
            </div>
        </div>
    </div>

    <!-- JavaScript untuk preview foto dan konfirmasi hapus -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addMorePhotos = document.getElementById('addMorePhotos');
            const fotoInput = document.getElementById('fotoInput');
            const previewContainer = document.getElementById('previewContainer');
            const dokumenInput = document.getElementById('dokumenInput');
            const dokumenPreview = document.getElementById('dokumenPreview');
            const deleteButton = document.getElementById('deleteButton');
            const deleteModal = document.getElementById('deleteModal');
            const cancelDelete = document.getElementById('cancelDelete');
            const confirmDelete = document.getElementById('confirmDelete');
            const deleteForm = document.getElementById('deleteForm');
            
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
            
            // Event untuk tombol hapus
            deleteButton.addEventListener('click', function() {
                deleteModal.style.display = 'block';
            });
            
            // Event untuk tombol batal hapus
            cancelDelete.addEventListener('click', function() {
                deleteModal.style.display = 'none';
            });
            
            // Event untuk tombol konfirmasi hapus
            confirmDelete.addEventListener('click', function() {
                deleteForm.submit();
            });
            
            // Tutup modal jika diklik di luar modal
            window.addEventListener('click', function(event) {
                if (event.target === deleteModal) {
                    deleteModal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>