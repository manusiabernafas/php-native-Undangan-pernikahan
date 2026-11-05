<?php
// File kalender agar tamu bisa menyimpan jadwal ke Google Calendar / kalender HP
[$c1, $c2] = pasangan($a);
$jenis = ($_GET['ics'] ?? '') === 'resepsi' && $a['resepsi_waktu'] ? 'resepsi' : 'akad';
$mulai = strtotime($a[$jenis . '_waktu']);
$selesaiJam = $a[$jenis . '_selesai'];
$akhir = $selesaiJam ? strtotime(date('Y-m-d', $mulai) . ' ' . $selesaiJam) : $mulai + 2 * 3600;
$fmt = fn ($t) => gmdate('Ymd\THis\Z', $t);
$esc = fn ($s) => str_replace(["\\", ';', ',', "\n"], ["\\\\", '\;', '\,', '\n'], (string) $s);
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $a['slug'] . '-' . $jenis . '.ics"');
echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Undangan Digital//ID\r\nBEGIN:VEVENT\r\n",
    'UID:', $a['slug'], '-', $jenis, '@undangan', "\r\n",
    'DTSTAMP:', $fmt(time()), "\r\n", 'DTSTART:', $fmt($mulai), "\r\n", 'DTEND:', $fmt($akhir), "\r\n",
    'SUMMARY:', $esc(($jenis === 'akad' ? 'Akad Nikah ' : 'Resepsi ') . $c1 . ' & ' . $c2), "\r\n",
    'LOCATION:', $esc($a[$jenis . '_tempat'] . ', ' . $a[$jenis . '_alamat']), "\r\n",
    'DESCRIPTION:', $esc('Lokasi: ' . ($a['maps_url'] ?: '-')), "\r\n",
    "BEGIN:VALARM\r\nTRIGGER:-P1D\r\nACTION:DISPLAY\r\nDESCRIPTION:Besok\r\nEND:VALARM\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
