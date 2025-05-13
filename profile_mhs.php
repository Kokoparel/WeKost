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
$id_user = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id_user = '$id_user' AND tipe_pengguna = 'mahasiswa'";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

// Update Profile
$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    
    // Verifikasi jika email sudah digunakan oleh pengguna lain
    $check_email = mysqli_query($conn, "SELECT id_user FROM users WHERE email = '$email' AND id_user != '$id_user'");
    if (mysqli_num_rows($check_email) > 0) {
        $message = "Email sudah digunakan. Gunakan email lain.";
        $message_type = "error";
    } else {
        // Upload foto profil jika ada
        $foto_profil = $user['foto_profil']; // Default ke foto yang sudah ada
        
        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] == 0) {
            $target_dir = "uploads/profile/";
            
            // Buat direktori jika belum ada
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
            $new_filename = "profile_" . $id_user . "_" . time() . "." . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            // Cek tipe file
            $allowed_types = array('jpg', 'jpeg', 'png');
            if (in_array(strtolower($file_extension), $allowed_types)) {
                if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $target_file)) {
                    // Hapus foto lama jika ada dan bukan foto default
                    if (!empty($user['foto_profil']) && file_exists($user['foto_profil']) && basename($user['foto_profil']) != 'default_profile.jpg') {
                        unlink($user['foto_profil']);
                    }
                    $foto_profil = $target_file;
                } else {
                    $message = "Gagal mengunggah foto profil.";
                    $message_type = "error";
                }
            } else {
                $message = "Hanya file JPG, JPEG, dan PNG yang diperbolehkan.";
                $message_type = "error";
            }
        }
        
        // Update password jika diisi
        $password_query = "";
        if (!empty($_POST['password_baru']) && !empty($_POST['password_lama'])) {
            // Verifikasi password lama
            if (password_verify($_POST['password_lama'], $user['password'])) {
                $password_baru = password_hash($_POST['password_baru'], PASSWORD_DEFAULT);
                $password_query = ", password = '$password_baru'";
            } else {
                $message = "Password lama tidak sesuai.";
                $message_type = "error";
            }
        }
        
        // Update data jika tidak ada error
        if (empty($message)) {
            $update_query = "UPDATE users SET 
                nama_lengkap = '$nama_lengkap', 
                email = '$email', 
                no_hp = '$no_hp', 
                foto_profil = '$foto_profil'
                $password_query
                WHERE id_user = '$id_user'";
                
            if (mysqli_query($conn, $update_query)) {
                $message = "Profil berhasil diperbarui!";
                $message_type = "success";
                
                // Refresh data user
                $result = mysqli_query($conn, $query);
                $user = mysqli_fetch_assoc($result);
            } else {
                $message = "Error: " . mysqli_error($conn);
                $message_type = "error";
            }
        }
    }
}

