<?php
// Start session and include configuration
session_start();
include 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login_form.php");
    exit;
}

// Ambil data user
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Ambil list chat rooms sesuai role
if ($user_role == 'mahasiswa') {
    // Jika mahasiswa, ambil semua chat dengan mitra
    $query_chat_rooms = "SELECT cr.*, 
                               u.nama_lengkap as nama_mitra, 
                               u.foto_profil as foto_mitra,
                               k.nama_kost
                        FROM chat_rooms cr
                        JOIN users u ON cr.id_mitra = u.id_user
                        LEFT JOIN kost k ON cr.id_kost = k.id_kost
                        WHERE cr.id_mahasiswa = $user_id
                        ORDER BY cr.last_update DESC";
} else if ($user_role == 'mitra') {
    // Jika mitra, ambil semua chat dengan mahasiswa
    $query_chat_rooms = "SELECT cr.*, 
                               u.nama_lengkap as nama_mahasiswa, 
                               u.foto_profil as foto_mahasiswa,
                               k.nama_kost
                        FROM chat_rooms cr
                        JOIN users u ON cr.id_mahasiswa = u.id_user
                        LEFT JOIN kost k ON cr.id_kost = k.id_kost
                        WHERE cr.id_mitra = $user_id
                        ORDER BY cr.last_update DESC";
}

$result_chat_rooms = mysqli_query($conn, $query_chat_rooms);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - WeKost</title>
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
        .top-bar {
            background-color: #0074E4;
            padding: 15px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .top-bar h1 {
            font-size: 18px;
            font-weight: bold;
        }
        .back-button {
            color: white;
            text-decoration: none;
            font-size: 20px;
        }
        .chat-list {
            padding: 10px;
            margin-bottom: 70px;
        }
        .chat-item {
            display: flex;
            padding: 15px;
            border-bottom: 1px solid #f1f1f1;
            text-decoration: none;
            color: #333;
        }
        .chat-item:hover {
            background-color: #f9f9f9;
        }
        .chat-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 15px;
            position: relative;
        }
        .chat-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .chat-avatar.online:after {
            content: '';
            position: absolute;
            width: 12px;
            height: 12px;
            background-color: #4CAF50;
            border-radius: 50%;
            bottom: 2px;
            right: 2px;
            border: 2px solid white;
        }
        .chat-info {
            flex: 1;
        }
        .chat-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .chat-name {
            font-weight: bold;
            font-size: 16px;
        }
        .chat-time {
            color: #999;
            font-size: 12px;
        }
        .chat-last-message {
            color: #666;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 280px;
        }
        .chat-kost {
            font-size: 12px;
            color: #0074E4;
            margin-top: 5px;
        }
        .unread-badge {
            width: 20px;
            height: 20px;
            background-color: #0074E4;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            margin-left: 10px;
        }
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 50px 20px;
            text-align: center;
            color: #666;
        }
        .empty-state i {
            font-size: 60px;
            color: #d1e6fa;
            margin-bottom: 20px;
        }
        .empty-state p {
            margin-top: 10px;
            font-size: 14px;
            color: #999;
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
    </style>
</head>
<body>
    <div class="container">
        <!-- Top Bar -->
        <div class="top-bar">
            <a href="<?php echo $user_role == 'mahasiswa' ? 'dashboard_mhs.php' : 'dashboard_mitra.php'; ?>" class="back-button">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1>Chat</h1>
            <div style="width: 20px;"></div> <!-- Placeholder for balance -->
        </div>
        
        <!-- Chat List -->
        <div class="chat-list">
            <?php if(mysqli_num_rows($result_chat_rooms) > 0): ?>
                <?php while($room = mysqli_fetch_assoc($result_chat_rooms)): ?>
                    <?php 
                    // Set variables based on user role
                    if($user_role == 'mahasiswa') {
                        $chat_person_name = $room['nama_mitra'];
                        $chat_person_img = !empty($room['foto_mitra']) ? $room['foto_mitra'] : 'images/default_profile.jpg';
                        $other_person_id = $room['id_mitra'];
                    } else {
                        $chat_person_name = $room['nama_mahasiswa'];
                        $chat_person_img = !empty($room['foto_mahasiswa']) ? $room['foto_mahasiswa'] : 'images/default_profile.jpg';
                        $other_person_id = $room['id_mahasiswa'];
                    }
                    
                    // Format waktu chat lebih user-friendly
                    $last_update = strtotime($room['last_update']);
                    $now = time();
                    $diff = $now - $last_update;
                    
                    if($diff < 60) {
                        $time_display = "Baru saja";
                    } elseif($diff < 3600) {
                        $time_display = floor($diff/60) . " menit yang lalu";
                    } elseif($diff < 86400) {
                        $time_display = floor($diff/3600) . " jam yang lalu";
                    } elseif($diff < 604800) {
                        $time_display = floor($diff/86400) . " hari yang lalu";
                    } else {
                        $time_display = date("d/m/Y", $last_update);
                    }
                    ?>
                    <a href="chat_detail.php?room=<?php echo $room['id_room']; ?>&user=<?php echo $other_person_id; ?><?php echo !empty($room['id_kost']) ? '&kost='.$room['id_kost'] : ''; ?>" class="chat-item">
                        <div class="chat-avatar <?php echo rand(0, 1) ? 'online' : ''; ?>">
                            <img src="<?php echo $chat_person_img; ?>" alt="<?php echo $chat_person_name; ?>">
                        </div>
                        <div class="chat-info">
                            <div class="chat-header">
                                <div class="chat-name"><?php echo $chat_person_name; ?></div>
                                <div class="chat-time"><?php echo $time_display; ?></div>
                            </div>
                            <div class="chat-last-message">
                                <?php echo !empty($room['last_message']) ? $room['last_message'] : 'Belum ada pesan'; ?>
                            </div>
                            <?php if(!empty($room['nama_kost'])): ?>
                                <div class="chat-kost">
                                    <i class="fas fa-home"></i> <?php echo $room['nama_kost']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if($room['unread_count'] > 0): ?>
                            <div class="unread-badge"><?php echo $room['unread_count']; ?></div>
                        <?php endif; ?>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <h3>Belum Ada Chat</h3>
                    <p>Mulai chat dengan mitra kost untuk mendapatkan informasi lebih lanjut.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Bottom Navigation -->
        <div class="bottom-nav">
            <a href="<?php echo $user_role == 'mahasiswa' ? 'dashboard_mhs.php' : 'dashboard_mitra.php'; ?>" class="nav-item">
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
            <a href="<?php echo $user_role == 'mahasiswa' ? 'profile_mhs.php' : 'profile_mitra.php'; ?>" class="nav-item">
                <i class="fas fa-user"></i>
                <span>Profil</span>
            </a>
        </div>
    </div>
</body>
</html>