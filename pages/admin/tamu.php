<?php
$acaraAktif = q('SELECT * FROM acara WHERE id = ?', [(int) ($_GET['id'] ?? 0)])->fetch();
if (!$acaraAktif) redirect('dashboard');
$a = $acaraAktif;
$aid = (int) $a['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    try {
        if ($aksi === 'tambah') {
            // Satu tamu per baris: Nama | No WA | Grup | Maks orang  (kolom selain nama opsional)
            $n = 0; $lewati = 0;
            foreach (preg_split('/\r?\n/', (string) ($_POST['daftar'] ?? '')) as $baris) {
                $p = array_map('trim', explode('|', str_replace(["\t", ';'], '|', $baris)));
                if ($p[0] === '') continue;
                $nama = mb_substr($p[0], 0, 120);
                $wa = isset($p[1]) ? preg_replace('/[^\d+]/', '', $p[1]) : '';
                if (q('SELECT 1 FROM tamu WHERE acara_id = ? AND nama = ?', [$aid, $nama])->fetchColumn()) { $lewati++; continue; }
                $maks = isset($p[3]) && (int) $p[3] > 0 ? min(10, (int) $p[3]) : (int) $a['maks_orang_default'];
                q('INSERT INTO tamu (acara_id, kode, nama, wa, grup, maks_orang) VALUES (?,?,?,?,?,?)', [$aid, kode_tamu(), $nama, $wa ?: null, ($p[2] ?? '') !== '' ? mb_substr($p[2], 0, 40) : null, $maks]);
                $n++;
            }
            if (!$n && !$lewati) throw new RuntimeException('Isi minimal satu nama tamu.');
            flash($n . ' tamu ditambahkan' . ($lewati ? ", $lewati dilewati karena namanya sudah ada" : '') . '.');
        } elseif ($aksi === 'ubah') {
            q('UPDATE tamu SET nama = ?, wa = ?, grup = ?, maks_orang = ? WHERE id = ? AND acara_id = ?', [
                mb_substr(trim((string) $_POST['nama']), 0, 120), preg_replace('/[^\d+]/', '', (string) $_POST['wa']) ?: null,
                trim((string) $_POST['grup']) ?: null, max(1, min(10, (int) $_POST['maks_orang'])), (int) $_POST['tid'], $aid]);
            flash('Data tamu diperbarui.');
        } elseif ($aksi === 'hapus') {
            q('DELETE FROM tamu WHERE id = ? AND acara_id = ?', [(int) $_POST['tid'], $aid]);
            flash('Tamu dihapus.');
        } elseif ($aksi === 'reset_kirim') {
            q('UPDATE tamu SET dikirim_at = NULL WHERE id = ? AND acara_id = ?', [(int) $_POST['tid'], $aid]);
            flash('Status kirim dikembalikan.');
        }
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'error');
    }
    redirect('tamu', ['id' => $aid] + array_intersect_key($_GET, array_flip(['f', 's', 'g'])));
}

$f = (string) ($_GET['f'] ?? 'semua');
$cari = trim((string) ($_GET['s'] ?? ''));
$grup = trim((string) ($_GET['g'] ?? ''));
$w = ['t.acara_id = ?']; $p = [$aid];
$filter = [
    'semua' => null, 'belum_kirim' => 't.dikirim_at IS NULL', 'belum_buka' => 't.dikirim_at IS NOT NULL AND t.dibuka_at IS NULL',
    'hadir' => "r.kehadiran = 'hadir'", 'ragu' => "r.kehadiran = 'ragu'", 'tidak' => "r.kehadiran = 'tidak'", 'belum_rsvp' => 'r.id IS NULL',
];
if (!empty($filter[$f])) $w[] = $filter[$f];
if ($cari !== '') { $w[] = '(t.nama LIKE ? OR t.wa LIKE ? OR t.kode = ?)'; array_push($p, "%$cari%", "%$cari%", strtoupper($cari)); }
if ($grup !== '') { $w[] = 't.grup = ?'; $p[] = $grup; }
$rows = q('SELECT t.*, r.kehadiran, r.jumlah, r.updated_at rsvp_at FROM tamu t LEFT JOIN rsvp r ON r.tamu_id = t.id WHERE ' . implode(' AND ', $w) . ' ORDER BY t.nama', $p)->fetchAll();

