<?php
// Start session and include configuration
session_start();
include 'config.php';

// Cek apakah user sudah login sebagai mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'mahasiswa') {
    header("Location: login_form.php");
    exit;
}

// Ambil data mahasiswa
$id_mhs = $_SESSION['user_id'];
$query_mhs = "SELECT * FROM users WHERE id_user = '$id_mhs'";
$result_mhs = mysqli_query($conn, $query_mhs);
$mahasiswa = mysqli_fetch_assoc($result_mhs);

// Set filter default
$where_clause = "status_verifikasi = 'diterima'"; // Hanya tampilkan kost yang terverifikasi
$sort_by = "tanggal_penambahan DESC"; // Default sort by terbaru

// Proses filter jika ada
$min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 10000000; // Default max price yang tinggi

if(isset($_GET['filter'])) {
    $filter = $_GET['filter'];
    
    switch($filter) {
        case 'harga_rendah':
            $sort_by = "harga_sewa ASC";
            break;
        case 'harga_tinggi':
            $sort_by = "harga_sewa DESC";
            break;
        case 'tersedia':
            $where_clause .= " AND status_kost = 'tersedia'";
            break;
        case 'penuh':
            $where_clause .= " AND status_kost = 'penuh'";
            break;
        case 'terbaru':
            $sort_by = "tanggal_penambahan DESC";
            break;
        case 'terlama':
            $sort_by = "tanggal_penambahan ASC";
            break;
    }
}

// Filter berdasarkan range harga
$where_clause .= " AND harga_sewa >= $min_price AND harga_sewa <= $max_price";

// Ambil semua data kost dari berbagai mitra yang sudah terverifikasi
$query_kost = "SELECT k.*, u.nama_lengkap as nama_mitra 
               FROM kost k 
               JOIN users u ON k.id_mitra = u.id_user 
               WHERE $where_clause 
               ORDER BY $sort_by";
$result_kost = mysqli_query($conn, $query_kost);

