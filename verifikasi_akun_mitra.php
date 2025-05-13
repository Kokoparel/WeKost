<?php
include 'config.php';

// Ambil data mitra yang statusnya pending beserta data verifikasi
$query = "SELECT u.id_user, u.email, u.nama_lengkap, u.no_hp, 
                 v.foto_ktp, v.foto_diri, v.alamat_lengkap, v.provinsi, 
                 v.detail_alamat, v.pekerjaan, v.instansi_pekerjaan, 
                 v.jabatan, v.penghasilan, v.status_verifikasi
          FROM users u 
          LEFT JOIN mitra_verifikasi v ON u.id_user = v.id_user
          WHERE u.tipe_pengguna = 'mitra' AND u.status_pengguna = 'pending'";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query error: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Verifikasi Akun Mitra</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            vertical-align: top;
            text-align: left;
        }
        th {
            background-color: #2196F3;
            color: white;
        }
        tr:hover {background-color: #f1f1f1;}
        button {
            margin: 2px;
            padding: 6px 12px;
            border: none;
            cursor: pointer;
            border-radius: 3px;
            font-weight: bold;
        }
        .btn-approve {
            background-color: #4CAF50;
            color: white;
        }
        .btn-reject {
            background-color: #f44336;
            color: white;
        }
        .notification {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }
        .error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }
        h2 {
            color: #333;
        }
        a {
            color: #2196F3;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<h2>Verifikasi Akun Mitra</h2>

<?php
// Tampilkan notifikasi jika ada
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        $message = ($action === 'approve') ? 'Akun mitra berhasil diverifikasi!' : 'Akun mitra berhasil ditolak!';
        echo '<div class="notification success">' . $message . '</div>';
    } elseif ($_GET['status'] === 'error') {
        $message = isset($_GET['message']) ? $_GET['message'] : 'Terjadi kesalahan saat memproses data.';
        echo '<div class="notification error">' . htmlspecialchars($message) . '</div>';
    }
}
?>

<?php if (mysqli_num_rows($result) == 0): ?>
    <p>Tidak ada mitra yang menunggu verifikasi.</p>
<?php else: ?>
<table>
    <tr>
        <th>ID</th>
        <th>Email</th>
        <th>Nama Lengkap</th>
        <th>No HP</th>
        <th>Alamat Lengkap</th>
        <th>Provinsi</th>
        <th>Detail Alamat</th>
        <th>Pekerjaan</th>
        <th>Instansi Pekerjaan</th>
        <th>Jabatan</th>
        <th>Penghasilan</th>
        <th>Foto KTP</th>
        <th>Foto Diri</th>
        <th>Status</th>
        <th>Aksi</th>
    </tr>
    <?php while($row = mysqli_fetch_assoc($result)) { ?>
    <tr>
        <td><?= htmlspecialchars($row['id_user']) ?></td>
        <td><?= htmlspecialchars($row['email']) ?></td>
        <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
        <td><?= htmlspecialchars($row['no_hp']) ?></td>
        <td><?= htmlspecialchars($row['alamat_lengkap']) ?></td>
        <td><?= htmlspecialchars($row['provinsi']) ?></td>
        <td><?= htmlspecialchars($row['detail_alamat']) ?></td>
        <td><?= htmlspecialchars($row['pekerjaan']) ?></td>
        <td><?= htmlspecialchars($row['instansi_pekerjaan']) ?></td>
        <td><?= htmlspecialchars($row['jabatan']) ?></td>
        <td><?= htmlspecialchars($row['penghasilan']) ?></td>
        <td>
            <?php if (!empty($row['foto_ktp'])): ?>
                <a href="<?= htmlspecialchars($row['foto_ktp']) ?>" target="_blank">Lihat</a>
            <?php else: ?>
                N/A
            <?php endif; ?>
        </td>
        <td>
            <?php if (!empty($row['foto_diri'])): ?>
                <a href="<?= htmlspecialchars($row['foto_diri']) ?>" target="_blank">Lihat</a>
            <?php else: ?>
                N/A
            <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($row['status_verifikasi'] ?? 'pending') ?></td>
        <td>
            <form method="post" action="process_verifikasi.php" onsubmit="return confirm('Apakah Anda yakin?');">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($row['id_user']) ?>">
                <button type="submit" name="action" value="approve" class="btn-approve">Verifikasi</button>
                <button type="submit" name="action" value="reject" class="btn-reject">Tolak</button>
            </form>
        </td>
    </tr>
    <?php } ?>
</table>
<?php endif; ?>
<p><a href="dashboard_admin.php">&larr; Kembali ke Dashboard</a></p>
</body>
</html>