  </main>
</div>
<div class="toast" id="toast" hidden></div>
<script>
  // Tombol salin link (dipakai di beberapa halaman)
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-copy]'); if (!b) return;
    var t = b.dataset.copy, done = function () { var el = document.getElementById('toast'); el.textContent = 'Link tersalin'; el.hidden = false; setTimeout(function () { el.hidden = true; }, 1800); };
    if (navigator.clipboard) navigator.clipboard.writeText(t).then(done, function () {}); else { var ta = document.createElement('textarea'); ta.value = t; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove(); done(); }
  });
  document.querySelectorAll('form[data-confirm]').forEach(function (f) { f.addEventListener('submit', function (e) { if (!confirm(f.dataset.confirm)) e.preventDefault(); }); });
</script>
</body>
</html>
