<?php
// Sav/dashboard.php
require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_login();

$ott = $_GET['ott'] ?? '';
header('Location: user/index.php' . ($ott ? '?ott=' . urlencode($ott) : ''));
exit;
