<?php
session_start();       // Mulai session
session_unset();       // Hapus semua variabel session
session_destroy();     // Hancurkan session-nya

header("Location: login_form.php");  // Arahkan ke halaman login
exit;
?>
