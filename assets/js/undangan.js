(function () {
  'use strict';
  var U = window.UND, $ = function (s) { return document.querySelector(s); };
  var esc = function (s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  function toast(t) { var el = $('#toast'); el.textContent = t; el.hidden = false; clearTimeout(toast.t); toast.t = setTimeout(function () { el.hidden = true; }, 2200); }
  function post(data) {
    var fd = new FormData(); fd.append('u', U.slug); fd.append('k', U.kode); fd.append('_csrf', U.csrf);
    Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
    return fetch('api.php', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); });
  }

  // Buka undangan + musik
  var music = $('#music'), btnMusic = $('#btnMusic');
  function setMusic(on) { if (!music) return; if (on) music.play().catch(function () {}); else music.pause(); btnMusic.classList.toggle('on', on); }
  $('#btnOpen').addEventListener('click', function () {
    $('#cover').classList.add('open'); document.body.classList.remove('is-locked');
    if (music) { btnMusic.hidden = false; setMusic(true); }
    setTimeout(function () { $('#cover').style.display = 'none'; }, 950);
  });
  if (btnMusic) btnMusic.addEventListener('click', function () { setMusic(music.paused); });

  // Animasi muncul saat discroll
  var io = 'IntersectionObserver' in window ? new IntersectionObserver(function (es) { es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } }); }, { threshold: .15 }) : null;
  document.querySelectorAll('.reveal').forEach(function (el) { io ? io.observe(el) : el.classList.add('in'); });

  // Hitung mundur
  var cd = $('#countdown');
  if (cd) {
    var tgt = +cd.dataset.t, tick = function () {
      var d = Math.max(0, tgt - Date.now()) / 1000;
      [['d', 86400], ['h', 3600], ['m', 60], ['s', 1]].forEach(function (u, i, arr) {
        var v = Math.floor(i ? (d % arr[i - 1][1]) / u[1] : d / u[1]); cd.querySelector('[data-u=' + u[0] + ']').textContent = v;
      });
    };
    tick(); setInterval(tick, 1000);
  }

  // Galeri lightbox
  var lb = $('#lightbox');
  document.querySelectorAll('.grid .g').forEach(function (g) { g.addEventListener('click', function () { lb.querySelector('img').src = g.dataset.src; lb.hidden = false; }); });
  lb.addEventListener('click', function () { lb.hidden = true; });

  // Salin nomor rekening
  document.querySelectorAll('[data-copy]').forEach(function (b) {
    b.addEventListener('click', function () {
      var t = b.dataset.copy;
      (navigator.clipboard ? navigator.clipboard.writeText(t) : Promise.reject()).catch(function () {
        var ta = document.createElement('textarea'); ta.value = t; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove();
      }).then(function () { toast('Tersalin: ' + t); });
    });
  });

  function msg(el, text, err) { el.textContent = text; el.className = 'msg' + (err ? ' err' : ''); el.hidden = false; }

  // RSVP
  var fr = $('#formRsvp');
  if (fr) {
    var syncJumlah = function () { var v = (fr.querySelector('[name=kehadiran]:checked') || {}).value; $('#fJumlah').style.display = v === 'hadir' ? '' : 'none'; };
    fr.addEventListener('change', syncJumlah); syncJumlah();
    fr.addEventListener('submit', function (e) {
      e.preventDefault(); var btn = fr.querySelector('button'); btn.disabled = true;
      post({ action: 'rsvp', kehadiran: (fr.querySelector('[name=kehadiran]:checked') || {}).value || '', jumlah: fr.jumlah.value })
        .then(function (r) { msg($('#rsvpMsg'), r.msg, !r.ok); if (r.ok) btn.lastChild.textContent = ' Perbarui konfirmasi'; })
        .catch(function () { msg($('#rsvpMsg'), 'Koneksi bermasalah, coba lagi.', true); })
        .then(function () { btn.disabled = false; });
    });
  }

  // Ucapan: daftar + kirim + muat lebih banyak + cek ucapan baru tiap 20 detik
  var list = $('#wList'), more = $('#wMore'), oldest = 0, newest = 0;
  var lbl = { hadir: 'Hadir', tidak: 'Tidak hadir', ragu: 'Masih ragu' };
  function item(w, isNew) {
    return '<li' + (isNew ? ' class="new"' : '') + '><div class="wh"><b>' + esc(w.nama) + '</b>' + (w.kehadiran ? '<span class="badge ' + w.kehadiran + '">' + lbl[w.kehadiran] + '</span>' : '') +
      '</div><p>' + esc(w.isi).replace(/\n/g, '<br>') + '</p><time>' + esc(w.waktu) + '</time></li>';
  }
  function load(params) {
    return fetch('api.php?' + new URLSearchParams(Object.assign({ action: 'ucapan', u: U.slug }, params))).then(function (r) { return r.json(); });
  }
  load({}).then(function (r) {
    list.innerHTML = r.items.map(function (w) { return item(w); }).join('');
    if (r.items.length) { newest = r.items[0].id; oldest = r.items[r.items.length - 1].id; }
    more.hidden = !r.more;
  });
  more.addEventListener('click', function () {
    more.disabled = true;
    load({ before: oldest }).then(function (r) {
      list.insertAdjacentHTML('beforeend', r.items.map(function (w) { return item(w); }).join(''));
      if (r.items.length) oldest = r.items[r.items.length - 1].id; more.hidden = !r.more;
    }).then(function () { more.disabled = false; });
  });
  function poll() {
    if (document.hidden) return;
    load({ after: newest }).then(function (r) {
      if (!r.items.length) return;
      list.insertAdjacentHTML('afterbegin', r.items.map(function (w) { return item(w, true); }).join(''));
      newest = r.items[0].id; $('#wCount').textContent = r.stat.n; $('#wHadir').textContent = r.stat.hadir;
    });
  }
  setInterval(poll, 20000);

  var fu = $('#formUcapan');
  fu.addEventListener('submit', function (e) {
    e.preventDefault(); var btn = fu.querySelector('button'); btn.disabled = true;
    var d = { action: 'kirim_ucapan', isi: fu.isi.value };
    if (fu.nama) d.nama = fu.nama.value;
    if (fu.kehadiran) d.kehadiran = fu.kehadiran.value;
    else { var r0 = document.querySelector('#formRsvp [name=kehadiran]:checked'); if (r0) d.kehadiran = r0.value; }
    post(d).then(function (r) {
      msg($('#ucapanMsg'), r.msg, !r.ok);
      if (r.ok) { fu.isi.value = ''; poll(); }
    }).catch(function () { msg($('#ucapanMsg'), 'Koneksi bermasalah, coba lagi.', true); })
      .then(function () { btn.disabled = false; });
  });
})();
