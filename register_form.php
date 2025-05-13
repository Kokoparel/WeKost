<?php
$t = isset($_GET['role']) ? $_GET['role'] : 'pengguna'; // default pengguna
$buttonLabel = $t === 'mitra' ? 'Lanjut' : 'Daftar';
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Registrasi - WeKost</title>
  <style>
    body {
      background-color: #f0f4f8;
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .container {
      background: white;
      padding: 40px;
      border-radius: 12px;
      width: 420px;
      box-shadow: 0 8px 20px rgba(0, 123, 255, 0.1);
    }

    h2 {
      color: #007BFF;
      margin-bottom: 20px;
      text-align: center;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"],
    input[type="file"],
    input[type="tel"] {
      width: 100%;
      padding: 12px;
      margin-bottom: 16px;
      border: 1px solid #ccc;
      border-radius: 8px;
    }

    label {
      font-size: 14px;
      color: #333;
    }

    .btn {
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 8px;
      background-color: #007BFF;
      color: white;
      font-size: 16px;
      cursor: pointer;
    }

    .btn:hover {
      background-color: #0056b3;
    }

    .bottom-text {
      text-align: center;
      margin-top: 16px;
      font-size: 14px;
    }

    .bottom-text a {
      color: #007BFF;
      text-decoration: none;
    }

    .oauth {
      display: flex;
      justify-content: space-evenly;
      margin-top: 20px;
    }

    .oauth button {
      background: #eee;
      border: none;
      padding: 10px 16px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Daftar</h2>
    <form action="register_process.php" method="post" enctype="multipart/form-data">
      <input type="hidden" name="tipe" value="<?= $t ?>">
      <label>Nama Lengkap</label>
      <input type="text" name="nama" required>

      <label>Email</label>
      <input type="email" name="email" required>

      <label>No HP</label>
      <input type="tel" name="no_hp" required>

      <label>Password</label>
      <input type="password" name="password" required>

      <label>Upload Foto Profil (Opsional)</label>
      <input type="file" name="foto">

      <button type="submit" class="btn"><?= $buttonLabel ?></button>
    </form>

    <div class="oauth">
      <button>Google</button>
      <button>Facebook</button>
    </div>

    <div class="bottom-text">
      Sudah punya akun? <a href="login_form.php?role=<?= $t ?>">Masuk</a>
    </div>
  </div>
</body>
</html>