// Ekspor CSV daftar tamu + RSVP
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="tamu-' . $a['slug'] . '.csv"');
    $o = fopen('php://output', 'w');
    fwrite($o, "\xEF\xBB\xBF");
    fputcsv($o, ['Nama', 'WhatsApp', 'Grup', 'Kode', 'Link', 'Dikirim', 'Dibuka', 'Kehadiran', 'Jumlah'], ';', '"', '');
    foreach ($rows as $r) fputcsv($o, [$r['nama'], $r['wa'], $r['grup'], $r['kode'], link_tamu($a, $r), $r['dikirim_at'], $r['dibuka_at'], $r['kehadiran'], $r['jumlah']], ';', '"', '');
    exit;
}

$s = q("SELECT COUNT(*) total, SUM(t.dikirim_at IS NOT NULL) kirim, SUM(t.dibuka_at IS NOT NULL) buka, SUM(r.kehadiran = 'hadir') hadir, SUM(r.kehadiran = 'ragu') ragu,
        SUM(r.kehadiran = 'tidak') tidak, COALESCE(SUM(CASE WHEN r.kehadiran = 'hadir' THEN r.jumlah END),0) orang, SUM(r.id IS NULL) belum
        FROM tamu t LEFT JOIN rsvp r ON r.tamu_id = t.id WHERE t.acara_id = ?", [$aid])->fetch();
$grupList = q('SELECT grup, COUNT(*) n FROM tamu WHERE acara_id = ? AND grup IS NOT NULL GROUP BY grup ORDER BY grup', [$aid])->fetchAll();
[$c1, $c2] = pasangan($a);
$title = 'Tamu & RSVP';
$sub = e($c1 . ' & ' . $c2) . ' · ' . e(tgl_panjang($a['akad_waktu']));
$actions = '<a class="btn" href="' . url('tamu', ['id' => $aid, 'f' => $f, 's' => $cari, 'g' => $grup, 'export' => 'csv']) . '">' . icon('download', 16) . ' Ekspor CSV</a>'
    . '<button class="btn pri" onclick="document.getElementById(\'dlgTambah\').showModal()">' . icon('plus', 16) . ' Tambah tamu</button>';
require __DIR__ . '/../../includes/admin_header.php';
$pct = fn ($x) => $s['total'] ? round($x / $s['total'] * 100) : 0;
?>
<div class="stats">
  <div class="st"><small>Total tamu</small><b><?= (int) $s['total'] ?></b><span><?= (int) $s['kirim'] ?> sudah dikirim</span></div>
  <div class="st"><small>Membuka undangan</small><b><?= (int) $s['buka'] ?></b><span><?= $pct($s['buka']) ?>% dari tamu</span></div>
  <div class="st dark"><small>Akan hadir</small><b><?= (int) $s['orang'] ?> <em>orang</em></b><span>dari <?= (int) $s['hadir'] ?> undangan</span></div>
  <div class="st"><small>Konfirmasi</small><b><?= (int) $s['total'] - (int) $s['belum'] ?></b><span><?= (int) $s['ragu'] ?> ragu · <?= (int) $s['tidak'] ?> tidak hadir</span></div>
</div>

<div class="toolbar">
  <div class="tabs">
    <?php foreach (['semua' => 'Semua', 'belum_kirim' => 'Belum dikirim', 'belum_buka' => 'Belum dibuka', 'hadir' => 'Hadir', 'ragu' => 'Ragu', 'tidak' => 'Tidak hadir', 'belum_rsvp' => 'Belum RSVP'] as $k => $l): ?>
      <a class="<?= $f === $k ? 'on' : '' ?>" href="<?= url('tamu', ['id' => $aid, 'f' => $k, 's' => $cari, 'g' => $grup]) ?>"><?= $l ?></a>
    <?php endforeach; ?>
  </div>
  <form class="find" method="get"><input type="hidden" name="page" value="tamu"><input type="hidden" name="id" value="<?= $aid ?>"><input type="hidden" name="f" value="<?= e($f) ?>">
    <select name="g" onchange="this.form.submit()"><option value="">Semua grup</option><?php foreach ($grupList as $g): ?><option value="<?= e($g['grup']) ?>" <?= $grup === $g['grup'] ? 'selected' : '' ?>><?= e($g['grup']) ?> (<?= (int) $g['n'] ?>)</option><?php endforeach; ?></select>
    <label class="search"><?= icon('search', 16) ?><input name="s" value="<?= e($cari) ?>" placeholder="Cari nama, no. WA, kode"></label>
  </form>
</div>

<div class="panel">
  <table class="tbl">
    <thead><tr><th>Tamu</th><th>Grup</th><th>Status</th><th>RSVP</th><th class="r">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): $link = link_tamu($a, $r); ?>
      <tr>
        <td><b><?= e($r['nama']) ?></b><small><?= e($r['wa'] ?: 'tanpa no. WA') ?> · <code><?= e($r['kode']) ?></code> · maks <?= (int) $r['maks_orang'] ?></small></td>
        <td><?= $r['grup'] ? '<span class="tag">' . e($r['grup']) . '</span>' : '<span class="mut">-</span>' ?></td>
        <td>
          <?php if ($r['dibuka_at']): ?><span class="dot ok"></span>Dibuka <small><?= e(waktu_lalu($r['dibuka_at'])) ?><?= $r['dibuka_kali'] > 1 ? ' · ' . (int) $r['dibuka_kali'] . 'x' : '' ?></small>
          <?php elseif ($r['dikirim_at']): ?><span class="dot wait"></span>Terkirim <small><?= e(waktu_lalu($r['dikirim_at'])) ?></small>
          <?php else: ?><span class="dot"></span><span class="mut">Belum dikirim</span><?php endif; ?>
        </td>
        <td>
          <?php if ($r['kehadiran'] === 'hadir'): ?><span class="badge hadir">Hadir · <?= (int) $r['jumlah'] ?> org</span>
          <?php elseif ($r['kehadiran'] === 'ragu'): ?><span class="badge ragu">Ragu</span>
          <?php elseif ($r['kehadiran'] === 'tidak'): ?><span class="badge tidak">Tidak hadir</span>
          <?php else: ?><span class="mut">-</span><?php endif; ?>
        </td>
        <td class="r nowrap">
          <?php if ($r['wa']): ?><a class="btn sm <?= $r['dikirim_at'] ? '' : 'pri' ?>" href="<?= url('kirim', ['tid' => $r['id'], 't' => csrf_token()]) ?>" target="_blank" rel="noopener" title="Kirim lewat WhatsApp"><?= icon('chat', 15) ?> <?= $r['dikirim_at'] ? 'Kirim ulang' : 'Kirim WA' ?></a><?php endif; ?>
          <button class="btn sm icon" data-copy="<?= e($link) ?>" title="Salin link"><?= icon('link', 15) ?></button>
          <button class="btn sm icon" title="Ubah" onclick='ubah(<?= e(json_encode(['id' => $r['id'], 'nama' => $r['nama'], 'wa' => $r['wa'], 'grup' => $r['grup'], 'maks' => $r['maks_orang']])) ?>)'><?= icon('edit', 15) ?></button>
          <form method="post" class="inline" data-confirm="Hapus <?= e($r['nama']) ?> beserta RSVP-nya?"><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="tid" value="<?= $r['id'] ?>"><button class="btn sm icon" title="Hapus"><?= icon('trash', 15) ?></button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="5" class="empty">Tidak ada tamu pada filter ini.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<p class="mut sm">Menampilkan <?= count($rows) ?> tamu. Status "Dibuka" tercatat otomatis saat tamu membuka link pribadinya.</p>

