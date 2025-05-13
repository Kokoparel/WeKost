<?php 
$role = $_GET['role'] ?? 'pengguna'; // Default role adalah 'pengguna' 
$error = $_GET['error'] ?? '';

// Tampilkan pesan error yang sesuai
$error_message = '';
if ($error == 'password_salah') {
    $error_message = 'Password yang Anda masukkan salah.';
} elseif ($error == 'email_tidak_ditemukan') {
    $error_message = "Email tidak ditemukan untuk role " . ucfirst($role) . ".";
} elseif ($error == 'tipe_user_tidak_valid') {
    $error_message = 'Tipe pengguna tidak valid.';
} elseif ($error == 'akun_pending') {
    $error_message = 'Mohon maaf, akun anda masih dalam proses verifikasi admin. Mohon cek berkala kembali.';
} elseif ($error == 'akun_nonaktif') {
    $error_message = 'Mohon maaf, akun anda telah dinonaktifkan.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login - WeKost</title>
  <style>
    * {
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background: #f0f4f8;
      display: flex;
      height: 100vh;
      align-items: center;
      justify-content: center;
    }

    .container {
      background: white;
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0, 123, 255, 0.15);
      width: 400px;
      padding: 30px;
    }

    h2 {
      color: #007BFF;
      margin-bottom: 20px;
      text-align: center;
    }

    input {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 6px;
    }

    button {
      width: 100%;
      padding: 10px;
      background: #007BFF;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 16px;
      margin-top: 10px;
    }

    .other-login {
      margin-top: 20px;
      text-align: center;
    }

    .other-login button {
      background: #4267B2;
      margin-bottom: 10px;
    }

    .google-btn {
      background: #DB4437;
    }

    .small-text {
      margin-top: 15px;
      font-size: 14px;
      text-align: center;
    }

    .small-text a {
      color: #007BFF;
      text-decoration: none;
      font-weight: bold;
    }

    .error-message {
      color: #d9534f;
      background-color: #f9e2e2;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 15px;
      text-align: center;
      font-size: 14px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Masuk sebagai <?= ucfirst($role) ?></h2>
    
    <?php if ($error_message): ?>
    <div class="error-message">
      <?= $error_message ?>
    </div>
    <?php endif; ?>
    
    <form method="POST" action="login.php">
      <input type="hidden" name="role" value="<?= $role ?>">
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Masuk</button>
    </form>

    <div class="other-login">
      <p>Atau masuk dengan</p>
      <button class="google-btn">Google</button>
      <button>Facebook</button>
    </div>

    <div class="small-text">
      Belum punya akun? <a href="register_form.php?role=<?= $role ?>">Daftar</a>
    </div>
  </div>
</body>
</html>