<?php
$acaraAktif = q('SELECT * FROM acara WHERE id = ?', [(int) ($_GET['id'] ?? 0)])->fetch();
if (!$acaraAktif) redirect('dashboard');
$aid = (int) $acaraAktif['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = (int) ($_POST['uid'] ?? 0);
    if (($_POST['aksi'] ?? '') === 'toggle') q('UPDATE ucapan SET tampil = 1 - tampil WHERE id = ? AND acara_id = ?', [$uid, $aid]);
    if (($_POST['aksi'] ?? '') === 'hapus') q('DELETE FROM ucapan WHERE id = ? AND acara_id = ?', [$uid, $aid]);
    flash('Ucapan diperbarui.');
    redirect('ucapan', ['id' => $aid]);
}
$rows = q('SELECT u.*, t.kode FROM ucapan u LEFT JOIN tamu t ON t.id = u.tamu_id WHERE u.acara_id = ? ORDER BY u.id DESC', [$aid])->fetchAll();
[$c1, $c2] = pasangan($acaraAktif);
$title = 'Ucapan & doa';
$sub = e($c1 . ' & ' . $c2) . ' · ' . count($rows) . ' ucapan';
require __DIR__ . '/../../includes/admin_header.php';
?>
<div class="wishes">
  <?php foreach ($rows as $r): ?>
    <article class="wish <?= $r['tampil'] ? '' : 'off' ?>">
      <div class="wh"><b><?= e($r['nama']) ?></b>
        <?php if ($r['kehadiran']): ?><span class="badge <?= e($r['kehadiran']) ?>"><?= ['hadir' => 'Hadir', 'ragu' => 'Ragu', 'tidak' => 'Tidak hadir'][$r['kehadiran']] ?></span><?php endif; ?>
        <?= $r['kode'] ? '<span class="tag">tamu terdaftar</span>' : '<span class="tag">tamu umum</span>' ?>
        <?= $r['tampil'] ? '' : '<span class="tag warn">disembunyikan</span>' ?></div>
      <p><?= nl2br(e($r['isi'])) ?></p>
      <div class="wf"><time><?= e(waktu_lalu($r['created_at'])) ?></time>
        <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="aksi" value="toggle"><input type="hidden" name="uid" value="<?= $r['id'] ?>"><button class="btn sm"><?= icon($r['tampil'] ? 'eyeoff' : 'eye', 15) ?> <?= $r['tampil'] ? 'Sembunyikan' : 'Tampilkan' ?></button></form>
        <form method="post" class="inline" data-confirm="Hapus ucapan ini?"><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="uid" value="<?= $r['id'] ?>"><button class="btn sm icon" title="Hapus"><?= icon('trash', 15) ?></button></form></div>
    </article>
  <?php endforeach; ?>
  <?php if (!$rows): ?><div class="empty">Belum ada ucapan.</div><?php endif; ?>
</div>
<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
