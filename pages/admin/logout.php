<?php
$_SESSION = [];
session_destroy();
header('Location: admin.php?page=login');
exit;
