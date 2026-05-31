<?php
// includes/csrf.php
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (empty($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
        exit;
    }
}

function get_csrf_meta_tag() {
    return '<meta name="csrf-token" content="' . htmlspecialchars(generate_csrf_token()) . '">';
}

/**
 * Outputs a <meta> tag containing the CSRF-protected logout URL.
 * Include this in <head> on all authenticated pages alongside get_csrf_meta_tag().
 * JS reads it via: document.querySelector('meta[name="logout-url"]').content
 *
 * @param string $base_path  Relative path prefix to auth/ directory (e.g. '../', '../../')
 */
function get_logout_meta_tag($base_path = '../') {
    $token = $_SESSION['logout_token'] ?? '';
    $url   = htmlspecialchars($base_path . 'auth/logout.php?token=' . urlencode($token));
    return '<meta name="logout-url" content="' . $url . '">';
}
