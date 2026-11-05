<?php
// Endpoint publik untuk RSVP dan ucapan (dipanggil dari halaman undangan)
require __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

function jawab(bool $ok, string $msg, array $extra = []): never
{
    echo json_encode(['ok' => $ok, 'msg' => $msg] + $extra);
    exit;
}

$a = q('SELECT * FROM acara WHERE slug = ? AND aktif = 1', [(string) ($_REQUEST['u'] ?? '')])->fetch();
if (!$a) { http_response_code(404); jawab(false, 'Undangan tidak ditemukan.'); }
$action = (string) ($_REQUEST['action'] ?? '');

// ---- Daftar ucapan (GET) ----
if ($action === 'ucapan') {
    $p = [$a['id']]; $w = '';
    if (!empty($_GET['before'])) { $w = ' AND u.id < ?'; $p[] = (int) $_GET['before']; }
    if (!empty($_GET['after'])) { $w = ' AND u.id > ?'; $p[] = (int) $_GET['after']; }
    $limit = 10;
    $rows = q("SELECT u.id, u.nama, u.isi, u.kehadiran, u.created_at FROM ucapan u WHERE u.acara_id = ? AND u.tampil = 1$w ORDER BY u.id DESC LIMIT " . ($limit + 1), $p)->fetchAll();
    $more = count($rows) > $limit;
    $rows = array_slice($rows, 0, $limit);
    $stat = q("SELECT COUNT(*) n, COALESCE(SUM(kehadiran = 'hadir'),0) hadir FROM ucapan WHERE acara_id = ? AND tampil = 1", [$a['id']])->fetch();
    echo json_encode([
        'items' => array_map(fn ($r) => ['id' => (int) $r['id'], 'nama' => $r['nama'], 'isi' => $r['isi'], 'kehadiran' => $r['kehadiran'], 'waktu' => waktu_lalu($r['created_at'])], $rows),
        'more' => $more && empty($_GET['after']),
        'stat' => ['n' => (int) $stat['n'], 'hadir' => (int) $stat['hadir']],
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); jawab(false, 'Metode tidak didukung.'); }
if (!hash_equals((string) ($_SESSION['pub_csrf'] ?? ''), (string) ($_POST['_csrf'] ?? ''))) jawab(false, 'Sesi kedaluwarsa, muat ulang halaman.');

$tamu = null;
if (!empty($_POST['k'])) {
    $tamu = q('SELECT * FROM tamu WHERE kode = ? AND acara_id = ?', [strtoupper((string) $_POST['k']), $a['id']])->fetch() ?: null;
}
$kehadiran = in_array($_POST['kehadiran'] ?? '', ['hadir', 'tidak', 'ragu'], true) ? $_POST['kehadiran'] : null;
$lewat = strtotime(substr($a['resepsi_waktu'] ?: $a['akad_waktu'], 0, 10) . ' 23:59:59') < time();

// ---- RSVP (hanya tamu dengan link personal) ----
if ($action === 'rsvp') {
    if (!$tamu) jawab(false, 'Konfirmasi hanya bisa lewat link undangan pribadi.');
    if ($lewat) jawab(false, 'Acara sudah berlalu.');
    if (!$kehadiran) jawab(false, 'Pilih status kehadiran.');
    $jumlah = $kehadiran === 'hadir' ? max(1, min((int) $tamu['maks_orang'], (int) ($_POST['jumlah'] ?? 1))) : ($kehadiran === 'ragu' ? 1 : 0);
    q('INSERT INTO rsvp (tamu_id, kehadiran, jumlah, updated_at) VALUES (?,?,?,NOW()) ON DUPLICATE KEY UPDATE kehadiran = VALUES(kehadiran), jumlah = VALUES(jumlah), updated_at = NOW()', [$tamu['id'], $kehadiran, $jumlah]);
    jawab(true, $kehadiran === 'hadir' ? "Terima kasih! Kami menantikan kehadiran Anda ($jumlah orang)." : ($kehadiran === 'ragu' ? 'Terima kasih, semoga bisa hadir ya.' : 'Terima kasih atas konfirmasinya. Doa Anda sangat berarti bagi kami.'));
}

// ---- Kirim ucapan ----
if ($action === 'kirim_ucapan') {
    $isi = trim(preg_replace('/[ \t]+/', ' ', (string) ($_POST['isi'] ?? '')));
    $nama = $tamu ? $tamu['nama'] : trim(mb_substr((string) ($_POST['nama'] ?? ''), 0, 80));
    if (mb_strlen($nama) < 2) jawab(false, 'Isi nama Anda.');
    if (mb_strlen($isi) < 3) jawab(false, 'Ucapan terlalu singkat.');
    if (mb_strlen($isi) > 500) jawab(false, 'Ucapan maksimal 500 karakter.');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    // Batasi spam: maksimal 3 ucapan per 10 menit per IP, dan tidak boleh mengirim isi yang sama dua kali
    $baru = (int) q('SELECT COUNT(*) FROM ucapan WHERE acara_id = ? AND ip = ? AND created_at > NOW() - INTERVAL 10 MINUTE', [$a['id'], $ip])->fetchColumn();
    if ($baru >= 3) jawab(false, 'Terlalu banyak ucapan dalam waktu singkat. Coba lagi beberapa menit lagi.');
    if (q('SELECT 1 FROM ucapan WHERE acara_id = ? AND nama = ? AND isi = ?', [$a['id'], $nama, $isi])->fetchColumn()) jawab(false, 'Ucapan yang sama sudah terkirim.');
    q('INSERT INTO ucapan (acara_id, tamu_id, nama, isi, kehadiran, ip, created_at) VALUES (?,?,?,?,?,?,NOW())', [$a['id'], $tamu['id'] ?? null, $nama, $isi, $kehadiran, $ip]);
    jawab(true, 'Terima kasih atas ucapan dan doanya.');
}

http_response_code(400);
jawab(false, 'Aksi tidak dikenal.');
