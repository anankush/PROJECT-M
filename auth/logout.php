<?php
require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
set_security_headers();

$provided_token = $_GET['token'] ?? '';
$stored_token   = $_SESSION['logout_token'] ?? '';

if (empty($stored_token) || !hash_equals($stored_token, $provided_token)) {
    $redirect = isset($_SESSION['admin_id']) ? '../admin/index.php' : '../dashboard/index.php';
    header('Location: ' . $redirect);
    exit;
}

$was_admin = isset($_SESSION['admin_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

header('Location: ' . ($was_admin ? 'admin_login.php' : 'login.php'));
exit;
