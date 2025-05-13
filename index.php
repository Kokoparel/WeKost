<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Selamat Datang - WeKost</title>
  <style>
    * {
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background: #f0f4f8;
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .container {
      background: white;
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0, 123, 255, 0.15);
      padding: 40px 30px;
      width: 400px;
      text-align: center;
    }

    h1 {
      color: #007BFF;
      margin-bottom: 10px;
    }

    p {
      color: #555;
      margin-bottom: 30px;
    }

    .role-btn {
      display: block;
      background: #007BFF;
      color: white;
      border: none;
      padding: 12px;
      border-radius: 8px;
      font-size: 16px;
      margin: 10px 0;
      cursor: pointer;
      text-decoration: none;
    }

    .role-btn:hover {
      background: #0056b3;
    }

    footer {
      margin-top: 40px;
      font-size: 13px;
      color: #888;
    }

  </style>
</head>
<body>
  <div class="container">
    <h1>Selamat Datang di WeKost</h1>
    <p>Pilih peran Anda untuk melanjutkan</p>

    <a href="login_form.php?role=pengguna" class="role-btn">Saya Mahasiswa</a>
    <a href="login_form.php?role=mitra" class="role-btn">Saya Mitra Kost</a>

    <footer>&copy; <?= date("Y") ?> WeKost. Semua Hak Dilindungi.</footer>
  </div>
</body>
</html>
