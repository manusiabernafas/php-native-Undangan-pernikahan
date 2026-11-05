<?php
// Inti aplikasi: koneksi database, helper, keamanan
declare(strict_types=1);
date_default_timezone_set('Asia/Jakarta');
session_start();

const UPLOAD_DIR = __DIR__ . '/../assets/uploads/';
const PALET = [
    'sage'       => ['Sage & Krem',        '#f6f3ec', '#4f6b52', '#c9a96a'],
    'terracotta' => ['Terakota',           '#f8efe7', '#a5543f', '#d9a35b'],
    'navy'       => ['Biru Navy & Emas',   '#f3f2ee', '#1f3557', '#c6a15b'],
    'mauve'      => ['Mauve',              '#f7f0f1', '#7d4f62', '#c9a2a8'],
];

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = require __DIR__ . '/../config/database.php';
        $pdo = new PDO("mysql:host={$c['host']};port={$c['port']};dbname={$c['database']};charset=utf8mb4", $c['username'], $c['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '+07:00'");
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function e($v): string { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }

function url(string $page, array $p = []): string { return 'admin.php?' . http_build_query(['page' => $page] + $p); }

function redirect(string $page, array $p = []): void { header('Location: ' . url($page, $p)); exit; }

function flash(?string $msg = null, string $type = 'ok')
{
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return null; }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function user(): ?array { return $_SESSION['user'] ?? null; }

function csrf_token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">'; }
function verify_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals(csrf_token(), (string) ($_POST['_csrf'] ?? ''))) {
        http_response_code(419);
        exit('Sesi formulir kedaluwarsa, silakan muat ulang halaman.');
    }
}

/** Alamat dasar aplikasi, contoh http://localhost/undangan/ — dipakai untuk link tamu. */
function base_url(): string
{
    $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir . '/';
}

function link_tamu(array $acara, ?array $tamu = null): string
{
    return base_url() . '?u=' . rawurlencode($acara['slug']) . ($tamu ? '&k=' . $tamu['kode'] : '');
}

const HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
const BULAN = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

function tgl_panjang(string $dt): string
{
    $t = strtotime($dt);
    return HARI[(int) date('w', $t)] . ', ' . date('j', $t) . ' ' . BULAN[(int) date('n', $t)] . ' ' . date('Y', $t);
}

function jam(?string $dt): string { return $dt ? date('H.i', strtotime($dt)) : ''; }

function waktu_lalu(string $dt): string
{
    $d = time() - strtotime($dt);
    if ($d < 60) return 'baru saja';
    if ($d < 3600) return floor($d / 60) . ' menit lalu';
    if ($d < 86400) return floor($d / 3600) . ' jam lalu';
    if ($d < 86400 * 30) return floor($d / 86400) . ' hari lalu';
    return date('j', strtotime($dt)) . ' ' . BULAN[(int) date('n', strtotime($dt))] . ' ' . date('Y', strtotime($dt));
}

function nomor_wa(string $no): string
{
    $no = preg_replace('/\D/', '', $no);
    if (str_starts_with($no, '0')) $no = '62' . substr($no, 1);
    return $no;
}

function kode_tamu(): string
{
    $huruf = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';   // tanpa 0/O/1/I agar tidak tertukar
    do {
        $k = '';
        for ($i = 0; $i < 6; $i++) $k .= $huruf[random_int(0, strlen($huruf) - 1)];
    } while (q('SELECT 1 FROM tamu WHERE kode = ?', [$k])->fetchColumn());
    return $k;
}

/** Nama pasangan sesuai urutan yang dipilih, contoh "Nadia & Raka". */
function pasangan(array $a, string $field = 'panggilan'): array
{
    $w = $a['wanita_' . $field]; $p = $a['pria_' . $field];
    return $a['urutan'] === 'pria' ? [$p, $w] : [$w, $p];
}

/** Simpan file upload gambar/audio dengan validasi tipe & ukuran. */
function simpan_upload(array $f, string $folder, array $mimeBoleh, int $maksMb): ?string
{
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload gagal (kode ' . $f['error'] . ').');
    if ($f['size'] > $maksMb * 1024 * 1024) throw new RuntimeException('Ukuran file maksimal ' . $maksMb . ' MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!isset($mimeBoleh[$mime])) throw new RuntimeException('Tipe file tidak didukung (' . $mime . ').');
    if (!is_dir(UPLOAD_DIR . $folder)) mkdir(UPLOAD_DIR . $folder, 0775, true);
    $nama = $folder . '/' . bin2hex(random_bytes(8)) . '.' . $mimeBoleh[$mime];
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . $nama)) throw new RuntimeException('Gagal menyimpan file.');
    return $nama;
}

const MIME_GAMBAR = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
const MIME_AUDIO = ['audio/mpeg' => 'mp3', 'audio/ogg' => 'ogg', 'audio/mp4' => 'm4a', 'audio/x-m4a' => 'm4a'];

function icon(string $name, int $size = 18): string
{
    // Ikon garis Lucide (MIT) disematkan agar bisa offline
    $p = [
        'heart' => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
        'map' => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
        'calendar' => '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'gift' => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/>',
        'copy' => '<rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
        'music' => '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
        'pause' => '<rect x="14" y="4" width="4" height="16" rx="1"/><rect x="6" y="4" width="4" height="16" rx="1"/>',
        'mail' => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
        'send' => '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'eye' => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
        'chat' => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
        'plus' => '<path d="M5 12h14"/><path d="M12 5v14"/>',
        'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'trash' => '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>',
        'link' => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'eyeoff' => '<path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/>',
        'chevron' => '<path d="m6 9 6 6 6-6"/>',
        'home' => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
    ][$name] ?? '';
    return '<svg class="ic" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

const TEMPLATE_WA = "Assalamu'alaikum Warahmatullahi Wabarakatuh\n\nKepada Yth.\nBapak/Ibu/Saudara/i *{nama}*\n\nTanpa mengurangi rasa hormat, perkenankan kami mengundang Bapak/Ibu/Saudara/i untuk hadir di acara pernikahan kami:\n\n*{pasangan}*\n{tanggal}\n\nInfo lengkap acara, konfirmasi kehadiran, dan ucapan dapat dibuka melalui tautan berikut:\n{link}\n\nMerupakan suatu kebahagiaan bagi kami apabila Bapak/Ibu/Saudara/i berkenan hadir dan memberikan doa restu.\n\nTerima kasih.";

function pesan_wa(array $a, array $t): string
{
    [$c1, $c2] = pasangan($a);
    return strtr($a['template_wa'] ?: TEMPLATE_WA, ['{nama}' => $t['nama'], '{link}' => link_tamu($a, $t), '{pasangan}' => $c1 . ' & ' . $c2, '{tanggal}' => tgl_panjang($a['akad_waktu'])]);
}
