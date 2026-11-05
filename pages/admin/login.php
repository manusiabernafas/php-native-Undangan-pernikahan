<?php
if (user()) redirect('dashboard');
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = q('SELECT * FROM users WHERE username = ?', [trim((string) ($_POST['username'] ?? ''))])->fetch();
    if ($u && password_verify((string) ($_POST['password'] ?? ''), $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => $u['id'], 'nama' => $u['nama']];
        redirect('dashboard');
    }
    $error = 'Username atau password salah.';
    usleep(400000);
}
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Masuk · Admin Undangan</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;1,500&family=Jost:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css?v=1"></head>
<body class="login-page">
  <form method="post" class="login-card"><?= csrf_field() ?>
    <div class="brand big"><span>&amp;</span></div>
    <h1>Admin Undangan</h1>
    <p class="mut">Kelola undangan, tamu, dan ucapan</p>
    <?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
    <label>Username<input name="username" required autofocus></label>
    <label>Password<input type="password" name="password" required></label>
    <button class="btn pri block">Masuk</button>
  </form>
</body></html>
