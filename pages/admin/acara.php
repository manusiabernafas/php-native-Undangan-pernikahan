<?php
$id = (int) ($_GET['id'] ?? 0);
$acaraAktif = $id ? q('SELECT * FROM acara WHERE id = ?', [$id])->fetch() : null;
if ($id && !$acaraAktif) redirect('dashboard');
$a = $acaraAktif ?: [
    'slug' => '', 'pria_panggilan' => '', 'pria_nama' => '', 'pria_ortu' => '', 'pria_foto' => null, 'wanita_panggilan' => '', 'wanita_nama' => '', 'wanita_ortu' => '', 'wanita_foto' => null,
    'urutan' => 'wanita', 'akad_waktu' => date('Y-m-d 08:00', strtotime('+30 days')), 'akad_selesai' => '10:00', 'akad_tempat' => '', 'akad_alamat' => '',
    'resepsi_waktu' => date('Y-m-d 11:00', strtotime('+30 days')), 'resepsi_selesai' => '14:00', 'resepsi_tempat' => '', 'resepsi_alamat' => '', 'maps_url' => '',
    'kutipan' => '', 'kutipan_sumber' => '', 'palet' => 'sage', 'musik' => null, 'alamat_kado' => '', 'maks_orang_default' => 2, 'template_wa' => '', 'aktif' => 1,
];
$rekening = $id ? q('SELECT * FROM rekening WHERE acara_id = ? ORDER BY id', [$id])->fetchAll() : [];
$cerita = $id ? q('SELECT * FROM cerita WHERE acara_id = ? ORDER BY urut, id', [$id])->fetchAll() : [];
$galeri = $id ? q('SELECT * FROM galeri WHERE acara_id = ? ORDER BY urut, id', [$id])->fetchAll() : [];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $in = fn ($k, $max = 255) => mb_substr(trim((string) ($_POST[$k] ?? '')), 0, $max);
    $d = [
        'pria_panggilan' => $in('pria_panggilan', 40), 'pria_nama' => $in('pria_nama', 120), 'pria_ortu' => $in('pria_ortu', 200),
        'wanita_panggilan' => $in('wanita_panggilan', 40), 'wanita_nama' => $in('wanita_nama', 120), 'wanita_ortu' => $in('wanita_ortu', 200),
        'urutan' => ($_POST['urutan'] ?? '') === 'pria' ? 'pria' : 'wanita',
        'akad_waktu' => $in('akad_tanggal', 10) . ' ' . $in('akad_jam', 5), 'akad_selesai' => $in('akad_selesai', 5) ?: null,
        'akad_tempat' => $in('akad_tempat', 150), 'akad_alamat' => $in('akad_alamat'),
        'resepsi_waktu' => $in('resepsi_tanggal', 10) ? $in('resepsi_tanggal', 10) . ' ' . ($in('resepsi_jam', 5) ?: '11:00') : null, 'resepsi_selesai' => $in('resepsi_selesai', 5) ?: null,
        'resepsi_tempat' => $in('resepsi_tempat', 150) ?: null, 'resepsi_alamat' => $in('resepsi_alamat') ?: null,
        'maps_url' => $in('maps_url', 500) ?: null, 'kutipan' => $in('kutipan', 1000) ?: null, 'kutipan_sumber' => $in('kutipan_sumber', 100) ?: null,
        'palet' => isset(PALET[$_POST['palet'] ?? '']) ? $_POST['palet'] : 'sage', 'alamat_kado' => $in('alamat_kado') ?: null,
        'maks_orang_default' => max(1, min(10, (int) ($_POST['maks_orang_default'] ?? 2))), 'template_wa' => $in('template_wa', 3000) ?: null,
        'aktif' => empty($_POST['aktif']) ? 0 : 1,
        'slug' => trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($in('slug', 60) ?: $in('wanita_panggilan') . '-' . $in('pria_panggilan'))), '-'),
    ];
    $a = $d + $a;
    try {
        foreach (['pria_panggilan' => 'Panggilan mempelai pria', 'pria_nama' => 'Nama mempelai pria', 'wanita_panggilan' => 'Panggilan mempelai wanita', 'wanita_nama' => 'Nama mempelai wanita', 'akad_tempat' => 'Tempat akad'] as $k => $l) {
            if ($d[$k] === '') throw new RuntimeException("$l wajib diisi.");
        }
        if (!strtotime($d['akad_waktu'])) throw new RuntimeException('Tanggal & jam akad tidak valid.');
        if ($d['maps_url'] && !preg_match('#^https?://#', $d['maps_url'])) throw new RuntimeException('Link Google Maps harus diawali http:// atau https://');
        if (strlen($d['slug']) < 3) throw new RuntimeException('Alamat link (slug) minimal 3 karakter.');
        if (q('SELECT 1 FROM acara WHERE slug = ? AND id <> ?', [$d['slug'], $id])->fetchColumn()) throw new RuntimeException('Alamat link "' . $d['slug'] . '" sudah dipakai undangan lain.');

        // Upload dulu (di luar transaksi); file lama dihapus setelah berhasil disimpan
        $hapusFile = [];
        foreach (['pria_foto', 'wanita_foto'] as $k) {
            $baru = simpan_upload($_FILES[$k] ?? [], 'foto', MIME_GAMBAR, 4);
            if ($baru) { if ($a[$k] ?? null) $hapusFile[] = $a[$k]; $d[$k] = $baru; }
            elseif (!empty($_POST['hapus_' . $k])) { if ($a[$k] ?? null) $hapusFile[] = $a[$k]; $d[$k] = null; }
        }
        $musik = simpan_upload($_FILES['musik'] ?? [], 'musik', MIME_AUDIO, 10);
        if ($musik) { if ($acaraAktif['musik'] ?? null) $hapusFile[] = $acaraAktif['musik']; $d['musik'] = $musik; }
        elseif (!empty($_POST['hapus_musik'])) { if ($acaraAktif['musik'] ?? null) $hapusFile[] = $acaraAktif['musik']; $d['musik'] = null; }
        $galBaru = [];
        if (!empty($_FILES['galeri']['name'][0])) {
            foreach ($_FILES['galeri']['name'] as $i => $_) {
                if (count($galeri) + count($galBaru) >= 24) break;
                $galBaru[] = simpan_upload(['name' => $_FILES['galeri']['name'][$i], 'type' => $_FILES['galeri']['type'][$i], 'tmp_name' => $_FILES['galeri']['tmp_name'][$i], 'error' => $_FILES['galeri']['error'][$i], 'size' => $_FILES['galeri']['size'][$i]], 'galeri', MIME_GAMBAR, 4);
            }
        }

        db()->beginTransaction();
        if ($id) {
            q('UPDATE acara SET ' . implode(', ', array_map(fn ($k) => "$k = ?", array_keys($d))) . ' WHERE id = ?', [...array_values($d), $id]);
        } else {
            q('INSERT INTO acara (' . implode(', ', array_keys($d)) . ') VALUES (' . implode(',', array_fill(0, count($d), '?')) . ')', array_values($d));
            $id = (int) db()->lastInsertId();
        }
        q('DELETE FROM rekening WHERE acara_id = ?', [$id]);
        foreach ($_POST['rek_bank'] ?? [] as $i => $bank) {
            $no = preg_replace('/\s+/', '', (string) ($_POST['rek_nomor'][$i] ?? ''));
            if (trim($bank) !== '' && $no !== '') q('INSERT INTO rekening (acara_id, bank, nomor, atas_nama) VALUES (?,?,?,?)', [$id, mb_substr(trim($bank), 0, 40), mb_substr($no, 0, 40), mb_substr(trim((string) ($_POST['rek_nama'][$i] ?? '')), 0, 100)]);
        }
        q('DELETE FROM cerita WHERE acara_id = ?', [$id]);
        foreach ($_POST['cer_judul'] ?? [] as $i => $judul) {
            if (trim($judul) !== '') q('INSERT INTO cerita (acara_id, judul, waktu, isi, urut) VALUES (?,?,?,?,?)', [$id, mb_substr(trim($judul), 0, 80), mb_substr(trim((string) ($_POST['cer_waktu'][$i] ?? '')), 0, 40), trim((string) ($_POST['cer_isi'][$i] ?? '')), $i]);
        }
        foreach ($_POST['hapus_galeri'] ?? [] as $gid) {
            $g = q('SELECT file FROM galeri WHERE id = ? AND acara_id = ?', [(int) $gid, $id])->fetch();
            if ($g) { $hapusFile[] = $g['file']; q('DELETE FROM galeri WHERE id = ?', [(int) $gid]); }
        }
        $urut = (int) q('SELECT COALESCE(MAX(urut),0) FROM galeri WHERE acara_id = ?', [$id])->fetchColumn();
        foreach ($galBaru as $file) q('INSERT INTO galeri (acara_id, file, urut) VALUES (?,?,?)', [$id, $file, ++$urut]);
        db()->commit();
        foreach ($hapusFile as $file) if (is_file(UPLOAD_DIR . $file) && str_contains($file, '/')) @unlink(UPLOAD_DIR . $file);
        flash('Undangan disimpan.');
        redirect('acara', ['id' => $id]);
    } catch (RuntimeException $ex) {
        if (db()->inTransaction()) db()->rollBack();
        $error = $ex->getMessage();
        $rekening = array_map(fn ($i) => ['bank' => $_POST['rek_bank'][$i], 'nomor' => $_POST['rek_nomor'][$i] ?? '', 'atas_nama' => $_POST['rek_nama'][$i] ?? ''], array_keys($_POST['rek_bank'] ?? []));
        $cerita = array_map(fn ($i) => ['judul' => $_POST['cer_judul'][$i], 'waktu' => $_POST['cer_waktu'][$i] ?? '', 'isi' => $_POST['cer_isi'][$i] ?? ''], array_keys($_POST['cer_judul'] ?? []));
    }
}

