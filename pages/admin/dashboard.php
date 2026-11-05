<?php
$rows = q("SELECT a.*,
  (SELECT COUNT(*) FROM tamu t WHERE t.acara_id = a.id) tamu,
  (SELECT COUNT(*) FROM tamu t WHERE t.acara_id = a.id AND t.dikirim_at IS NOT NULL) dikirim,
  (SELECT COUNT(*) FROM tamu t WHERE t.acara_id = a.id AND t.dibuka_at IS NOT NULL) dibuka,
  (SELECT COALESCE(SUM(r.jumlah),0) FROM rsvp r JOIN tamu t ON t.id = r.tamu_id WHERE t.acara_id = a.id AND r.kehadiran = 'hadir') orang,
  (SELECT COUNT(*) FROM ucapan u WHERE u.acara_id = a.id) ucapan
  FROM acara a ORDER BY a.akad_waktu")->fetchAll();
$title = 'Semua undangan';
$sub = count($rows) . ' undangan';
$actions = '<a class="btn pri" href="' . url('acara') . '">' . icon('plus', 16) . ' Buat undangan</a>';
require __DIR__ . '/../../includes/admin_header.php';
?>
<div class="events">
  <?php foreach ($rows as $r): [$x1, $x2] = pasangan($r); $pal = PALET[$r['palet']] ?? PALET['sage'];
    $sisa = (int) floor((strtotime(date('Y-m-d', strtotime($r['akad_waktu']))) - strtotime('today')) / 86400); ?>
    <article class="ev-card" style="--p:<?= e($pal[2]) ?>;--a:<?= e($pal[3]) ?>;--b:<?= e($pal[1]) ?>">
      <div class="ev-head">
        <div><h2><?= e($x1) ?> <em>&amp;</em> <?= e($x2) ?></h2><p><?= e(tgl_panjang($r['akad_waktu'])) ?></p></div>
        <span class="pill"><?= $sisa > 0 ? $sisa . ' hari lagi' : ($sisa === 0 ? 'Hari ini' : 'Selesai') ?></span>
      </div>
      <div class="ev-stats">
        <div><b><?= (int) $r['tamu'] ?></b><small>Tamu</small></div>
        <div><b><?= (int) $r['dibuka'] ?></b><small>Membuka</small></div>
        <div><b><?= (int) $r['orang'] ?></b><small>Orang hadir</small></div>
        <div><b><?= (int) $r['ucapan'] ?></b><small>Ucapan</small></div>
      </div>
      <div class="bar"><span style="width:<?= $r['tamu'] ? round($r['dibuka'] / $r['tamu'] * 100) : 0 ?>%"></span></div>
      <p class="mut sm"><?= (int) $r['dikirim'] ?> dari <?= (int) $r['tamu'] ?> undangan terkirim · <?= $r['tamu'] ? round($r['dibuka'] / $r['tamu'] * 100) : 0 ?>% sudah dibuka<?= $r['aktif'] ? '' : ' · <b>nonaktif</b>' ?></p>
      <div class="ev-btns">
        <a class="btn pri" href="<?= url('tamu', ['id' => $r['id']]) ?>"><?= icon('users', 16) ?> Tamu &amp; RSVP</a>
        <a class="btn" href="<?= url('acara', ['id' => $r['id']]) ?>"><?= icon('edit', 16) ?> Edit</a>
        <a class="btn" href="<?= e(link_tamu($r)) ?>" target="_blank" rel="noopener"><?= icon('eye', 16) ?> Lihat</a>
      </div>
    </article>
  <?php endforeach; ?>
  <?php if (!$rows): ?><div class="empty">Belum ada undangan. <a href="<?= url('acara') ?>">Buat undangan pertama</a>.</div><?php endif; ?>
</div>
<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
