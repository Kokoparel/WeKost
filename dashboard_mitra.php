<?php
// Start session and include configuration
session_start();
include 'config.php';

// Cek apakah user sudah login sebagai mitra
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'mitra') {
    header("Location: login_form.php");
    exit;
}

// Ambil data mitra
$id_mitra = $_SESSION['user_id'];
$query_mitra = "SELECT * FROM users WHERE id_user = '$id_mitra'";
$result_mitra = mysqli_query($conn, $query_mitra);
$mitra = mysqli_fetch_assoc($result_mitra);

// Ambil hanya kost yang sudah diverifikasi oleh admin untuk ditampilkan di beranda
$query_kost = "SELECT * FROM kost WHERE id_mitra = '$id_mitra' AND status_verifikasi = 'diterima' ORDER BY tanggal_penambahan DESC";
$result_kost = mysqli_query($conn, $query_kost);

// Hitung jumlah kost yang menunggu verifikasi
$query_pending = "SELECT COUNT(*) as pending_count FROM kost WHERE id_mitra = '$id_mitra' AND status_verifikasi = 'menunggu'";
$result_pending = mysqli_query($conn, $query_pending);
$pending_data = mysqli_fetch_assoc($result_pending);
$pending_count = $pending_data['pending_count'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mitra</title>
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
        }
        .search-bar {
            background-color: #0074E4;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .search-box {
            display: flex;
            background-color: #ffffff;
            border-radius: 20px;
            padding: 8px 15px;
            width: 80%;
        }
        .search-box input {
            border: none;
            outline: none;
            width: 100%;
            font-size: 14px;
        }
        .search-box button {
            background: none;
            border: none;
            cursor: pointer;
            color: #0074E4;
        }
        .chat-icon {
            background-color: #ffffff;
            color: #0074E4;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            cursor: pointer;
            text-decoration: none;
        }
        .kost-heading {
            padding: 15px;
            background-color: #0074E4;
            color: white;
            font-size: 18px;
        }
        .filter-button {
            margin: 10px;
            padding: 8px 15px;
            background-color: #ffffff;
            color: #0074E4;
            border: 1px solid #0074E4;
            border-radius: 20px;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            width: fit-content;
        }
        .kost-list {
            padding: 10px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }
        .kost-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .kost-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        .kost-info {
            padding: 10px;
        }
        .kost-name {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
        }
        .kost-address {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        .kost-price {
            font-weight: bold;
            color: #0074E4;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
            margin-left: 5px;
        }
        .status-menunggu {
            background-color: #FFC107;
            color: #000;
        }
        .status-diterima {
            background-color: #4CAF50;
            color: white;
        }
        .status-ditolak {
            background-color: #F44336;
            color: white;
        }
        .add-kost {
            position: fixed;
            bottom: 80px;
            right: calc(50% - 30px);
            width: 60px;
            height: 60px;
            background-color: #0074E4;
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,116,228,0.3);
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
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            text-align: center;
            color: #666;
        }
        .empty-state i {
            font-size: 60px;
            color: #d1e6fa;
            margin-bottom: 20px;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ff4757;
            color: white;
            font-size: 10px;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .nav-item-wrapper {
            position: relative;
        }
        .info-banner {
            margin: 10px;
            padding: 10px 15px;
            background-color: #f1f8ff;
            border-left: 4px solid #0074E4;
            border-radius: 4px;
            font-size: 14px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Search Bar -->
        <div class="search-bar">
            <div class="search-box">
                <input type="text" placeholder="Cari nama kost atau kata kunci">
                <button><i class="fas fa-search"></i></button>
            </div>
                <a class="chat-icon" href="chat_list.php"><i class="fas fa-comments"></i></a>
        </div>
        
        <!-- Kost Heading -->
        <div class="kost-heading">
            Selamat datang di WeKost!
        </div>
        
        <?php if($pending_count > 0): ?>
        <!-- Info Banner untuk kost yang menunggu verifikasi -->
        <div class="info-banner">
            <i class="fas fa-info-circle"></i> Anda memiliki <?php echo $pending_count; ?> kost yang menunggu verifikasi. Lihat di halaman "Kost Saya".
        </div>
        <?php endif; ?>
        
        <!-- Filter Button -->
        <button class="filter-button">
            <i class="fas fa-filter"></i>&nbsp; Masukkan filter
        </button>
        
        <!-- Kost List - Hanya menampilkan yang sudah diverifikasi -->
        <div class="kost-list">
            <?php if(mysqli_num_rows($result_kost) > 0): ?>
                <?php while($kost = mysqli_fetch_assoc($result_kost)): ?>
                    <div class="kost-card">
                        <?php if(!empty($kost['foto_kost']) && file_exists($kost['foto_kost'])): ?>
                            <img src="<?php echo $kost['foto_kost']; ?>" class="kost-image" alt="<?php echo $kost['nama_kost']; ?>">
                        <?php else: ?>
                            <img src="images/default_kost.jpg" class="kost-image" alt="Default Kost Image">
                        <?php endif; ?>
                        <div class="kost-info">
                            <div class="kost-name">
                                <?php echo $kost['nama_kost']; ?>
                            </div>
                            <div class="kost-address"><?php echo $kost['alamat']; ?></div>
                            <div class="kost-price">Rp <?php echo number_format($kost['harga_sewa'], 0, ',', '.'); ?> / bulan</div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-home"></i>
                    <h3>Belum ada kost terverifikasi</h3>
                    <p>Anda belum memiliki kost yang terverifikasi. Klik tombol + untuk menambahkan kost baru.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Add Kost Button -->
        <a href="tambah_kost.php" class="add-kost">
            <i class="fas fa-plus"></i>
        </a>
        
        <!-- Bottom Navigation -->
        <div class="bottom-nav">
            <a href="dashboard_mitra.php" class="nav-item active">
                <i class="fas fa-home"></i>
                <span>Beranda</span>
            </a>
            <a href="search.php" class="nav-item">
                <i class="fas fa-search"></i>
                <span>Cari</span>
            </a>
            <div class="nav-item-wrapper">
                <a href="kost_saya.php" class="nav-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Kost Saya</span>
                </a>
                <?php if($pending_count > 0): ?>
                <span class="notification-badge"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </div>
            <a href="profile.php" class="nav-item">
                <i class="fas fa-user"></i>
                <span>Profil</span>
            </a>
        </div>
    </div>
</body>
</html>