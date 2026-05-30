<?php
// includes/auth_check.php
require_once __DIR__ . '/db.php';

function session_start_secure() {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
             || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    // PHP 7.2 compatible (no array syntax for session_set_cookie_params)
    session_set_cookie_params(
        SESSION_LIFETIME,       // lifetime
        '/',                    // path
        '',                     // domain
        $is_https,              // secure
        true                    // httponly
    );
    session_name('PROJECTM_SID');
    session_start();
}

session_start_secure();

function check_session_timeout() {
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
        return;
    }
    if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        // Check if this is an API request
        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
         || strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Session expired']);
            exit;
        }
        header('Location: ' . BASE_URL . 'auth/login.php?error=session_expired');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function require_login() {
    if (empty($_SESSION['user_id']) && empty($_SESSION['admin_id'])) {
        // Check if API request
        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }
    check_session_timeout();
}

function require_admin() {
    if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header('Location: ' . BASE_URL . 'auth/admin_login.php');
        exit;
    }
    check_session_timeout();
}
