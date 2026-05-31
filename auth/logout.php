<?php
// ProjectM/auth/logout.php
require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
set_security_headers();

// SECURITY: CSRF-protected logout using a session-bound logout token.
// The token is set at login time and must be passed as ?token=... in the URL.
// This prevents malicious sites from force-logging out users via a crafted link.
$provided_token = $_GET['token'] ?? '';
$stored_token   = $_SESSION['logout_token'] ?? '';

if (empty($stored_token) || !hash_equals($stored_token, $provided_token)) {
    // Invalid or missing token — redirect to dashboard silently (do not logout)
    $redirect = isset($_SESSION['admin_id']) ? '../admin/index.php' : '../dashboard/index.php';
    header('Location: ' . $redirect);
    exit;
}

// Capture role BEFORE destroying session
$was_admin = isset($_SESSION['admin_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

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

// Redirect to the correct login page based on who logged out
header('Location: ' . ($was_admin ? 'admin_login.php' : 'login.php'));
exit;

