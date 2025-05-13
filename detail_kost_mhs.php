<?php
// Start session and include configuration
session_start();
include 'config.php';

// Check if user is logged in as a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'mahasiswa') {
    header("Location: login_form.php");
    exit;
}

// Get student data
$id_mhs = $_SESSION['user_id'];
$query_mhs = "SELECT * FROM users WHERE id_user = '$id_mhs'";
$result_mhs = mysqli_query($conn, $query_mhs);
$mahasiswa = mysqli_fetch_assoc($result_mhs);

// Get kost ID from URL parameter
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: dashboard_mhs.php");
    exit;
}

$id_kost = mysqli_real_escape_string($conn, $_GET['id']);

// Get kost data including mitra information
$query_kost = "SELECT k.*, u.nama_lengkap as nama_mitra, u.no_hp as telepon_mitra, u.email as email_mitra, u.id_user as id_mitra
               FROM kost k 
               JOIN users u ON k.id_mitra = u.id_user 
               WHERE k.id_kost = '$id_kost' AND k.status_verifikasi = 'diterima'";
$result_kost = mysqli_query($conn, $query_kost);

// Check if kost exists and is verified
if (mysqli_num_rows($result_kost) == 0) {
    header("Location: dashboard_mhs.php");
    exit;
}

$kost = mysqli_fetch_assoc($result_kost);

// // Check if the kost is bookmarked by the user
// $query_bookmark = "SELECT * FROM bookmarks WHERE id_mahasiswa = '$id_mhs' AND id_kost = '$id_kost'";
// $result_bookmark = mysqli_query($conn, $query_bookmark);
// $is_bookmarked = mysqli_num_rows($result_bookmark) > 0;

// // Handle bookmark toggling
// if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bookmark_action'])) {
//     $action = $_POST['bookmark_action'];
    
//     if ($action == 'add') {
//         $query_add_bookmark = "INSERT INTO bookmarks (id_mahasiswa, id_kost) VALUES ('$id_mhs', '$id_kost')";
//         mysqli_query($conn, $query_add_bookmark);
//     } else if ($action == 'remove') {
//         $query_remove_bookmark = "DELETE FROM bookmarks WHERE id_mahasiswa = '$id_mhs' AND id_kost = '$id_kost'";
//         mysqli_query($conn, $query_remove_bookmark);
//     }
    
//     // Refresh the page to update the bookmark status
//     header("Location: detail_kost_mhs.php?id=$id_kost");
//     exit;
// }

// Check if a chat room exists for this student, owner, and kost
$query_check_room = "SELECT id_room FROM chat_rooms 
                     WHERE id_mahasiswa = '$id_mhs' 
                     AND id_mitra = '{$kost['id_mitra']}'
                     AND id_kost = '$id_kost'";
$result_check_room = mysqli_query($conn, $query_check_room);
$existing_room = mysqli_fetch_assoc($result_check_room);
$chat_room_id = $existing_room ? $existing_room['id_room'] : null;

