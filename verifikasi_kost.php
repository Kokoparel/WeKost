<?php
session_start();
include 'config.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login_form.php");
    exit;
}

// Proses verifikasi atau penolakan
if (isset($_POST['action']) && isset($_POST['id_kost'])) {
    $id_kost = $_POST['id_kost'];
    $action = $_POST['action'];
    $status = ($action == 'verifikasi') ? 'diterima' : 'ditolak';
    
    // Mulai transaksi
    $conn->begin_transaction();
    
    try {
        // Update status di tabel kost
        $query_kost = "UPDATE kost SET status_verifikasi = ? WHERE id_kost = ?";
        $stmt_kost = $conn->prepare($query_kost);
        $stmt_kost->bind_param("si", $status, $id_kost);
        $stmt_kost->execute();
        
        // Update status di tabel dokumen_kepemilikan untuk dokumen kost ini
        $query_dok = "UPDATE dokumen_kepemilikan SET status_verifikasi = ? WHERE id_kost = ?";
        $stmt_dok = $conn->prepare($query_dok);
        $stmt_dok->bind_param("si", $status, $id_kost);
        $stmt_dok->execute();
        
        // Commit transaksi jika berhasil
        $conn->commit();
        
        // Set pesan sukses
        $_SESSION['message'] = "Kost berhasil " . ($status == 'diterima' ? 'diverifikasi' : 'ditolak');
    } catch (Exception $e) {
        // Rollback jika ada error
        $conn->rollback();
        $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
    }
    
    // Redirect untuk menghindari resubmit form
    header("Location: verifikasi_kost.php");
    exit;
}

// Query untuk mendapatkan data kost dan dokumen yang belum diverifikasi
$query = "SELECT k.*, u.nama_lengkap AS nama_mitra, u.email AS email_mitra,
          COUNT(d.id_dokumen) AS jumlah_dokumen 
          FROM kost k 
          LEFT JOIN dokumen_kepemilikan d ON k.id_kost = d.id_kost 
          LEFT JOIN users u ON k.id_mitra = u.id_user 
          WHERE k.status_verifikasi = 'menunggu' 
          GROUP BY k.id_kost
          ORDER BY k.tanggal_penambahan DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Verifikasi Kost - WeKost</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            line-height: 1.6;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #3498db;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin: 2px 0;
        }
        .btn-verifikasi {
            background-color: #2ecc71;
            color: white;
        }
        .btn-tolak {
            background-color: #e74c3c;
            color: white;
        }
        .btn-lihat {
            background-color: #3498db;
            color: white;
        }
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .back-link {
            color: #3498db;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .no-data {
            padding: 20px;
            text-align: center;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <h1>Verifikasi Kost</h1>
    
    <?php
    // Tampilkan pesan sukses jika ada
    if (isset($_SESSION['message'])) {
        echo '<div class="message success">' . $_SESSION['message'] . '</div>';
        unset($_SESSION['message']);
    }
    
    // Tampilkan pesan error jika ada
    if (isset($_SESSION['error'])) {
        echo '<div class="message error">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']);
    }
    ?>
    
    <?php if ($result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Kost</th>
                    <th>Alamat</th>
                    <th>Pemilik</th>
                    <th>Email</th>
                    <th>Harga Sewa</th>
                    <th>Ketersediaan</th>
                    <th>Foto</th>
                    <th>Dokumen</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id_kost']; ?></td>
                    <td><?php echo htmlspecialchars($row['nama_kost']); ?></td>
                    <td><?php echo htmlspecialchars($row['alamat']); ?></td>
                    <td><?php echo htmlspecialchars($row['nama_mitra']); ?></td>
                    <td><?php echo htmlspecialchars($row['email_mitra']); ?></td>
                    <td>Rp <?php echo number_format($row['harga_sewa'], 0, ',', '.'); ?></td>
                    <td><?php echo $row['ketersediaan_kamar']; ?> kamar</td>
                    <td>
                        <?php if (!empty($row['foto_kost'])): ?>
                            <a href="uploads/kost/<?php echo htmlspecialchars($row['foto_kost']); ?>" target="_blank" class="btn btn-lihat">Lihat</a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['jumlah_dokumen'] > 0): ?>
                            <?php echo $row['jumlah_dokumen']; ?> dokumen
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td><?php echo $row['status_verifikasi']; ?></td>
                    <td>
                        <form method="POST" action="">
                            <input type="hidden" name="id_kost" value="<?php echo $row['id_kost']; ?>">
                            <input type="hidden" name="action" value="verifikasi">
                            <button type="submit" class="btn btn-verifikasi" onclick="return confirm('Apakah Anda yakin ingin memverifikasi kost ini?')">
                                Verifikasi
                            </button>
                        </form>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="id_kost" value="<?php echo $row['id_kost']; ?>">
                            <input type="hidden" name="action" value="tolak">
                            <button type="submit" class="btn btn-tolak" onclick="return confirm('Apakah Anda yakin ingin menolak kost ini?')">
                                Tolak
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-data">Tidak ada kost yang menunggu verifikasi.</div>
    <?php endif; ?>
    
    <a href="dashboard_admin.php" class="back-link">← Kembali ke Dashboard</a>

</body>
</html>