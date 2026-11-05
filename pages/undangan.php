<?php
/** @var array $a @var ?array $tamu @var string $namaTamu */
$monogram = mb_substr($c1, 0, 1) . mb_substr($c2, 0, 1);
$tglAkad = tgl_panjang($a['akad_waktu']);
$foto = fn ($f, $n) => $f ? '<img src="assets/uploads/' . e($f) . '" alt="' . e($n) . '">' : '<span>' . e(mb_substr($n, 0, 1)) . '</span>';
$ranting = '<svg class="sprig" viewBox="0 0 120 40" aria-hidden="true"><path d="M2 20 H118" stroke="currentColor" stroke-width="1"/><g fill="currentColor">'
    . '<ellipse cx="30" cy="14" rx="7" ry="3" transform="rotate(-30 30 14)"/><ellipse cx="44" cy="26" rx="7" ry="3" transform="rotate(30 44 26)"/><ellipse cx="58" cy="14" rx="7" ry="3" transform="rotate(-30 58 14)"/>'
    . '<ellipse cx="72" cy="26" rx="7" ry="3" transform="rotate(30 72 26)"/><ellipse cx="86" cy="14" rx="7" ry="3" transform="rotate(-30 86 14)"/><circle cx="104" cy="20" r="3"/></g></svg>';
$acaraList = [['akad', 'Akad Nikah', $a['akad_waktu'], $a['akad_selesai'], $a['akad_tempat'], $a['akad_alamat']]];
if ($a['resepsi_waktu']) $acaraList[] = ['resepsi', 'Resepsi', $a['resepsi_waktu'], $a['resepsi_selesai'], $a['resepsi_tempat'], $a['resepsi_alamat']];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Undangan Pernikahan <?= e($c1 . ' & ' . $c2) ?></title>
<meta name="description" content="<?= e('Undangan pernikahan ' . $c1 . ' & ' . $c2 . ', ' . $tglAkad) ?>">
<meta property="og:title" content="<?= e('The Wedding of ' . $c1 . ' & ' . $c2) ?>">
<meta property="og:description" content="<?= e($tglAkad . ' · ' . $a['akad_tempat']) ?>">
<meta name="theme-color" content="<?= e($pal[2]) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/undangan.css?v=1">
<style>:root{--bg:<?= e($pal[1]) ?>;--pri:<?= e($pal[2]) ?>;--acc:<?= e($pal[3]) ?>}</style>
</head>
<body class="is-locked">

<!-- Sampul -->
<section class="cover" id="cover">
  <div class="cover-in">
    <p class="eyebrow">Undangan Pernikahan</p>
    <div class="mono"><?= e($monogram) ?></div>
    <h1 class="names"><?= e($c1) ?> <em>&amp;</em> <?= e($c2) ?></h1>
    <p class="date"><?= e($tglAkad) ?></p>
    <?= $ranting ?>
    <div class="to">
      <small>Kepada Yth. Bapak/Ibu/Saudara/i</small>
      <b><?= e($namaTamu !== '' ? $namaTamu : 'Tamu Undangan') ?></b>
      <?php if ($tamu): ?><small class="note">Mohon maaf bila ada kesalahan penulisan nama dan gelar</small><?php endif; ?>
    </div>
    <button class="btn" id="btnOpen"><?= icon('mail', 18) ?> Buka Undangan</button>
  </div>
</section>

