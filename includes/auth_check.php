<?php
// includes/auth_check.php
require_once __DIR__ . '/db.php';

function session_start_secure() {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    
    // Robust SSL detection including Cloudflare/InfinityFree reverse proxies
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
             || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
             || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
             || (!empty($_SERVER['HTTP_X_FORWARDED_PORT']) && $_SERVER['HTTP_X_FORWARDED_PORT'] == 443);

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

    // ── Session Hijacking Protection (IP Subnet & User-Agent checks) ──
    if (isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        // Extract first 2 octets for a relaxed subnet check to handle proxy IP changes smoothly (InfinityFree compatible)
        $ip_parts = explode('.', $ip);
        $ip_subnet = (count($ip_parts) >= 2) ? $ip_parts[0] . '.' . $ip_parts[1] : $ip;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (!isset($_SESSION['secure_subnet'])) {
            $_SESSION['secure_subnet'] = $ip_subnet;
            $_SESSION['secure_user_agent'] = $user_agent;
        } else {
            if ($_SESSION['secure_subnet'] !== $ip_subnet || $_SESSION['secure_user_agent'] !== $user_agent) {
                // Terminate session due to mismatch (potential session hijacking)
                $_SESSION = [];
                if (ini_get('session.use_cookies')) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
                }
                session_destroy();

                if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
                 || strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
                    http_response_code(401);
                    echo json_encode(['status' => 'error', 'message' => 'Security check failed. Session terminated.']);
                    exit;
                }
                header('Location: ' . BASE_URL . 'auth/login.php?error=security_breach');
                exit;
            }
        }
    }
}

session_start_secure();

// ── One-Time Token (OTT) helpers for Google-like secure sub-module tunnels ──
function generate_ott($module) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start_secure();
    }
    $token = bin2hex(random_bytes(16));
    $_SESSION[$module . '_ott'] = [
        'token' => $token,
        'created_at' => time()
    ];
    return $token;
}

function validate_ott($module, $token) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start_secure();
    }
    if (!isset($_SESSION[$module . '_ott'])) {
        return false;
    }
    $stored = $_SESSION[$module . '_ott'];
    // Valid for 15 seconds (extended from 5s to handle InfinityFree/slow-network latency)
    if ($stored['token'] === $token && (time() - $stored['created_at'] <= 15)) {
        unset($_SESSION[$module . '_ott']); // Single-use consumption
        return true;
    }
    return false;
}

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
    if (empty($_SESSION['user_id'])) {
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

/**
 * Returns a CSRF-protected logout URL with the session logout_token.
 * Usage in PHP pages: href="<?php echo get_logout_url('../'); ?>"
 *
 * @param string $base_path  Relative path to the auth/ directory (e.g. '../', '../../')
 * @return string  Full logout URL with token query param
 */
function get_logout_url($base_path = '../') {
    $token = $_SESSION['logout_token'] ?? '';
    return $base_path . 'auth/logout.php?token=' . urlencode($token);
}
