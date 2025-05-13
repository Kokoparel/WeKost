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

// Cek apakah id_kost tersedia di parameter URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: dashboard_mitra.php");
    exit;
}

$id_kost = mysqli_real_escape_string($conn, $_GET['id']);

// Ambil data kost dari database
$query = "SELECT k.*, 
            CASE 
                WHEN k.status_verifikasi = 'menunggu' THEN 'Menunggu Verifikasi'
                WHEN k.status_verifikasi = 'diterima' THEN 'Terverifikasi'
                WHEN k.status_verifikasi = 'ditolak' THEN 'Ditolak'
                ELSE 'Tidak Diketahui'
            END AS status_text,
            DATE_FORMAT(k.tanggal_penambahan, '%d %M %Y') AS tanggal_dibuat,
            d.jenis_dokumen, d.file_dokumen
          FROM kost k
          LEFT JOIN dokumen_kepemilikan d ON k.id_kost = d.id_kost
          WHERE k.id_kost = '$id_kost' AND k.id_mitra = '$id_mitra'";

$result = mysqli_query($conn, $query);

// Check if query was successful
if (!$result) {
    // Print error for debugging
    echo "Error in query: " . mysqli_error($conn);
    exit;
}

if (mysqli_num_rows($result) == 0) {
    header("Location: dashboard_mitra.php");
    exit;
}

$kost = mysqli_fetch_assoc($result);

// Ambil semua foto kost
$query_foto = "SELECT * FROM foto_kost WHERE id_kost = '$id_kost' ORDER BY urutan ASC";
$result_foto = mysqli_query($conn, $query_foto);
$fotos = [];