<div class="layout">
  <!-- Panel kiri (desktop) -->
  <aside class="side">
    <div class="side-in">
      <p class="eyebrow">The Wedding of</p>
      <h2 class="names"><?= e($c1) ?><br><em>&amp;</em><br><?= e($c2) ?></h2>
      <?= $ranting ?>
      <p class="date"><?= e($tglAkad) ?></p>
    </div>
  </aside>

  <main class="main">
    <section class="sec hero reveal">
      <p class="eyebrow">Kami yang berbahagia</p>
      <h2 class="names big"><?= e($c1) ?> <em>&amp;</em> <?= e($c2) ?></h2>
      <p class="date"><?= e($tglAkad) ?></p>
      <?php if ($a['kutipan']): ?>
        <blockquote class="quote"><?= nl2br(e($a['kutipan'])) ?><?php if ($a['kutipan_sumber']): ?><cite><?= e($a['kutipan_sumber']) ?></cite><?php endif; ?></blockquote>
      <?php endif; ?>
    </section>

    <section class="sec couple reveal">
      <h3 class="title">Mempelai</h3>
      <p class="lead">Dengan memohon rahmat dan ridha Tuhan Yang Maha Esa, kami bermaksud menyelenggarakan pernikahan kami</p>
      <div class="person"><div class="photo"><?= $foto($f1, $c1) ?></div><h4><?= e($n1) ?></h4><p><?= e($o1) ?></p></div>
      <div class="amp">&amp;</div>
      <div class="person"><div class="photo"><?= $foto($f2, $c2) ?></div><h4><?= e($n2) ?></h4><p><?= e($o2) ?></p></div>
    </section>

    <?php if (!$selesai): ?>
    <section class="sec count reveal">
      <h3 class="title">Menuju hari bahagia</h3>
      <div class="cd" id="countdown" data-t="<?= strtotime($a['akad_waktu']) * 1000 ?>">
        <div><b data-u="d">0</b><small>Hari</small></div><div><b data-u="h">0</b><small>Jam</small></div><div><b data-u="m">0</b><small>Menit</small></div><div><b data-u="s">0</b><small>Detik</small></div>
      </div>
    </section>
    <?php endif; ?>

    <section class="sec events reveal">
      <h3 class="title">Waktu &amp; Tempat</h3>
      <?php foreach ($acaraList as [$key, $label, $mulai, $akhir, $tempat, $alamat]): ?>
        <article class="event">
          <h4><?= e($label) ?></h4>
          <p class="ev-date"><?= icon('calendar', 16) ?> <?= e(tgl_panjang($mulai)) ?></p>
          <p class="ev-time"><?= icon('clock', 16) ?> <?= e(jam($mulai)) ?><?= $akhir ? ' – ' . e(substr($akhir, 0, 5)) : '' ?> WIB</p>
          <p class="ev-place"><b><?= e($tempat) ?></b><br><?= e($alamat) ?></p>
          <div class="ev-btns">
            <?php if ($a['maps_url']): ?><a class="btn ghost" href="<?= e($a['maps_url']) ?>" target="_blank" rel="noopener"><?= icon('map', 16) ?> Lihat lokasi</a><?php endif; ?>
            <a class="btn ghost" href="?u=<?= e(rawurlencode($a['slug'])) ?>&amp;ics=<?= $key ?>"><?= icon('calendar', 16) ?> Simpan ke kalender</a>
          </div>
        </article>
      <?php endforeach; ?>
    </section>

    <?php if ($cerita): ?>
    <section class="sec story reveal">
      <h3 class="title">Cerita Kami</h3>
      <ol class="timeline"><?php foreach ($cerita as $c): ?><li><small><?= e($c['waktu']) ?></small><h4><?= e($c['judul']) ?></h4><p><?= nl2br(e($c['isi'])) ?></p></li><?php endforeach; ?></ol>
    </section>
    <?php endif; ?>

    <?php if ($galeri): ?>
    <section class="sec gallery reveal">
      <h3 class="title">Galeri</h3>
      <div class="grid"><?php foreach ($galeri as $i => $g): ?><button class="g<?= $i % 5 === 0 ? ' wide' : '' ?>" data-src="assets/uploads/<?= e($g['file']) ?>"><img src="assets/uploads/<?= e($g['file']) ?>" alt="Galeri <?= $i + 1 ?>" loading="lazy"></button><?php endforeach; ?></div>
    </section>
    <?php endif; ?>

    <?php if ($tamu && !$selesai): ?>
    <section class="sec rsvp reveal" id="rsvp">
      <h3 class="title">Konfirmasi Kehadiran</h3>
      <p class="lead">Mohon kesediaannya untuk mengisi konfirmasi kehadiran agar kami dapat menyiapkan tempat dengan baik.</p>
      <form class="card" id="formRsvp">
        <p class="who"><?= icon('users', 16) ?> <?= e($tamu['nama']) ?> <small>· maksimal <?= (int) $tamu['maks_orang'] ?> orang</small></p>
        <div class="opts">
          <?php foreach (['hadir' => 'Hadir', 'ragu' => 'Masih ragu', 'tidak' => 'Tidak hadir'] as $k => $l): ?>
            <label><input type="radio" name="kehadiran" value="<?= $k ?>" <?= ($tamu['kehadiran'] ?? '') === $k ? 'checked' : '' ?> required><span><?= $l ?></span></label>
          <?php endforeach; ?>
        </div>
        <label class="field" id="fJumlah">Jumlah yang hadir
          <select name="jumlah"><?php for ($i = 1; $i <= (int) $tamu['maks_orang']; $i++): ?><option value="<?= $i ?>" <?= (int) ($tamu['jumlah'] ?? 1) === $i ? 'selected' : '' ?>><?= $i ?> orang</option><?php endfor; ?></select>
        </label>
        <p class="msg" id="rsvpMsg" hidden></p>
        <button class="btn"><?= icon('check', 16) ?> <?= $tamu['kehadiran'] ? 'Perbarui konfirmasi' : 'Kirim konfirmasi' ?></button>
      </form>
    </section>
    <?php endif; ?>

    <section class="sec wishes reveal" id="ucapan">
      <h3 class="title">Ucapan &amp; Doa</h3>
      <p class="lead"><span id="wCount"><?= (int) $stat['n'] ?></span> ucapan · <span id="wHadir"><?= (int) $stat['hadir'] ?></span> akan hadir</p>
      <form class="card" id="formUcapan">
        <?php if (!$tamu): ?><label class="field">Nama<input name="nama" maxlength="80" required value="<?= e($namaTamu) ?>"></label><?php endif; ?>
        <label class="field">Ucapan &amp; doa<textarea name="isi" rows="3" maxlength="500" required placeholder="Tulis ucapan untuk kedua mempelai"></textarea></label>
        <?php if (!$tamu): ?>
        <label class="field">Kehadiran<select name="kehadiran"><option value="">Pilih</option><option value="hadir">Hadir</option><option value="ragu">Masih ragu</option><option value="tidak">Tidak hadir</option></select></label>
        <?php endif; ?>
        <p class="msg" id="ucapanMsg" hidden></p>
        <button class="btn"><?= icon('send', 16) ?> Kirim ucapan</button>
      </form>
      <ul class="wlist" id="wList"></ul>
      <button class="btn ghost more" id="wMore" hidden>Muat ucapan lainnya</button>
    </section>

    <?php if ($rekening || $a['alamat_kado']): ?>
    <section class="sec gift reveal">
      <h3 class="title">Tanda Kasih</h3>
      <p class="lead">Doa restu Anda merupakan karunia yang sangat berarti bagi kami. Namun jika memberi adalah ungkapan tanda kasih, Anda dapat memberi kado secara digital.</p>
      <?php foreach ($rekening as $r): ?>
        <div class="card bank"><div><small><?= e($r['bank']) ?></small><b class="no"><?= e(trim(chunk_split($r['nomor'], 4, ' '))) ?></b><span>a.n. <?= e($r['atas_nama']) ?></span></div>
          <button class="btn ghost sm" data-copy="<?= e($r['nomor']) ?>"><?= icon('copy', 15) ?> Salin</button></div>
      <?php endforeach; ?>
      <?php if ($a['alamat_kado']): ?>
        <div class="card bank"><div><small>Kirim kado</small><span><?= e($a['alamat_kado']) ?></span></div><button class="btn ghost sm" data-copy="<?= e($a['alamat_kado']) ?>"><?= icon('copy', 15) ?> Salin</button></div>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <section class="sec closing reveal">
      <p class="lead">Merupakan suatu kehormatan dan kebahagiaan bagi kami apabila Bapak/Ibu/Saudara/i berkenan hadir dan memberikan doa restu.</p>
      <p class="eyebrow">Kami yang berbahagia</p>
      <h2 class="names big"><?= e($c1) ?> <em>&amp;</em> <?= e($c2) ?></h2>
      <?= $ranting ?>
    </section>
  </main>
</div>

<?php if ($a['musik']): ?>
<audio id="music" src="assets/uploads/<?= e($a['musik']) ?>" loop preload="none"></audio>
<button class="music" id="btnMusic" aria-label="Musik" hidden><?= icon('music', 18) ?></button>
<?php endif; ?>
<div class="lightbox" id="lightbox" hidden><img alt=""></div>
<div class="toast" id="toast" hidden></div>

<script>
  window.UND = { slug: <?= json_encode($a['slug']) ?>, kode: <?= json_encode($tamu['kode'] ?? '') ?>, csrf: <?= json_encode($_SESSION['pub_csrf']) ?> };
</script>
<script src="assets/js/undangan.js?v=1"></script>
</body>
</html>
