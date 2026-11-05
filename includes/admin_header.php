<?php
/** Variabel: $title, $acaraAktif (opsional), $actions (opsional) */
$navAcara = q('SELECT id, slug, wanita_panggilan, pria_panggilan, urutan, akad_waktu, palet FROM acara ORDER BY akad_waktu')->fetchAll();
$navNow = $_GET['page'] ?? 'dashboard';
$navId = (int) ($acaraAktif['id'] ?? 0);
$navFlash = flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · Admin Undangan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=Jost:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css?v=1">
</head>
<body>
<div class="shell">
  <aside class="nav">
    <a class="brand" href="<?= url('dashboard') ?>"><span>&amp;</span><div><b>Undangan</b><small>Panel admin</small></div></a>
    <a class="nl <?= $navNow === 'dashboard' ? 'on' : '' ?>" href="<?= url('dashboard') ?>"><?= icon('home', 18) ?> Semua undangan</a>
    <p class="nh">Undangan</p>
    <?php foreach ($navAcara as $navA): [$navX1, $navX2] = pasangan($navA); ?>
      <a class="nl ev <?= $navId === (int) $navA['id'] ? 'on' : '' ?>" href="<?= url('tamu', ['id' => $navA['id']]) ?>"><i style="background:<?= e((PALET[$navA['palet']] ?? PALET['sage'])[2]) ?>"></i><span><?= e($navX1 . " & " . $navX2) ?><small><?= e(date('j/n/Y', strtotime($navA['akad_waktu']))) ?></small></span></a>
      <?php if ($navId === (int) $navA['id']): ?>
        <div class="sub">
          <a class="<?= $navNow === 'tamu' ? 'on' : '' ?>" href="<?= url('tamu', ['id' => $navA['id']]) ?>">Tamu &amp; RSVP</a>
          <a class="<?= $navNow === 'ucapan' ? 'on' : '' ?>" href="<?= url('ucapan', ['id' => $navA['id']]) ?>">Ucapan</a>
          <a class="<?= $navNow === 'acara' ? 'on' : '' ?>" href="<?= url('acara', ['id' => $navA['id']]) ?>">Edit undangan</a>
          <a href="<?= e(link_tamu($navA)) ?>" target="_blank" rel="noopener">Lihat undangan &#8599;</a>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
    <a class="nl add" href="<?= url('acara') ?>"><?= icon('plus', 18) ?> Buat undangan</a>
    <div class="me"><span><?= e(user()['nama']) ?></span><a href="<?= url('akun') ?>">Akun</a><a href="<?= url('logout') ?>" title="Keluar"><?= icon('logout', 16) ?></a></div>
  </aside>
  <main class="content">
    <header class="top"><div><h1><?= e($title) ?></h1><?php if (!empty($sub)): ?><p><?= $sub ?></p><?php endif; ?></div><div class="acts"><?= $actions ?? '' ?></div></header>
    <?php if ($navFlash): ?><div class="alert <?= $navFlash[1] === "error" ? "err" : "" ?>"><?= e($navFlash[0]) ?></div><?php endif; ?>
