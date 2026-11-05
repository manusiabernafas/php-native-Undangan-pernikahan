<?php
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $me = q('SELECT * FROM users WHERE id = ?', [user()['id']])->fetch();
    if (!password_verify((string) $_POST['lama'], $me['password'])) $error = 'Password lama salah.';
    elseif (strlen((string) $_POST['baru']) < 8) $error = 'Password baru minimal 8 karakter.';
    elseif ($_POST['baru'] !== $_POST['ulang']) $error = 'Konfirmasi password tidak sama.';
    else {
        q('UPDATE users SET password = ?, nama = ? WHERE id = ?', [password_hash((string) $_POST['baru'], PASSWORD_DEFAULT), trim((string) $_POST['nama']) ?: $me['nama'], $me['id']]);
        $_SESSION['user']['nama'] = trim((string) $_POST['nama']) ?: $me['nama'];
        flash('Akun diperbarui.');
        redirect('akun');
    }
}
$title = 'Akun';
require __DIR__ . '/../../includes/admin_header.php';
?>
<?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="panel pad narrow"><?= csrf_field() ?>
  <label>Nama<input name="nama" value="<?= e(user()['nama']) ?>"></label>
  <label>Password lama<input type="password" name="lama" required></label>
  <div class="row2"><label>Password baru<input type="password" name="baru" required minlength="8"></label><label>Ulangi password baru<input type="password" name="ulang" required></label></div>
  <button class="btn pri">Simpan</button>
</form>
<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