<dialog id="dlgTambah" class="dlg">
  <form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="tambah">
    <h2>Tambah tamu</h2>
    <p class="mut sm">Satu tamu per baris. Format: <code>Nama | No. WA | Grup | Maks orang</code>. Hanya nama yang wajib. Bisa juga tempel langsung dari Excel (kolom dipisah tab).</p>
    <textarea name="daftar" rows="10" placeholder="Bapak Hendra Wijaya | 081234567890 | Keluarga | 4&#10;Rina Wulandari | 085712345678 | Teman kantor&#10;Pak RT 05"></textarea>
    <div class="dlg-acts"><button type="button" class="btn" onclick="this.closest('dialog').close()">Batal</button><button class="btn pri">Simpan</button></div>
  </form>
</dialog>
<dialog id="dlgUbah" class="dlg">
  <form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="ubah"><input type="hidden" name="tid">
    <h2>Ubah tamu</h2>
    <label>Nama<input name="nama" required></label>
    <div class="row2"><label>No. WhatsApp<input name="wa"></label><label>Grup<input name="grup" list="grupList"></label></div>
    <label>Maksimal orang<input type="number" name="maks_orang" min="1" max="10"></label>
    <datalist id="grupList"><?php foreach ($grupList as $g): ?><option value="<?= e($g['grup']) ?>"><?php endforeach; ?></datalist>
    <div class="dlg-acts"><button type="button" class="btn" onclick="this.closest('dialog').close()">Batal</button><button class="btn pri">Simpan</button></div>
  </form>
</dialog>
<script>
  function ubah(t) { var f = document.querySelector('#dlgUbah form'); f.tid.value = t.id; f.nama.value = t.nama; f.wa.value = t.wa || ''; f.grup.value = t.grup || ''; f.maks_orang.value = t.maks; document.getElementById('dlgUbah').showModal(); }
</script>
<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
