<?php
session_start();
include 'config.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login_form.php");
    exit;
}

// Query untuk mengambil semua data kost
$query = "SELECT k.*, u.nama_lengkap as nama_mitra 
          FROM kost k 
          JOIN users u ON k.id_mitra = u.id_user 
          ORDER BY k.tanggal_penambahan DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Daftar Kost - WeKost</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        nav { margin-bottom: 20px; }
        nav a {
            margin-right: 15px;
            text-decoration: none;
            color: #3498db;
            font-weight: bold;
        }
        nav a:hover { text-decoration: underline; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .status-tersedia {
            color: green;
            font-weight: bold;
        }
        
        .status-penuh {
            color: red;
            font-weight: bold;
        }
        
        .status-menunggu {
            color: orange;
            font-weight: bold;
        }
        
        .status-diterima {
            color: green;
            font-weight: bold;
        }
        
        .status-ditolak {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h2>Daftar Kost</h2>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Kost</th>
                <th>Pemilik (Mitra)</th>
                <th>Alamat</th>
                <th>Harga Sewa</th>
                <th>Kamar Tersedia</th>
                <th>Status Kost</th>
                <th>Status Verifikasi</th>
                <th>Tanggal Ditambahkan</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>";
                    echo "<td>" . $row['id_kost'] . "</td>";
                    echo "<td>" . htmlspecialchars($row['nama_kost']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['nama_mitra']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['alamat']) . "</td>";
                    echo "<td>Rp " . number_format($row['harga_sewa'], 0, ',', '.') . "</td>";
                    echo "<td>" . $row['ketersediaan_kamar'] . "</td>";
                    echo "<td class='status-" . $row['status_kost'] . "'>" . ucfirst($row['status_kost']) . "</td>";
                    echo "<td class='status-" . $row['status_verifikasi'] . "'>" . ucfirst($row['status_verifikasi']) . "</td>";
                    echo "<td>" . date('d-m-Y H:i', strtotime($row['tanggal_penambahan'])) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='10' style='text-align:center;'>Tidak ada data kost yang tersedia</td></tr>";
            }
            ?>
        </tbody>
    </table>
    <p><a href="dashboard_admin.php">&larr; Kembali ke Dashboard</a></p>
</body>
</html>