$title = $acaraAktif ? 'Edit undangan' : 'Buat undangan';
$sub = $acaraAktif ? 'Link: <a href="' . e(link_tamu($acaraAktif)) . '" target="_blank" rel="noopener">' . e(link_tamu($acaraAktif)) . '</a>' : 'Isi data mempelai dan acara, lalu tambahkan tamu';
$actions = '<button class="btn pri" form="formAcara">' . icon('check', 16) . ' Simpan</button>';
require __DIR__ . '/../../includes/admin_header.php';
$v = fn ($k) => e($a[$k] ?? '');
$tgl = fn ($k) => $a[$k] ? e(date('Y-m-d', strtotime($a[$k]))) : '';
$jm = fn ($k) => $a[$k] ? e(date('H:i', strtotime($a[$k]))) : '';
?>
<?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<form id="formAcara" method="post" enctype="multipart/form-data" class="form-acara"><?= csrf_field() ?>
  <section class="panel pad">
    <h2>Mempelai</h2>
    <div class="row2">
      <?php foreach (['wanita' => 'Mempelai wanita', 'pria' => 'Mempelai pria'] as $s => $lbl): ?>
        <div class="col">
          <h3><?= $lbl ?></h3>
          <label>Nama panggilan<input name="<?= $s ?>_panggilan" value="<?= $v($s . '_panggilan') ?>" required placeholder="<?= $s === 'wanita' ? 'Nadia' : 'Raka' ?>"></label>
          <label>Nama lengkap &amp; gelar<input name="<?= $s ?>_nama" value="<?= $v($s . '_nama') ?>" required></label>
          <label>Keterangan orang tua<input name="<?= $s ?>_ortu" value="<?= $v($s . '_ortu') ?>" placeholder="Putri pertama Bapak ... & Ibu ..."></label>
          <label>Foto <small>(opsional, JPG/PNG maks 4 MB; tanpa foto akan tampil inisial)</small><input type="file" name="<?= $s ?>_foto" accept="image/*"></label>
          <?php if ($a[$s . '_foto'] ?? null): ?><div class="thumb"><img src="assets/uploads/<?= $v($s . '_foto') ?>" alt=""><label class="chk"><input type="checkbox" name="hapus_<?= $s ?>_foto" value="1"> Hapus foto</label></div><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <label class="chk-line">Urutan nama<select name="urutan"><option value="wanita" <?= $a['urutan'] === 'wanita' ? 'selected' : '' ?>>Wanita &amp; Pria</option><option value="pria" <?= $a['urutan'] === 'pria' ? 'selected' : '' ?>>Pria &amp; Wanita</option></select></label>
  </section>

  <section class="panel pad">
    <h2>Acara</h2>
    <div class="row2">
      <div class="col"><h3>Akad nikah / pemberkatan</h3>
        <div class="row3"><label>Tanggal<input type="date" name="akad_tanggal" value="<?= $tgl('akad_waktu') ?>" required></label><label>Mulai<input type="time" name="akad_jam" value="<?= $jm('akad_waktu') ?>" required></label><label>Selesai<input type="time" name="akad_selesai" value="<?= e(substr((string) $a['akad_selesai'], 0, 5)) ?>"></label></div>
        <label>Tempat<input name="akad_tempat" value="<?= $v('akad_tempat') ?>" required></label>
        <label>Alamat<input name="akad_alamat" value="<?= $v('akad_alamat') ?>"></label>
      </div>
      <div class="col"><h3>Resepsi <small>(kosongkan tanggal jika tidak ada)</small></h3>
        <div class="row3"><label>Tanggal<input type="date" name="resepsi_tanggal" value="<?= $tgl('resepsi_waktu') ?>"></label><label>Mulai<input type="time" name="resepsi_jam" value="<?= $jm('resepsi_waktu') ?>"></label><label>Selesai<input type="time" name="resepsi_selesai" value="<?= e(substr((string) $a['resepsi_selesai'], 0, 5)) ?>"></label></div>
        <label>Tempat<input name="resepsi_tempat" value="<?= $v('resepsi_tempat') ?>"></label>
        <label>Alamat<input name="resepsi_alamat" value="<?= $v('resepsi_alamat') ?>"></label>
      </div>
    </div>
    <label>Link Google Maps<input name="maps_url" value="<?= $v('maps_url') ?>" placeholder="https://maps.app.goo.gl/..."></label>
  </section>

  <section class="panel pad">
    <h2>Tampilan</h2>
    <p class="mut sm">Pilih palet warna undangan</p>
    <div class="palets">
      <?php foreach (PALET as $k => [$nm, $bg, $pri, $acc]): ?>
        <label class="pal"><input type="radio" name="palet" value="<?= $k ?>" <?= $a['palet'] === $k ? 'checked' : '' ?>><span style="background:<?= $bg ?>"><i style="background:<?= $pri ?>"></i><i style="background:<?= $acc ?>"></i></span><b><?= e($nm) ?></b></label>
      <?php endforeach; ?>
    </div>
    <div class="row2">
      <label>Kutipan / ayat<textarea name="kutipan" rows="3"><?= $v('kutipan') ?></textarea></label>
      <div><label>Sumber kutipan<input name="kutipan_sumber" value="<?= $v('kutipan_sumber') ?>" placeholder="QS. Ar-Rum: 21"></label>
        <label>Musik latar <small>(MP3 maks 10 MB, pastikan bebas hak cipta / milik sendiri)</small><input type="file" name="musik" accept="audio/*"></label>
        <?php if ($acaraAktif['musik'] ?? null): ?><div class="thumb"><audio controls src="assets/uploads/<?= e($acaraAktif['musik']) ?>"></audio><label class="chk"><input type="checkbox" name="hapus_musik" value="1"> Hapus musik</label></div><?php endif; ?>
      </div>
    </div>
  </section>

  <section class="panel pad">
    <h2>Cerita kami <small class="mut">(opsional)</small></h2>
    <div id="cerList">
      <?php foreach (array_merge($cerita, $cerita ? [] : [['judul' => '', 'waktu' => '', 'isi' => '']]) as $c): ?>
        <div class="rep"><div class="row2"><label>Judul<input name="cer_judul[]" value="<?= e($c['judul']) ?>" placeholder="Pertama bertemu"></label><label>Waktu<input name="cer_waktu[]" value="<?= e($c['waktu']) ?>" placeholder="Maret 2021"></label></div>
          <label>Cerita<textarea name="cer_isi[]" rows="2"><?= e($c['isi']) ?></textarea></label><button type="button" class="btn sm icon rm" title="Hapus"><?= icon('trash', 15) ?></button></div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="btn sm" data-add="cerList"><?= icon('plus', 15) ?> Tambah cerita</button>
  </section>

  <section class="panel pad">
    <h2>Galeri</h2>
    <?php if ($galeri): ?><div class="gal"><?php foreach ($galeri as $g): ?><label><img src="assets/uploads/<?= e($g['file']) ?>" alt=""><span class="chk"><input type="checkbox" name="hapus_galeri[]" value="<?= $g['id'] ?>"> Hapus</span></label><?php endforeach; ?></div><?php endif; ?>
    <label>Tambah foto <small>(bisa pilih banyak sekaligus, maks 24 foto, masing-masing 4 MB)</small><input type="file" name="galeri[]" accept="image/*" multiple></label>
  </section>

  <section class="panel pad">
    <h2>Amplop digital</h2>
    <div id="rekList">
      <?php foreach (array_merge($rekening, $rekening ? [] : [['bank' => '', 'nomor' => '', 'atas_nama' => '']]) as $r): ?>
        <div class="rep"><div class="row3"><label>Bank / e-wallet<input name="rek_bank[]" value="<?= e($r['bank']) ?>" placeholder="BCA"></label><label>Nomor<input name="rek_nomor[]" value="<?= e($r['nomor']) ?>"></label><label>Atas nama<input name="rek_nama[]" value="<?= e($r['atas_nama']) ?>"></label></div><button type="button" class="btn sm icon rm" title="Hapus"><?= icon('trash', 15) ?></button></div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="btn sm" data-add="rekList"><?= icon('plus', 15) ?> Tambah rekening</button>
    <label class="mt">Alamat kirim kado <small>(opsional)</small><input name="alamat_kado" value="<?= $v('alamat_kado') ?>"></label>
  </section>

  <section class="panel pad">
    <h2>Pengaturan</h2>
    <div class="row2">
      <label>Alamat link (slug)<input name="slug" value="<?= $v('slug') ?>" placeholder="nadia-raka"><small>Undangan dibuka di <code><?= e(base_url()) ?>?u=<b>slug</b></code></small></label>
      <label>Default maksimal orang per tamu<input type="number" name="maks_orang_default" min="1" max="10" value="<?= (int) $a['maks_orang_default'] ?>"></label>
    </div>
    <label>Template pesan WhatsApp <small>Kode: {nama} {link} {pasangan} {tanggal}. Kosongkan untuk memakai template bawaan.</small><textarea name="template_wa" rows="8" placeholder="<?= e(TEMPLATE_WA) ?>"><?= $v('template_wa') ?></textarea></label>
    <label class="chk"><input type="checkbox" name="aktif" value="1" <?= $a['aktif'] ? 'checked' : '' ?>> Undangan aktif (bisa dibuka tamu)</label>
  </section>
  <div class="form-foot"><button class="btn pri"><?= icon('check', 16) ?> Simpan undangan</button></div>
</form>
<script>
  document.querySelectorAll('[data-add]').forEach(function (b) {
    b.addEventListener('click', function () {
      var list = document.getElementById(b.dataset.add), first = list.querySelector('.rep'), n = first.cloneNode(true);
      n.querySelectorAll('input, textarea').forEach(function (i) { i.value = ''; }); list.appendChild(n);
    });
  });
  document.addEventListener('click', function (e) {
    var rm = e.target.closest('.rm'); if (!rm) return;
    var rep = rm.closest('.rep'), list = rep.parentNode;
    if (list.querySelectorAll('.rep').length > 1) rep.remove(); else rep.querySelectorAll('input, textarea').forEach(function (i) { i.value = ''; });
  });
</script>
<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
