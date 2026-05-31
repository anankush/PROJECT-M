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
        global $pdo;
        if (isset($pdo)) {
            log_security_event($pdo, $_SESSION['user_email'] ?? 'unknown', 'csrf_validation_failed', $_SESSION['user_id'] ?? null);
        }
        
        // Terminate session securely
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        http_response_code(403);
        
        $is_json = (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false 
                 || strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false 
                 || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest'));

        $base_url = defined('BASE_URL') ? BASE_URL : '/';
        if ($is_json) {
            echo json_encode([
                'status' => 'error', 
                'message' => 'Verification Failed: Session expired or invalid request signature.',
                'redirect' => $base_url . 'error.php?code=csrf'
            ]);
        } else {
            header('Location: ' . $base_url . 'error.php?code=csrf');
        }
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