// Check if query was successful
if (!$result_foto) {
    // Print error for debugging
    echo "Error in foto query: " . mysqli_error($conn);
    // Continue execution even if no photos found
} else {
    while ($row = mysqli_fetch_assoc($result_foto)) {
        $fotos[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Kost</title>
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
        .content-container {
            padding: 20px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            margin: 30px 0 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            color: #0074E4;
        }
        .kost-images {
            width: 100%;
            position: relative;
            height: 220px;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .kost-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
        }
        .image-nav {
            position: absolute;
            bottom: 10px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 6px;
        }
        .image-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
        }
        .image-dot.active {
            background-color: #fff;
        }
        .image-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(0, 0, 0, 0.4);
            color: white;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
        }
        .prev-arrow {
            left: 10px;
        }
        .next-arrow {
            right: 10px;
        }
        .info-item {
            margin-bottom: 15px;
        }
        .info-label {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .info-value {
            color: #555;
            line-height: 1.5;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            color: white;
            margin-bottom: 5px;
        }
        .status-pending {
            background-color: #f39c12;
        }
        .status-verified {
            background-color: #27ae60;
        }
        .status-rejected {
            background-color: #e74c3c;
        }
        .notification-box {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }
        .notification-pending {
            background-color: #fff3cd;
            border-left: 4px solid #f39c12;
            color: #856404;
        }
        .notification-verified {
            background-color: #d4edda;
            border-left: 4px solid #27ae60;
            color: #155724;
        }
        .notification-rejected {
            background-color: #f8d7da;
            border-left: 4px solid #e74c3c;
            color: #721c24;
        }
        .action-button {
            background-color: #0074E4;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }
        .action-button:hover {
            background-color: #0056a6;
        }
        .action-button.edit {
            background-color: #f39c12;
        }
        .action-button.edit:hover {
            background-color: #e67e22;
        }
        .harga {
            font-size: 20px;
            font-weight: bold;
            color: #0074E4;
            margin: 10px 0;
        }
        .document-preview {
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            display: flex;
            align-items: center;
        }
        .document-icon {
            font-size: 36px;
            color: #e74c3c;
            margin-right: 10px;
        }
        .document-info {
            flex: 1;
        }
        .document-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .document-action {
            color: #0074E4;
            text-decoration: none;
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
            <h1>Detail Kost</h1>
        </div>
        
        <!-- Content Container -->
        <div class="content-container">
            <!-- Status Verifikasi Notification -->
            <?php if($kost['status_verifikasi'] == 'menunggu'): ?>
                <div class="notification-box notification-pending">
                    <strong>Status: Menunggu Verifikasi</strong><br>
                    Data kost Anda sedang dalam proses verifikasi oleh admin. Proses verifikasi akan selesai paling lambat 2x24 jam sejak tanggal pengajuan (<?php echo $kost['tanggal_dibuat']; ?>).
                </div>
            <?php elseif($kost['status_verifikasi'] == 'diterima'): ?>
                <div class="notification-box notification-verified">
                    <strong>Status: Terverifikasi</strong><br>
                    Selamat! Data kost Anda telah diverifikasi dan telah ditampilkan di aplikasi.
                </div>
            <?php elseif($kost['status_verifikasi'] == 'ditolak'): ?>
                <div class="notification-box notification-rejected">
                    <strong>Status: Ditolak</strong><br>
                    Maaf, data kost Anda ditolak. Silakan periksa kembali data dan dokumen yang diupload kemudian coba lagi.
                    <?php if(!empty($kost['alasan_penolakan'])): ?>
                        <br><br><strong>Alasan Penolakan:</strong> <?php echo $kost['alasan_penolakan']; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Foto Kost Carousel -->
            <div class="kost-images">
                <?php if(count($fotos) > 0): ?>
                    <?php foreach($fotos as $index => $foto): ?>
                        <img src="<?php echo $foto['file_foto']; ?>" class="kost-image" id="image-<?php echo $index; ?>" style="display: <?php echo $index == 0 ? 'block' : 'none'; ?>">
                    <?php endforeach; ?>
                    
                    <?php if(count($fotos) > 1): ?>
                        <button class="image-arrow prev-arrow" onclick="changeImage(-1)">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="image-arrow next-arrow" onclick="changeImage(1)">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        
                        <div class="image-nav">
                            <?php for($i = 0; $i < count($fotos); $i++): ?>
                                <div class="image-dot <?php echo $i == 0 ? 'active' : ''; ?>" onclick="showImage(<?php echo $i; ?>)"></div>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <img src="img/no-image.jpg" class="kost-image">
                <?php endif; ?>
            </div>
            
            <!-- Informasi Kost -->
            <div class="section">
                <h2><?php echo $kost['nama_kost']; ?></h2>
                <div class="harga">Rp <?php echo number_format($kost['harga_sewa'], 0, ',', '.'); ?> / bulan</div>
                
                <!-- Added Status Kost Display -->
                <div class="info-item">
                    <div class="info-label">Status Ketersediaan</div>
                    <div class="info-value">
                        <?php 
                        if ($kost['status_kost'] == 'tersedia') {
                            echo '<span style="color: #27ae60; font-weight: bold;">Tersedia</span>';
                        } else {
                            echo '<span style="color: #e74c3c; font-weight: bold;">Penuh</span>';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Alamat</div>
                    <div class="info-value"><?php echo $kost['alamat']; ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Koordinat Lokasi</div>
                    <div class="info-value">
                        <?php echo !empty($kost['koordinat_lokasi']) ? $kost['koordinat_lokasi'] : 'Belum diatur'; ?>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Fasilitas</div>
                    <div class="info-value"><?php echo nl2br($kost['fasilitas']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Ketersediaan Kamar</div>
                    <div class="info-value"><?php echo $kost['ketersediaan_kamar']; ?> kamar</div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Tanggal Pengajuan</div>
                    <div class="info-value"><?php echo $kost['tanggal_dibuat']; ?></div>
                </div>
                
            </div>
            
            <!-- Dokumen Kepemilikan -->
            <h3 class="section-title">Dokumen Kepemilikan</h3>
            <div class="section">
                <div class="info-item">
                    <div class="info-label">Jenis Dokumen</div>
                    <div class="info-value"><?php echo $kost['jenis_dokumen']; ?></div>
                </div>
                
                <?php if(!empty($kost['file_dokumen'])): ?>
                    <div class="document-preview">
                        <?php
                        $ext = pathinfo($kost['file_dokumen'], PATHINFO_EXTENSION);
                        $icon_class = ($ext == 'pdf') ? 'fa-file-pdf' : 'fa-file-image';
                        ?>
                        <div class="document-icon">
                            <i class="fas <?php echo $icon_class; ?>"></i>
                        </div>
                        <div class="document-info">
                            <div class="document-name">Dokumen <?php echo $kost['jenis_dokumen']; ?></div>
                            <a href="<?php echo $kost['file_dokumen']; ?>" class="document-action" target="_blank">Lihat Dokumen</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <a href="dashboard_mitra.php" class="action-button">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>

    <!-- JavaScript untuk carousel foto -->
    <script>
        let currentImageIndex = 0;
        const totalImages = <?php echo count($fotos); ?>;
        
        function showImage(index) {
            // Sembunyikan semua gambar
            for (let i = 0; i < totalImages; i++) {
                document.getElementById(`image-${i}`).style.display = 'none';
                document.querySelectorAll('.image-dot')[i].classList.remove('active');
            }
            
            // Tampilkan gambar yang dipilih
            document.getElementById(`image-${index}`).style.display = 'block';
            document.querySelectorAll('.image-dot')[index].classList.add('active');
            
            // Update index saat ini
            currentImageIndex = index;
        }
        
        function changeImage(direction) {
            let newIndex = currentImageIndex + direction;
            
            // Jika index melebihi batas, putar kembali
            if (newIndex >= totalImages) {
                newIndex = 0;
            } else if (newIndex < 0) {
                newIndex = totalImages - 1;
            }
            
            showImage(newIndex);
        }
    </script>
</body>
</html>