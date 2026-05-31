<?php
// Exp/dashboard.php
require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_login();

$query_string = $_SERVER['QUERY_STRING'] ?? '';
header('Location: user/index.php' . ($query_string ? '?' . $query_string : ''));
exit;