// Handle starting a new chat
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['start_chat'])) {
    if (!$chat_room_id) {
        // Create a new chat room if it doesn't exist
        $query_create_room = "INSERT INTO chat_rooms (id_mahasiswa, id_mitra, id_kost) 
                              VALUES ('$id_mhs', '{$kost['id_mitra']}', '$id_kost')";
        if (mysqli_query($conn, $query_create_room)) {
            $chat_room_id = mysqli_insert_id($conn);
            
            // Optionally send an initial message
            $initial_message = "Halo, saya tertarik dengan kost " . $kost['nama_kost'] . ".";
            $query_send_message = "INSERT INTO chat (id_pengirim, id_penerima, id_kost, pesan, status_baca) 
                                  VALUES ('$id_mhs', '{$kost['id_mitra']}', '$id_kost', '$initial_message', 'belum_dibaca')";
            mysqli_query($conn, $query_send_message);
            
            // Update the chat room with the initial message
            $query_update_room = "UPDATE chat_rooms SET last_message = '$initial_message', unread_count = 1 
                                 WHERE id_room = '$chat_room_id'";
            mysqli_query($conn, $query_update_room);
        }
    }
    
    // Redirect to the chat detail page with the room ID
    header("Location: chat_detail.php?room=$chat_room_id&user={$kost['id_mitra']}&kost=$id_kost");
    exit;
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $kost['nama_kost']; ?> - WeKost</title>
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
            background-color: #ffffff;
            padding-bottom: 70px; /* Space for bottom nav */
        }
        .header {
            position: relative;
            height: 250px;
            background-color: #ddd;
        }
        .header-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .back-button {
            position: absolute;
            top: 15px;
            left: 15px;
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #333;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .bookmark-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .bookmark-btn i {
            color: #0074E4;
            font-size: 18px;
        }
        .kost-detail {
            padding: 20px;
        }
        .kost-name {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
            margin-left: 10px;
        }
        .status-tersedia {
            background-color: #4CAF50;
            color: white;
        }
        .status-penuh {
            background-color: #F44336;
            color: white;
        }
        .kost-address {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }
        .kost-address i {
            margin-right: 5px;
            margin-top: 3px;
        }
        .kost-price {
            font-size: 20px;
            font-weight: bold;
            color: #0074E4;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        .price-period {
            font-size: 14px;
            font-weight: normal;
            color: #666;
            margin-left: 5px;
        }
        .detail-section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .facility-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        .facility-item {
            background-color: #f1f8ff;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
            color: #333;
            display: flex;
            align-items: center;
        }
        .facility-item i {
            margin-right: 5px;
            color: #0074E4;
        }
        .availability-info {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            font-size: 14px;
            color: #333;
        }
        .availability-info i {
            margin-right: 5px;
            color: #0074E4;
        }
        .divider {
            height: 10px;
            background-color: #f1f1f1;
            margin: 20px -20px;
        }
        .mitra-info {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .mitra-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
        .mitra-name {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        .mitra-badge {
            font-size: 12px;
            color: #666;
        }
        .contact-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .contact-btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
        }
        .whatsapp-btn {
            background-color: #25D366;
            color: white;
        }
        .call-btn {
            background-color: #0074E4;
            color: white;
        }
        .email-btn {
            background-color: #EA4335;
            color: white;
        }
        .chat-btn {
            background-color: #6c5ce7;
            color: white;
            margin-top: 10px;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .chat-btn i {
            margin-right: 8px;
        }
        .contact-btn i {
            margin-right: 5px;
        }
        .map-container {
            margin-bottom: 20px;
            border-radius: 10px;
            overflow: hidden;
            height: 200px;
            background-color: #eee;
        }
        .map-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        .review-section {
            margin-bottom: 20px;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .rating-info {
            display: flex;
            align-items: center;
        }
        .avg-rating {
            font-size: 18px;
            font-weight: bold;
            margin-right: 10px;
        }
        .stars {
            color: #FFC107;
        }
        .review-count {
            color: #666;
            font-size: 14px;
        }
        .write-review-btn {
            background-color: transparent;
            border: 1px solid #0074E4;
            color: #0074E4;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
        }
        .review-item {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .review-user {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        .review-user-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }
        .review-user-name {
            font-weight: bold;
            font-size: 14px;
        }
        .review-date {
            font-size: 12px;
            color: #666;
        }
        .review-rating {
            margin-bottom: 5px;
        }
        .review-text {
            font-size: 14px;
            color: #333;
            line-height: 1.4;
        }
        .show-more-reviews {
            text-align: center;
            margin-top: 15px;
        }
        .show-more-btn {
            background-color: transparent;
            border: 1px solid #0074E4;
            color: #0074E4;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
        }
        .bottom-nav {
            position: fixed;
            bottom: 0;
            width: 100%;
            max-width: 480px;
            height: 60px;
            background-color: #ffffff;
            display: flex;
            justify-content: space-around;
            align-items: center;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }
        .nav-item {
            color: #666;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 12px;
        }
        .nav-item.active {
            color: #0074E4;
        }
        .nav-item i {
            font-size: 20px;
            margin-bottom: 3px;
        }
        .cta-button {
            position: fixed;
            bottom: 70px;
            width: calc(100% - 40px);
            max-width: 440px;
            padding: 15px;
            background-color: #0074E4;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cta-button i {
            margin-right: 10px;
        }
        .cta-button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .tooltip {
            position: relative;
            display: inline-block;
            margin-left: 5px;
        }
        .tooltip .tooltiptext {
            visibility: hidden;
            width: 120px;
            background-color: #555;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -60px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
        }
        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
        .gallery-container {
            display: flex;
            overflow-x: auto;
            gap: 10px;
            padding: 10px 0;
            margin-bottom: 15px;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none; /* Firefox */
        }
        .gallery-container::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }
        .gallery-item {
            flex: 0 0 auto;
            width: 200px;
            height: 150px;
            border-radius: 8px;
            overflow: hidden;
        }
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header with Kost Image -->
        <div class="header">
            <?php if(!empty($kost['foto_kost']) && file_exists($kost['foto_kost'])): ?>
                <img src="<?php echo $kost['foto_kost']; ?>" class="header-image" alt="<?php echo $kost['nama_kost']; ?>">
            <?php else: ?>
                <img src="images/default_kost.jpg" class="header-image" alt="Default Kost Image">
            <?php endif; ?>
            <a href="dashboard_mhs.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
            </a>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="bookmark_action" value="<?php echo $is_bookmarked ? 'remove' : 'add'; ?>">
                <button type="submit" class="bookmark-btn">
                    <i class="<?php echo $is_bookmarked ? 'fas' : 'far'; ?> fa-bookmark"></i>
                </button>
            </form>
        </div>
        
        <!-- Kost Details -->
        <div class="kost-detail">
            <h1 class="kost-name">
                <?php echo $kost['nama_kost']; ?>
                <?php if($kost['status_kost'] == 'penuh'): ?>
                    <span class="status-badge status-penuh">Penuh</span>
                <?php else: ?>
                    <span class="status-badge status-tersedia">Tersedia</span>
                <?php endif; ?>
            </h1>
            
            <div class="kost-address">
                <i class="fas fa-map-marker-alt"></i>
                <span><?php echo $kost['alamat']; ?></span>
            </div>
            
            <div class="kost-price">
                Rp <?php echo number_format($kost['harga_sewa'], 0, ',', '.'); ?>
                <span class="price-period">/ bulan</span>
            </div>
            
            <!-- Photo Gallery -->
            <?php if(!empty($kost['foto_kost']) && file_exists($kost['foto_kost'])): ?>
            <div class="detail-section">
                <h2 class="section-title">Galeri Foto</h2>
                <div class="gallery-container">
                    <div class="gallery-item">
                        <img src="<?php echo $kost['foto_kost']; ?>" alt="<?php echo $kost['nama_kost']; ?> 1">
                    </div>
                    <div class="gallery-item">
                        <img src="<?php echo $kost['foto_kost']; ?>" alt="<?php echo $kost['nama_kost']; ?> 2">
                    </div>
                    <div class="gallery-item">
                        <img src="<?php echo $kost['foto_kost']; ?>" alt="<?php echo $kost['nama_kost']; ?> 3">
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Facilities -->
            <div class="detail-section">
                <h2 class="section-title">Fasilitas</h2>
                <div class="facility-list">
                    <?php 
                    if(!empty($kost['fasilitas'])):
                        $fasilitas = explode(',', $kost['fasilitas']);
                        foreach($fasilitas as $fas): 
                            $fas = trim($fas);
                            $icon = 'fa-check';
                            
                            // Determine appropriate icon based on facility keyword
                            if(stripos($fas, 'wifi') !== false || stripos($fas, 'internet') !== false) {
                                $icon = 'fa-wifi';
                            } elseif(stripos($fas, 'ac') !== false || stripos($fas, 'air') !== false) {
                                $icon = 'fa-snowflake';
                            } elseif(stripos($fas, 'kamar mandi') !== false) {
                                $icon = 'fa-shower';
                            } elseif(stripos($fas, 'dapur') !== false) {
                                $icon = 'fa-utensils';
                            } elseif(stripos($fas, 'tv') !== false || stripos($fas, 'televisi') !== false) {
                                $icon = 'fa-tv';
                            } elseif(stripos($fas, 'parkir') !== false) {
                                $icon = 'fa-car';
                            } elseif(stripos($fas, 'kulkas') !== false || stripos($fas, 'lemari es') !== false) {
                                $icon = 'fa-refrigerator';
                            } elseif(stripos($fas, 'meja') !== false) {
                                $icon = 'fa-table';
                            } elseif(stripos($fas, 'kursi') !== false) {
                                $icon = 'fa-chair';
                            } elseif(stripos($fas, 'lemari') !== false) {
                                $icon = 'fa-cabinet-filing';
                            } elseif(stripos($fas, 'keamanan') !== false || stripos($fas, 'cctv') !== false) {
                                $icon = 'fa-shield-alt';
                            }
                    ?>
                        <div class="facility-item">
                            <i class="fas <?php echo $icon; ?>"></i>
                            <?php echo $fas; ?>
                        </div>
                    <?php 
                        endforeach;
                    else:
                    ?>
                        <div class="facility-item">
                            <i class="fas fa-info-circle"></i>
                            Tidak ada informasi fasilitas
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Availability -->
            <div class="detail-section">
                <h2 class="section-title">Ketersediaan</h2>
                <div class="availability-info">
                    <i class="fas fa-door-open"></i>
                    <?php echo $kost['ketersediaan_kamar']; ?> kamar tersedia
                    <div class="tooltip">
                        <i class="fas fa-info-circle"></i>
                        <span class="tooltiptext">Terakhir diperbarui pada <?php echo date('d M Y', strtotime($kost['tanggal_penambahan'])); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="divider"></div>
            
            <!-- Mitra (Owner) Information -->
            <div class="detail-section">
                <h2 class="section-title">Informasi Pemilik</h2>
                <div class="mitra-info">
                    <img src="images/default_user.jpg" class="mitra-photo" alt="Foto Mitra">
                    <div>
                        <div class="mitra-name"><?php echo $kost['nama_mitra']; ?></div>
                        <div class="mitra-badge">Pemilik Kost</div>
                    </div>
                </div>
                
                <div class="contact-buttons">
                    <a href="https://wa.me/<?php echo str_replace(['+', '-', ' '], '', $kost['telepon_mitra']); ?>" class="contact-btn whatsapp-btn" target="_blank">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                    <a href="tel:<?php echo $kost['telepon_mitra']; ?>" class="contact-btn call-btn">
                        <i class="fas fa-phone"></i> Telepon
                    </a>
                    <a href="mailto:<?php echo $kost['email_mitra']; ?>" class="contact-btn email-btn">
                        <i class="fas fa-envelope"></i> Email
                    </a>
                </div>
                
                <!-- Chat Button -->
                <form method="POST">
                    <input type="hidden" name="start_chat" value="1">
                    <button type="submit" class="chat-btn">
                        <i class="fas fa-comments"></i> Chat dengan Pemilik
                    </button>
                </form>
            </div>
            
            <!-- Location -->
            <div class="detail-section">
                <h2 class="section-title">Lokasi</h2>
                <div class="map-container">
                    <?php if(!empty($kost['koordinat_lokasi'])): 
                        $koordinat = explode(',', $kost['koordinat_lokasi']);
                        $latitude = trim($koordinat[0]);
                        $longitude = isset($koordinat[1]) ? trim($koordinat[1]) : '';
                        
                        if(!empty($latitude) && !empty($longitude)):
                    ?>
                        <iframe 
                            src="https://maps.google.com/maps?q=<?php echo $latitude; ?>,<?php echo $longitude; ?>&z=15&output=embed" 
                            allowfullscreen>
                        </iframe>
                    <?php else: ?>
                        <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #666;">
                            <i class="fas fa-map-marker-alt" style="margin-right: 10px;"></i>
                            Lokasi tidak tersedia
                        </div>
                    <?php endif; else: ?>
                        <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #666;">
                            <i class="fas fa-map-marker-alt" style="margin-right: 10px;"></i>
                            Lokasi tidak tersedia
                        </div>
                    <?php endif; ?>
                </div>
                <div style="font-size: 14px; color: #666; margin-top: 5px;">
                    <i class="fas fa-map-marker-alt"></i> <?php echo $kost['alamat']; ?>
                </div>
            </div>
            
            <!-- Bottom Navigation -->
            <div class="bottom-nav">
                <a href="dashboard_mhs.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>Beranda</span>
                </a>
                <a href="search.php" class="nav-item">
                    <i class="fas fa-search"></i>
                    <span>Cari</span>
                </a>
                <a href="chat_list.php" class="nav-item">
                    <i class="fas fa-comments"></i>
                    <span>Chat</span>
                </a>
                <a href="profile_mhs.php" class="nav-item">
                    <i class="fas fa-user"></i>
                    <span>Profil</span>
                </a>
            </div>
        </div>
    </div>
</body>
</html>