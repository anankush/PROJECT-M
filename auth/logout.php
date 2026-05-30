<?php
// ProjectM/auth/logout.php
require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
set_security_headers();

// Clear all session data
$_SESSION = [];

// Delete the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();
header('Location: login.php');
exit;
