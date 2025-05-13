<?php
include 'config.php';

// Ambil semua data pengguna
$query = "SELECT * FROM users";
$result = mysqli_query($conn, $query);

// Pesan sukses jika ada
$message = isset($_GET['message']) ? $_GET['message'] : '';
$alert_message = '';

if ($message === 'user_nonaktif') {
    $alert_message = 'Akun pengguna berhasil dinonaktifkan.';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Pengguna</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:hover {background-color: #f1f1f1;}
        h2 {
            color: #333;
        }
        .btn-nonaktif {
            background-color: #ff9800;
            color: white;
            border: none;
            padding: 6px 12px;
            cursor: pointer;
            border-radius: 3px;
            font-weight: bold;
        }
        .btn-aktifkan {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 6px 12px;
            cursor: pointer;
            border-radius: 3px;
            font-weight: bold;
        }
        .status-aktif {
            color: green;
            font-weight: bold;
        }
        .status-nonaktif {
            color: red;
            font-weight: bold;
        }
        .status-pending {
            color: orange;
            font-weight: bold;
        }
        .alert {
            padding: 10px;
            background-color: #dff0d8;
            border: 1px solid #d6e9c6;
            border-radius: 4px;
            color: #3c763d;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<h2>Data Pengguna</h2>

<?php if ($alert_message): ?>
<div class="alert">
    <?= htmlspecialchars($alert_message) ?>
</div>
<?php endif; ?>

<table>
    <tr>
        <th>ID</th>
        <th>Nama Lengkap</th>
        <th>Email</th>
        <th>No HP</th>
        <th>Foto Profil</th>
        <th>Role</th>
        <th>Status</th>
        <th>Aksi</th>
    </tr>
    <?php while($row = mysqli_fetch_assoc($result)) { ?>
    <tr>
        <td><?= htmlspecialchars($row['id_user']) ?></td>
        <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
        <td><?= htmlspecialchars($row['email']) ?></td>
        <td><?= htmlspecialchars($row['no_hp']) ?></td>
        <td><?= htmlspecialchars($row['foto_profil']) ?></td>
        <td><?= htmlspecialchars($row['tipe_pengguna']) ?></td>
        <td class="status-<?= strtolower($row['status_pengguna']) ?>">
            <?= htmlspecialchars($row['status_pengguna']) ?>
        </td>
        <td>
            <?php if ($row['status_pengguna'] == 'aktif'): ?>
                <form method="post" action="nonaktifkan_pengguna.php" onsubmit="return confirm('Yakin ingin menonaktifkan akun pengguna ini?');">
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($row['id_user']) ?>">
                    <button type="submit" class="btn-nonaktif">Nonaktifkan</button>
                </form>
            <?php elseif ($row['status_pengguna'] == 'nonaktif'): ?>
                <form method="post" action="aktifkan_pengguna.php" onsubmit="return confirm('Yakin ingin mengaktifkan kembali akun pengguna ini?');">
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($row['id_user']) ?>">
                    <button type="submit" class="btn-aktifkan">Aktifkan</button>
                </form>
            <?php elseif ($row['status_pengguna'] == 'pending' && $row['tipe_pengguna'] == 'mitra'): ?>
                <form method="post" action="aktifkan_pengguna.php" onsubmit="return confirm('Yakin ingin menyetujui akun mitra ini?');">
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($row['id_user']) ?>">
                    <button type="submit" class="btn-aktifkan">Setujui</button>
                </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php } ?>
</table>
<p><a href="dashboard_admin.php">&larr; Kembali ke Dashboard</a></p>
</body>
</html>