// Tambahan: Dapatkan range harga terendah dan tertinggi untuk filter
$query_price_range = "SELECT MIN(harga_sewa) as min_price, MAX(harga_sewa) as max_price FROM kost WHERE status_verifikasi = 'diterima'";
$result_price_range = mysqli_query($conn, $query_price_range);
$price_range = mysqli_fetch_assoc($result_price_range);
$db_min_price = $price_range['min_price'];
$db_max_price = $price_range['max_price'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa - WeKost</title>
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
        .app-heading {
            padding: 15px;
            background-color: #0074E4;
            color: white;
            font-size: 18px;
        }
        .filter-container {
            margin: 10px;
            position: relative;
            display: inline-block;
        }
        .filter-button {
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
        .filter-dropdown {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 250px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 8px;
            margin-top: 5px;
            left: 0;
        }
        .filter-dropdown a {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-size: 14px;
            border-bottom: 1px solid #f1f1f1;
        }
        .filter-dropdown a:last-child {
            border-bottom: none;
        }
        .filter-dropdown a:hover {
            background-color: #f1f8ff;
        }
        .filter-dropdown.show {
            display: block;
        }
        .filter-header {
            font-weight: bold;
            color: #666;
            padding: 12px 16px;
            border-bottom: 1px solid #f1f1f1;
            font-size: 12px;
        }
        .price-range {
            padding: 0 16px 16px;
            border-bottom: 1px solid #f1f1f1;
        }
        .price-range label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #333;
        }
        .price-range input {
            width: 100%;
            margin-bottom: 12px;
        }
        .price-inputs {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-top: 5px;
        }
        .price-inputs input {
            width: 45%;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .apply-filter {
            background-color: #0074E4;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }
        .kost-list {
            padding: 10px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            margin-bottom: 70px; /* Space for bottom nav */
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
            position: relative;
        }
        .kost-info {
            padding: 10px;
        }
        .kost-name {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .kost-mitra {
            font-size: 12px;
            color: #666;
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
            margin-bottom: 5px;
        }
        .kost-facilities {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        .facility-tag {
            background-color: #f1f8ff;
            padding: 3px 8px;
            border-radius: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
        }
        .status-tersedia {
            background-color: #4CAF50;
            color: white;
        }
        .status-penuh {
            background-color: #F44336;
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
        .active-filter {
            margin-left: 10px;
            font-size: 12px;
            color: #666;
            font-style: italic;
        }
        .bookmark-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(255, 255, 255, 0.7);
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .bookmark-btn i {
            color: #0074E4;
            font-size: 18px;
        }
        .bookmark-btn:hover {
            background-color: rgba(255, 255, 255, 0.9);
        }
        .kost-image-container {
            position: relative;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Search Bar -->
        <div class="search-bar">
            <div class="search-box">
                <input type="text" placeholder="Cari nama kost atau lokasi">
                <button><i class="fas fa-search"></i></button>
            </div>
            <a class="chat-icon" href="chat_list.php"><i class="fas fa-comments"></i></a>
        </div>
        
        <!-- App Heading -->
        <div class="app-heading">
            Temukan Kost Nyaman di WeKost!
        </div>
        
        <!-- Filter Dropdown -->
        <div class="filter-container">
            <button class="filter-button" onclick="toggleFilter()">
                <i class="fas fa-filter"></i>&nbsp; Filter
                <?php if(isset($_GET['filter']) || isset($_GET['min_price']) || isset($_GET['max_price'])): ?>
                <span class="active-filter">
                    <?php 
                    if(isset($_GET['filter'])) {
                        switch($_GET['filter']) {
                            case 'harga_rendah': echo '(Harga Terendah)'; break;
                            case 'harga_tinggi': echo '(Harga Tertinggi)'; break;
                            case 'tersedia': echo '(Tersedia)'; break;
                            case 'penuh': echo '(Penuh)'; break;
                            case 'terbaru': echo '(Terbaru)'; break;
                            case 'terlama': echo '(Terlama)'; break;
                        }
                    } elseif(isset($_GET['min_price']) || isset($_GET['max_price'])) {
                        echo '(Kisaran Harga)';
                    }
                    ?>
                </span>
                <?php endif; ?>
            </button>
            <div id="filterDropdown" class="filter-dropdown">
                <div class="filter-header">HARGA</div>
                <div class="price-range">
                    <form id="priceFilterForm">
                        <label>Kisaran Harga (Rp):</label>
                        <div class="price-inputs">
                            <input type="number" id="min_price" name="min_price" placeholder="Min" value="<?php echo $min_price > 0 ? $min_price : ''; ?>">
                            <input type="number" id="max_price" name="max_price" placeholder="Max" value="<?php echo $max_price < 10000000 ? $max_price : ''; ?>">
                        </div>
                        <button type="submit" class="apply-filter">Terapkan Filter</button>
                    </form>
                </div>
                
                <div class="filter-header">URUTKAN BERDASARKAN</div>
                <a href="?filter=terbaru<?php echo isset($_GET['min_price']) ? '&min_price='.$_GET['min_price'] : ''; echo isset($_GET['max_price']) ? '&max_price='.$_GET['max_price'] : ''; ?>">Terbaru</a>
                <a href="?filter=terlama<?php echo isset($_GET['min_price']) ? '&min_price='.$_GET['min_price'] : ''; echo isset($_GET['max_price']) ? '&max_price='.$_GET['max_price'] : ''; ?>">Terlama</a>
                <a href="?filter=harga_rendah<?php echo isset($_GET['min_price']) ? '&min_price='.$_GET['min_price'] : ''; echo isset($_GET['max_price']) ? '&max_price='.$_GET['max_price'] : ''; ?>">Harga Terendah</a>
                <a href="?filter=harga_tinggi<?php echo isset($_GET['min_price']) ? '&min_price='.$_GET['min_price'] : ''; echo isset($_GET['max_price']) ? '&max_price='.$_GET['max_price'] : ''; ?>">Harga Tertinggi</a>
                
                <div class="filter-header">STATUS KOST</div>
                <a href="?filter=tersedia<?php echo isset($_GET['min_price']) ? '&min_price='.$_GET['min_price'] : ''; echo isset($_GET['max_price']) ? '&max_price='.$_GET['max_price'] : ''; ?>">Tersedia</a>
                <a href="?filter=penuh<?php echo isset($_GET['min_price']) ? '&min_price='.$_GET['min_price'] : ''; echo isset($_GET['max_price']) ? '&max_price='.$_GET['max_price'] : ''; ?>">Penuh</a>
                
                <div class="filter-header">RESET FILTER</div>
                <a href="dashboard_mhs.php">Hapus Semua Filter</a>
            </div>
        </div>
        
        <!-- Kost List - Menampilkan semua kost yang sudah diverifikasi dari semua mitra -->
        <div class="kost-list">
            <?php if(mysqli_num_rows($result_kost) > 0): ?>
                <?php while($kost = mysqli_fetch_assoc($result_kost)): ?>
                    <div class="kost-card">
                        <div class="kost-image-container">
                            <?php if(!empty($kost['foto_kost']) && file_exists($kost['foto_kost'])): ?>
                                <img src="<?php echo $kost['foto_kost']; ?>" class="kost-image" alt="<?php echo $kost['nama_kost']; ?>">
                            <?php else: ?>
                                <img src="images/default_kost.jpg" class="kost-image" alt="Default Kost Image">
                            <?php endif; ?>
                            <button class="bookmark-btn" onclick="toggleBookmark(this, <?php echo $kost['id_kost']; ?>)">
                                <i class="far fa-bookmark"></i>
                            </button>
                        </div>
                        <div class="kost-info">
                            <div class="kost-name">
                                <?php echo $kost['nama_kost']; ?>
                                <?php if($kost['status_kost'] == 'penuh'): ?>
                                    <span class="status-badge status-penuh">Penuh</span>
                                <?php else: ?>
                                    <span class="status-badge status-tersedia">Tersedia</span>
                                <?php endif; ?>
                            </div>
                            <div class="kost-mitra">
                                <i class="fas fa-user"></i> <?php echo $kost['nama_mitra']; ?>
                            </div>
                            <div class="kost-address">
                                <i class="fas fa-map-marker-alt"></i> <?php echo $kost['alamat']; ?>
                            </div>
                            <div class="kost-price">
                                <i class="fas fa-tags"></i> Rp <?php echo number_format($kost['harga_sewa'], 0, ',', '.'); ?> / bulan
                            </div>
                            <?php if(!empty($kost['fasilitas'])): ?>
                            <div class="kost-facilities">
                                <?php 
                                $fasilitas = explode(',', $kost['fasilitas']);
                                $counter = 0;
                                foreach($fasilitas as $fas): 
                                    if($counter < 3): // Batasi hanya menampilkan 3 fasilitas
                                ?>
                                    <span class="facility-tag"><?php echo trim($fas); ?></span>
                                <?php 
                                    endif;
                                    $counter++;
                                endforeach; 
                                
                                if(count($fasilitas) > 3):
                                ?>
                                    <span class="facility-tag">+<?php echo count($fasilitas) - 3; ?> lainnya</span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <div style="text-align: right; margin-top: 10px;">
                                <a href="detail_kost_mhs.php?id=<?php echo $kost['id_kost']; ?>" style="text-decoration: none; color: #0074E4; font-weight: bold; font-size: 14px;">
                                    Lihat Detail <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>Tidak ada kost ditemukan</h3>
                    <p>Tidak ada kost yang sesuai dengan filter yang Anda pilih. Coba ubah filter pencarian Anda.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Bottom Navigation -->
        <div class="bottom-nav">
            <a href="dashboard_mhs.php" class="nav-item active">
                <i class="fas fa-home"></i>
                <span>Beranda</span>
            </a>
            <a href="search.php" class="nav-item">
                <i class="fas fa-search"></i>
                <span>Cari</span>
            </a>
            <a href="bookmarks.php" class="nav-item">
                <i class="fas fa-bookmark"></i>
                <span>Tersimpan</span>
            </a>
            <a href="profile_mhs.php" class="nav-item">
                <i class="fas fa-user"></i>
                <span>Profil</span>
            </a>
        </div>
    </div>

    <script>
        function toggleFilter() {
            document.getElementById("filterDropdown").classList.toggle("show");
        }

        // Close the dropdown if the user clicks outside of it
        window.onclick = function(event) {
            if (!event.target.matches('.filter-button') && !event.target.matches('.filter-button *')) {
                var dropdowns = document.getElementsByClassName("filter-dropdown");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
        
        // Function untuk toggle bookmark (simpan kost)
        function toggleBookmark(button, id_kost) {
            // Ganti icon
            const icon = button.querySelector('i');
            if (icon.classList.contains('far')) {
                icon.classList.remove('far');
                icon.classList.add('fas');
                
                // Simpan data ke backend (contoh fetch API)
                // fetch('save_bookmark.php', {
                //     method: 'POST',
                //     headers: {
                //         'Content-Type': 'application/x-www-form-urlencoded',
                //     },
                //     body: 'id_kost=' + id_kost + '&action=add'
                // });
                
                // Alert sementara (nanti bisa diganti dengan notifikasi yang lebih halus)
                alert('Kost berhasil disimpan!');
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
                
                // Hapus data dari backend (contoh fetch API)
                // fetch('save_bookmark.php', {
                //     method: 'POST',
                //     headers: {
                //         'Content-Type': 'application/x-www-form-urlencoded',
                //     },
                //     body: 'id_kost=' + id_kost + '&action=remove'
                // });
                
                // Alert sementara (nanti bisa diganti dengan notifikasi yang lebih halus)
                alert('Kost dihapus dari tersimpan!');
            }
        }
    </script>
</body>
</html>