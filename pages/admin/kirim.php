<?php
// Tandai undangan terkirim lalu buka WhatsApp dengan pesan siap kirim
if (!hash_equals(csrf_token(), (string) ($_GET['t'] ?? ''))) { http_response_code(419); exit('Tautan kedaluwarsa, muat ulang halaman tamu.'); }
$t = q('SELECT t.*, a.id aid FROM tamu t JOIN acara a ON a.id = t.acara_id WHERE t.id = ?', [(int) ($_GET['tid'] ?? 0)])->fetch();
if (!$t || !$t['wa']) { flash('Tamu tidak punya nomor WhatsApp.', 'error'); redirect('dashboard'); }
$a = q('SELECT * FROM acara WHERE id = ?', [$t['aid']])->fetch();
q('UPDATE tamu SET dikirim_at = COALESCE(dikirim_at, NOW()) WHERE id = ?', [$t['id']]);
header('Location: https://wa.me/' . nomor_wa($t['wa']) . '?text=' . rawurlencode(pesan_wa($a, $t)));
exit;