// // Ambil data pemesanan/booking kost
// $query_bookings = "SELECT b.*, k.nama_kost, k.foto_kost, k.alamat, k.harga_sewa, u.nama_lengkap as nama_mitra 
//                   FROM bookings b 
//                   JOIN kost k ON b.id_kost = k.id_kost 
//                   JOIN users u ON k.id_mitra = u.id_user 
//                   WHERE b.id_mahasiswa = '$id_user' 
//                   ORDER BY b.tanggal_booking DESC";
// $result_bookings = mysqli_query($conn, $query_bookings);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Mahasiswa - WeKost</title>
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
        .app-heading {
            padding: 15px;
            background-color: #0074E4;
            color: white;
            font-size: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .back-button {
            color: white;
            text-decoration: none;
            font-size: 18px;
        }
        .profile-header {
            background-color: #0074E4;
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }
        .profile-header-details {
            margin-top: 10px;
        }
        .profile-picture-container {
            width: 100px;
            height: 100px;
            margin: 0 auto;
            position: relative;
        }
        .profile-picture {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .change-photo {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: #0074E4;
            color: white;
            border: 2px solid white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
        }
        .tab-container {
            display: flex;
            border-bottom: 1px solid #ddd;
            background-color: white;
        }
        .tab {
            flex: 1;
            text-align: center;
            padding: 15px 0;
            cursor: pointer;
            color: #666;
            font-weight: bold;
            font-size: 14px;
        }
        .tab.active {
            color: #0074E4;
            border-bottom: 2px solid #0074E4;
        }
        .tab-content {
            display: none;
            padding: 20px;
        }
        .tab-content.active {
            display: block;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            font-size: 14px;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            width: 100%;
        }
        .btn-primary {
            background-color: #0074E4;
            color: white;
        }
        .alert {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .booking-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 15px;
        }
        .booking-header {
            padding: 10px 15px;
            background-color: #f1f8ff;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .booking-date {
            font-size: 12px;
            color: #666;
        }
        .booking-status {
            font-size: 12px;
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 10px;
        }
        .status-confirmed {
            background-color: #4CAF50;
            color: white;
        }
        .status-pending {
            background-color: #FFC107;
            color: white;
        }
        .status-canceled {
            background-color: #F44336;
            color: white;
        }
        .booking-body {
            padding: 15px;
            display: flex;
        }
        .booking-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 15px;
        }
        .booking-info {
            flex: 1;
        }
        .booking-kost-name {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
        }
        .booking-kost-address {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        .booking-price {
            font-weight: bold;
            color: #0074E4;
            margin-bottom: 5px;
            font-size: 14px;
        }
        .booking-mitra {
            font-size: 12px;
            color: #666;
        }
        .booking-actions {
            padding: 10px 15px;
            border-top: 1px solid #f1f1f1;
            text-align: right;
        }
        .booking-btn {
            padding: 5px 15px;
            border-radius: 5px;
            font-size: 12px;
            border: none;
            cursor: pointer;
            margin-left: 5px;
        }
        .btn-detail {
            background-color: #0074E4;
            color: white;
        }
        .btn-cancel {
            background-color: #F44336;
            color: white;
        }
        .logout-btn {
            display: block;
            width: 100%;
            padding: 12px 20px;
            background-color: #F44336;
            color: white;
            border: none;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            margin-top: 20px;
            text-decoration: none;
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
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-picture-container">
                <?php if(!empty($user['foto_profil']) && file_exists($user['foto_profil'])): ?>
                    <img src="<?php echo $user['foto_profil']; ?>" class="profile-picture" alt="Profile Picture">
                <?php else: ?>
                    <img src="images/default_profile.jpg" class="profile-picture" alt="Default Profile Picture">
                <?php endif; ?>
                <label for="upload-photo" class="change-photo" title="Ubah Foto" id="changePhotoBtn">
                    <i class="fas fa-camera"></i>
                </label>
            </div>
            <div class="profile-header-details">
                <h2><?php echo $user['nama_lengkap']; ?></h2>
                <p><?php echo $user['email']; ?></p>
                <p><i class="fas fa-phone"></i> <?php echo !empty($user['no_hp']) ? $user['no_hp'] : 'Belum diatur'; ?></p>
                <p><i class="fas fa-calendar"></i> Bergabung sejak <?php echo date('d M Y', strtotime($user['tanggal_pendaftaran'])); ?></p>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tab-container">
            <div class="tab active" onclick="openTab('profile')">Profil</div>
            <div class="tab" onclick="openTab('bookings')">Pemesanan</div>
            <div class="tab" onclick="openTab('activity')">Aktivitas</div>
        </div>
        
        <!-- Tab Contents -->
        <div id="profile" class="tab-content active">
            <?php if(!empty($message)): ?>
                <div class="alert <?php echo $message_type == 'success' ? 'alert-success' : 'alert-danger'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <!-- Hidden file input for profile photo -->
                <input type="file" id="upload-photo" name="foto_profil" style="display: none;" onchange="showFileName()">
                
                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" name="nama_lengkap" value="<?php echo $user['nama_lengkap']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="<?php echo $user['email']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nomor HP</label>
                    <input type="text" class="form-control" name="no_hp" value="<?php echo $user['no_hp']; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password Lama (jika ingin mengganti password)</label>
                    <input type="password" class="form-control" name="password_lama">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password Baru</label>
                    <input type="password" class="form-control" name="password_baru">
                </div>
                
                <button type="submit" class="btn btn-primary" name="update_profile">Simpan Perubahan</button>
            </form>
            
            <a href="logout.php" class="logout-btn">Keluar</a>
        </div>
        
        <div id="bookings" class="tab-content">
            <?php if(mysqli_num_rows($result_bookings) > 0): ?>
                <?php while($booking = mysqli_fetch_assoc($result_bookings)): ?>
                    <div class="booking-card">
                        <div class="booking-header">
                            <div class="booking-date">
                                <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($booking['tanggal_booking'])); ?>
                            </div>
                            <?php 
                            $status_class = '';
                            switch($booking['status_booking']) {
                                case 'confirmed':
                                    $status_text = 'Dikonfirmasi';
                                    $status_class = 'status-confirmed';
                                    break;
                                case 'pending':
                                    $status_text = 'Menunggu';
                                    $status_class = 'status-pending';
                                    break;
                                case 'canceled':
                                    $status_text = 'Dibatalkan';
                                    $status_class = 'status-canceled';
                                    break;
                                default:
                                    $status_text = $booking['status_booking'];
                                    $status_class = '';
                            }
                            ?>
                            <span class="booking-status <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                        </div>
                        <div class="booking-body">
                            <?php if(!empty($booking['foto_kost']) && file_exists($booking['foto_kost'])): ?>
                                <img src="<?php echo $booking['foto_kost']; ?>" class="booking-image" alt="<?php echo $booking['nama_kost']; ?>">
                            <?php else: ?>
                                <img src="images/default_kost.jpg" class="booking-image" alt="Default Kost Image">
                            <?php endif; ?>
                            <div class="booking-info">
                                <div class="booking-kost-name"><?php echo $booking['nama_kost']; ?></div>
                                <div class="booking-kost-address">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo $booking['alamat']; ?>
                                </div>
                                <div class="booking-price">
                                    <i class="fas fa-tags"></i> Rp <?php echo number_format($booking['harga_sewa'], 0, ',', '.'); ?> / bulan
                                </div>
                                <div class="booking-mitra">
                                    <i class="fas fa-user"></i> <?php echo $booking['nama_mitra']; ?>
                                </div>
                            </div>
                        </div>
                        <div class="booking-actions">
                            <button class="booking-btn btn-detail" onclick="window.location.href='detail_booking.php?id=<?php echo $booking['id_booking']; ?>'">
                                Detail
                            </button>
                            <?php if($booking['status_booking'] != 'canceled'): ?>
                                <button class="booking-btn btn-cancel" onclick="cancelBooking(<?php echo $booking['id_booking']; ?>)">
                                    Batalkan
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>Tidak ada pemesanan</h3>
                    <p>Anda belum memiliki riwayat pemesanan kost.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div id="activity" class="tab-content">
            <div class="empty-state">
                <i class="fas fa-history"></i>
                <h3>Tidak ada aktivitas terbaru</h3>
                <p>Aktivitas dan riwayat Anda akan muncul di sini.</p>
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
            <a href="bookmarks.php" class="nav-item">
                <i class="fas fa-bookmark"></i>
                <span>Tersimpan</span>
            </a>
            <a href="profile_mhs.php" class="nav-item active">
                <i class="fas fa-user"></i>
                <span>Profil</span>
            </a>
        </div>
    </div>

    <script>
        function openTab(tabName) {
            // Hide all tab contents
            const tabContents = document.getElementsByClassName("tab-content");
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove("active");
            }
            
            // Remove active class from all tabs
            const tabs = document.getElementsByClassName("tab");
            for (let i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove("active");
            }
            
            // Show the specific tab content
            document.getElementById(tabName).classList.add("active");
            
            // Add active class to the clicked tab
            event.currentTarget.classList.add("active");
        }
        
        // Trigger file input when change photo button is clicked
        document.getElementById("changePhotoBtn").addEventListener("click", function() {
            document.getElementById("upload-photo").click();
        });
        
        // Show file name after selecting
        function showFileName() {
            const input = document.getElementById("upload-photo");
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector(".profile-picture").src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // // Cancel booking function
        // function cancelBooking(bookingId) {
        //     if (confirm("Apakah Anda yakin ingin membatalkan pemesanan ini?")) {
        //         // Submit form or call AJAX to cancel booking
        //         window.location.href = "cancel_booking.php?id=" + bookingId;
        //     }
        // }
    </script>
</body>
</html>