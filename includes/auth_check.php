<?php
require_once __DIR__ . '/db.php';

function session_start_secure() {
    if (session_status() === PHP_SESSION_ACTIVE) return;

    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
             || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
             || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
             || (!empty($_SERVER['HTTP_X_FORWARDED_PORT']) && $_SERVER['HTTP_X_FORWARDED_PORT'] == 443);

    session_set_cookie_params(SESSION_LIFETIME, '/', '', $is_https, true);
    session_name('PROJECTM_SID');
    session_start();

    if (isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])) {
        global $pdo;
        $session_invalid = false;
        $db_sess_id = null;
        if (isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("SELECT active_session_id FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $db_sess_id = $stmt->fetchColumn();
            if ($db_sess_id !== session_id()) {
                $session_invalid = true;
            }
        } elseif (isset($_SESSION['admin_id'])) {
            $stmt = $pdo->prepare("SELECT active_session_id FROM admin_users WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id']]);
            $db_sess_id = $stmt->fetchColumn();
            if ($db_sess_id !== session_id()) {
                $session_invalid = true;
            }
        }

        $log_msg = sprintf("[%s] URI: %s | User: %s | Session: %s | DB Session: %s | Invalid: %s | UA: %s\n",
            date('Y-m-d H:i:s'),
            $_SERVER['REQUEST_URI'] ?? '',
            $_SESSION['user_id'] ?? 'none',
            session_id(),
            $db_sess_id ?? 'null',
            $session_invalid ? 'YES' : 'NO',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );
        file_put_contents(__DIR__ . '/../session_debug.log', $log_msg, FILE_APPEND);

        if ($session_invalid) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
            if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
             || strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
                http_response_code(401);
                echo json_encode(['status' => 'error', 'message' => 'Concurrent login detected. Session terminated.', 'redirect' => BASE_URL . 'error.php?code=concurrent_login']);
                exit;
            }
            header('Location: ' . BASE_URL . 'error.php?code=concurrent_login');
            exit;
        }

        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ip_parts = explode('.', $ip);
        $ip_subnet = (count($ip_parts) >= 2) ? $ip_parts[0] . '.' . $ip_parts[1] : $ip;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (!isset($_SESSION['secure_user_agent'])) {
            $_SESSION['secure_user_agent'] = $user_agent;
        } else {
            if ($_SESSION['secure_user_agent'] !== $user_agent) {
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
                header('Location: ' . BASE_URL . 'error.php?code=security');
                exit;
            }
        }
    }
}

session_start_secure();

function generate_ott($module) {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start_secure();
    $token = bin2hex(random_bytes(16));
    $_SESSION[$module . '_ott'] = ['token' => $token, 'created_at' => time()];
    return $token;
}

function validate_ott($module, $token) {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start_secure();
    if (!isset($_SESSION[$module . '_ott'])) return false;
    $stored = $_SESSION[$module . '_ott'];
    if ($stored['token'] === $token && (time() - $stored['created_at'] <= 15)) {
        unset($_SESSION[$module . '_ott']);
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
        $role = $_SESSION['role'] ?? 'user';
        session_unset();
        session_destroy();
        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
         || strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Session expired']);
            exit;
        }
        $login_page = ($role === 'admin') ? 'auth/admin_login.php' : 'auth/login.php';
        header('Location: ' . BASE_URL . $login_page . '?error=session_expired');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function require_login() {
    if (empty($_SESSION['user_id'])) {
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
    $is_json = (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
             || strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false
             || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest'));

    if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        if (!empty($_SESSION['user_id'])) {
            global $pdo;
            log_security_event($pdo, $_SESSION['user_email'] ?? 'unknown', 'admin_privilege_escalation_attempt', $_SESSION['user_id']);
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
            if ($is_json) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Access denied. Administrative privileges required.', 'redirect' => BASE_URL . 'error.php?code=admin_required']);
                exit;
            }
            header('Location: ' . BASE_URL . 'error.php?code=admin_required');
            exit;
        }
        if ($is_json) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Admin login required.', 'redirect' => BASE_URL . 'auth/admin_login.php']);
            exit;
        }
        header('Location: ' . BASE_URL . 'auth/admin_login.php');
        exit;
    }
    check_session_timeout();
}

function get_logout_url($base_path = '../') {
    $token = $_SESSION['logout_token'] ?? '';
    return $base_path . 'auth/logout.php?token=' . urlencode($token);
}
