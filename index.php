<?php
// Halaman undangan untuk tamu: ?u=slug-acara&k=KODE_TAMU
require __DIR__ . '/includes/bootstrap.php';

$slug = (string) ($_GET['u'] ?? '');
$a = $slug !== '' ? q('SELECT * FROM acara WHERE slug = ? AND aktif = 1', [$slug])->fetch() : null;
if (!$a) {
    http_response_code(404);
    require __DIR__ . '/pages/tidak-ditemukan.php';
    exit;
}

// Unduh file kalender (.ics)
if (isset($_GET['ics'])) {
    require __DIR__ . '/pages/ics.php';
    exit;
}

$tamu = null;
if (!empty($_GET['k'])) {
    $tamu = q('SELECT t.*, r.kehadiran, r.jumlah FROM tamu t LEFT JOIN rsvp r ON r.tamu_id = t.id WHERE t.kode = ? AND t.acara_id = ?', [strtoupper((string) $_GET['k']), $a['id']])->fetch() ?: null;
    if ($tamu) q('UPDATE tamu SET dibuka_at = COALESCE(dibuka_at, NOW()), dibuka_kali = dibuka_kali + 1 WHERE id = ?', [$tamu['id']]);
}
// Tanpa kode, nama tamu boleh dari ?to= (untuk link umum yang dibagikan manual)
$namaTamu = $tamu['nama'] ?? trim(mb_substr((string) ($_GET['to'] ?? ''), 0, 80));

$rekening = q('SELECT * FROM rekening WHERE acara_id = ? ORDER BY id', [$a['id']])->fetchAll();
$cerita = q('SELECT * FROM cerita WHERE acara_id = ? ORDER BY urut, id', [$a['id']])->fetchAll();
$galeri = q('SELECT * FROM galeri WHERE acara_id = ? ORDER BY urut, id', [$a['id']])->fetchAll();
$stat = q("SELECT COUNT(*) n, SUM(kehadiran = 'hadir') hadir FROM ucapan WHERE acara_id = ? AND tampil = 1", [$a['id']])->fetch();
[$c1, $c2] = pasangan($a);
[$n1, $n2] = pasangan($a, 'nama');
[$o1, $o2] = pasangan($a, 'ortu');
[$f1, $f2] = pasangan($a, 'foto');
$pal = PALET[$a['palet']] ?? PALET['sage'];
$selesai = strtotime($a['resepsi_waktu'] ?: $a['akad_waktu']) < strtotime('today');
$_SESSION['pub_csrf'] ??= bin2hex(random_bytes(16));

require __DIR__ . '/pages/undangan.php';
