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

// Default filter status
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'semua';

// Query berdasarkan filter
if ($status_filter === 'semua') {
    $query_kost = "SELECT * FROM kost WHERE id_mitra = '$id_mitra' ORDER BY tanggal_penambahan DESC";
} else {
    $query_kost = "SELECT * FROM kost WHERE id_mitra = '$id_mitra' AND status_verifikasi = '$status_filter' ORDER BY tanggal_penambahan DESC";
}

$result_kost = mysqli_query($conn, $query_kost);

// Hitung jumlah masing-masing status
$query_count = "SELECT status_verifikasi, COUNT(*) as count FROM kost WHERE id_mitra = '$id_mitra' GROUP BY status_verifikasi";
$result_count = mysqli_query($conn, $query_count);

$status_counts = [
    'menunggu' => 0,
    'diterima' => 0,
    'ditolak' => 0
];

while ($row = mysqli_fetch_assoc($result_count)) {
    $status_counts[$row['status_verifikasi']] = $row['count'];
}

$total_kost = array_sum($status_counts);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kost Saya - WeKost</title>
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
            padding-bottom: 70px;
        }
        .header {
            background-color: #0074E4;
            color: white;
            padding: 15px;
            text-align: center;
            position: relative;
        }
        .header h1 {
            font-size: 18px;
        }
        .back-button {
            position: absolute;
            left: 15px;
            top: 15px;
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
        }
        .status-tabs {
            display: flex;
            background-color: #f0f0f0;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none; /* Firefox */
        }
        .status-tabs::-webkit-scrollbar {
            display: none; /* Chrome, Safari */
        }
        .status-tab {
            padding: 12px 15px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            white-space: nowrap;
            color: #666;
            border-bottom: 3px solid transparent;
        }
        .status-tab.active {
            color: #0074E4;
            border-bottom-color: #0074E4;
            font-weight: bold;
        }
        .count-badge {
            background-color: #e0e0e0;
            color: #666;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 12px;
            margin-left: 5px;
        }
        .kost-list {
            padding: 15px;
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
            padding: 15px;
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
            margin-bottom: 10px;
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
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .action-button {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .edit-button {
            background-color: #FFC107;
            color: #000;
        }
        .details-button {
            background-color: #0074E4;
            color: white;
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
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <a href="dashboard_mitra.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1>Kost Saya</h1>
        </div>
        
        <!-- Status Filter Tabs -->
        <div class="status-tabs">
            <a href="kost_saya.php?status=semua" class="status-tab <?php echo $status_filter === 'semua' ? 'active' : ''; ?>">
                Semua <span class="count-badge"><?php echo $total_kost; ?></span>
            </a>
            <a href="kost_saya.php?status=menunggu" class="status-tab <?php echo $status_filter === 'menunggu' ? 'active' : ''; ?>">
                Menunggu <span class="count-badge"><?php echo $status_counts['menunggu']; ?></span>
            </a>
            <a href="kost_saya.php?status=diterima" class="status-tab <?php echo $status_filter === 'diterima' ? 'active' : ''; ?>">
                Terverifikasi <span class="count-badge"><?php echo $status_counts['diterima']; ?></span>
            </a>
            <a href="kost_saya.php?status=ditolak" class="status-tab <?php echo $status_filter === 'ditolak' ? 'active' : ''; ?>">
                Ditolak <span class="count-badge"><?php echo $status_counts['ditolak']; ?></span>
            </a>
        </div>
        
        <!-- Kost List -->
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
                                <span class="status-badge status-<?php echo $kost['status_verifikasi']; ?>">
                                    <?php echo ucfirst($kost['status_verifikasi']); ?>
                                </span>
                            </div>
                            <div class="kost-address"><?php echo $kost['alamat']; ?></div>
                            <div class="kost-price">Rp <?php echo number_format($kost['harga_sewa'], 0, ',', '.'); ?> / bulan</div>
                            
                            <div class="action-buttons">
                                <a href="edit_kost.php?id=<?php echo $kost['id_kost']; ?>" class="action-button edit-button">
                                    <i class="fas fa-edit"></i>&nbsp; Edit
                                </a>
                                <a href="detail_kost.php?id=<?php echo $kost['id_kost']; ?>" class="action-button details-button">
                                    <i class="fas fa-info-circle"></i>&nbsp; Detail
                                </a>
                            </div>
                            
                            <?php if($kost['status_verifikasi'] === 'ditolak'): ?>
                                <div style="margin-top: 10px; color: #F44336; font-size: 14px;">
                                    <i class="fas fa-exclamation-circle"></i> Alasan: <?php echo !empty($kost['alasan_penolakan']) ? $kost['alasan_penolakan'] : 'Tidak memenuhi syarat'; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-home"></i>
                    <h3>Belum ada kost</h3>
                    <?php if($status_filter !== 'semua'): ?>
                        <p>Tidak ada kost dengan status <?php echo $status_filter; ?>.</p>
                    <?php else: ?>
                        <p>Anda belum menambahkan kost. Klik tombol + di beranda untuk menambahkan kost baru.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Bottom Navigation -->
        <div class="bottom-nav">
            <a href="dashboard_mitra.php" class="nav-item">
                <i class="fas fa-home"></i>
                <span>Beranda</span>
            </a>
            <a href="search.php" class="nav-item">
                <i class="fas fa-search"></i>
                <span>Cari</span>
            </a>
            <div class="nav-item-wrapper">
                <a href="kost_saya.php" class="nav-item active">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Kost Saya</span>
                </a>
                <?php if($status_counts['menunggu'] > 0): ?>
                <span class="notification-badge"><?php echo $status_counts['menunggu']; ?></span>
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