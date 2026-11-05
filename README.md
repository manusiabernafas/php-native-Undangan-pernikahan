# Undangan Pernikahan Digital (PHP Native + MySQL)

Aplikasi undangan pernikahan digital berbasis PHP Native dan MySQL. Setiap tamu mendapat link pribadi yang menampilkan namanya di halaman sampul, lalu bisa melihat detail acara, membuka lokasi, menyimpan jadwal ke kalender, mengisi konfirmasi kehadiran, menulis ucapan, dan mengirim tanda kasih lewat amplop digital.

Dari panel admin, pengantin atau penyedia jasa undangan bisa mengelola banyak undangan sekaligus: menambah ratusan tamu dalam sekali tempel, mengirim undangan ke WhatsApp satu per satu dengan pesan siap kirim, memantau siapa yang sudah membuka undangan, dan mengetahui berapa orang yang akan hadir. Tampilannya elegan dengan huruf serif, ornamen garis, dan 4 pilihan palet warna yang tetap ringan dibuka dari HP.

## Cara kerja

Setiap undangan punya alamat link sendiri (contoh ?u=nadia-raka). Saat tamu ditambahkan, sistem membuat kode unik 6 karakter sehingga link pribadinya menjadi ?u=nadia-raka&k=KODE. Ketika link itu dibuka, nama tamu tampil di sampul dan waktu pertama dibuka serta jumlah kunjungan tercatat, sehingga admin bisa melihat status Terkirim, Dibuka, atau Belum dikirim untuk setiap tamu.

Tombol Kirim WA menyusun pesan dari template (dengan {nama}, {link}, {pasangan}, {tanggal}), menandai undangan sebagai terkirim, lalu membuka WhatsApp dengan pesan siap kirim. RSVP hanya bisa diisi lewat link pribadi dan disimpan dengan satu baris per tamu (bisa diubah kapan saja), jumlah orang dibatasi sesuai jatah tamu, dan RSVP ditutup setelah hari acara.

Ucapan dikirim dan dimuat lewat AJAX: daftar ucapan diperbarui otomatis setiap 20 detik, tamu terdaftar memakai nama dari database (tidak bisa diganti), dan ada batas 3 ucapan per 10 menit per IP serta penolakan ucapan dobel. Admin bisa menyembunyikan ucapan yang tidak pantas. Tombol Simpan ke Kalender menghasilkan file .ics lengkap dengan pengingat sehari sebelum acara.

## Fitur

- Banyak undangan dalam satu aplikasi, cocok juga untuk usaha jasa undangan digital
- Link personal per tamu dengan sapaan "Kepada Yth. [nama tamu]"
- Lacak siapa yang sudah membuka undangan dan berapa kali dibuka
- Kirim undangan ke WhatsApp tamu sekali klik dengan template pesan yang bisa diubah
- Status terkirim / dibuka / belum dikirim untuk setiap tamu
- Tambah tamu massal: tempel daftar nama dari Excel atau WhatsApp (nama, no. WA, grup, maks orang)
- Kelompok tamu (keluarga, teman kantor, dst) dan filter berdasarkan grup
- RSVP: hadir / ragu / tidak hadir dengan batas jumlah orang per tamu
- Rekap total orang yang akan hadir untuk perkiraan katering
- Ucapan & doa yang muncul otomatis tanpa reload, dengan tombol muat lebih banyak
- Anti spam ucapan: batas per IP, tolak ucapan dobel, nama tamu terdaftar tidak bisa dipalsukan
- Moderasi ucapan: sembunyikan atau hapus dari panel admin
- Sampul pembuka dengan musik latar (upload MP3 sendiri)
- Hitung mundur ke hari H
- Akad dan resepsi dengan tombol Google Maps dan Simpan ke Kalender (.ics)
- Profil mempelai dengan foto (atau inisial elegan jika tanpa foto) dan nama orang tua
- Cerita cinta (timeline) dan galeri foto dengan lightbox
- Amplop digital: rekening bank / e-wallet dengan tombol salin dan alamat kirim kado
- 4 palet warna: Sage, Terakota, Navy & Emas, Mauve
- Tampilan desktop dengan panel sampul tetap + kolom undangan, dan tampilan HP yang ringan
- Ekspor daftar tamu + link + RSVP ke CSV (Excel)
- Keamanan: PDO prepared statement, password_hash, CSRF, validasi tipe file upload, folder upload tanpa eksekusi script
- Data contoh 2 undangan, 160 tamu, RSVP, dan ucapan
- PHP Native tanpa framework & tanpa Composer, cukup XAMPP

## Instalasi

**Kebutuhan:** PHP 8.1 atau lebih baru dan MySQL/MariaDB (XAMPP atau Laragon).

1. Ekstrak ZIP lalu pindahkan folder `undangan` ke:
   - XAMPP: `C:\xampp\htdocs\undangan`
   - Laragon: `C:\laragon\www\undangan`

2. Jalankan **Apache** dan **MySQL**.

3. Buka `http://localhost/phpmyadmin` → **Import** → pilih `database/undangan_digital.sql` → **Import**. Tanggal acara contoh menyesuaikan hari saat import.

   Atau lewat terminal:
   ```bash
   mysql -u root -p < database/undangan_digital.sql
   ```

4. Sesuaikan `config/database.php` jika user/password MySQL kamu berbeda.

5. **Panel admin:** `http://localhost/undangan/admin.php`
   - Username: `admin`
   - Password: `admin123` (segera ganti di menu Akun)

6. **Contoh undangan:** `http://localhost/undangan/?u=nadia-raka` atau buka link pribadi salah satu tamu dari menu **Tamu & RSVP** (ikon link).

7. Agar link bisa dibuka tamu dari HP, upload ke hosting (folder `public_html/undangan` atau subdomain). Link tamu otomatis mengikuti alamat domain kamu.

**Tips:** naikkan `upload_max_filesize` dan `post_max_size` di php.ini (misal 20M) jika ingin mengunggah musik atau banyak foto sekaligus.

## Alamat penting

| Halaman | URL |
|---|---|
| Undangan umum | `/?u=slug` |
| Undangan pribadi | `/?u=slug&k=KODE` |
| Undangan umum dengan nama | `/?u=slug&to=Nama+Tamu` |
| Panel admin | `/admin.php` |

## Struktur folder

```
undangan/
├── index.php              Halaman undangan untuk tamu
├── api.php                RSVP & ucapan (AJAX)
├── admin.php              Panel admin
├── config/database.php    Koneksi MySQL
├── database/              File SQL + data contoh
├── includes/              Helper, header & footer admin
├── pages/                 Template undangan, .ics, halaman admin
└── assets/                CSS, JS, uploads (foto, galeri, musik)
```

Palet warna bisa ditambah di konstanta `PALET` pada `includes/bootstrap.php`.

---
Source code ini dibagikan gratis oleh **Papicode** — https://papicode.id
