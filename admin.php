<?php
// Panel admin: kelola undangan, tamu, RSVP, dan ucapan
require __DIR__ . '/includes/bootstrap.php';

$routes = [
    'login'      => 'pages/admin/login.php',
    'logout'     => 'pages/admin/logout.php',
    'dashboard'  => 'pages/admin/dashboard.php',
    'acara'      => 'pages/admin/acara.php',
    'tamu'       => 'pages/admin/tamu.php',
    'kirim'      => 'pages/admin/kirim.php',
    'ucapan'     => 'pages/admin/ucapan.php',
    'akun'       => 'pages/admin/akun.php',
];
$page = (string) ($_GET['page'] ?? 'dashboard');
if (!isset($routes[$page])) { http_response_code(404); $page = 'dashboard'; }
if ($page !== 'login' && !user()) redirect('login');
verify_csrf();
require __DIR__ . '/' . $routes[$page];
