<?php
function sanitize_input($value)
{
    if (is_array($value)) {
        $clean = [];
        foreach ($value as $k => $v) {
            $clean[sanitize_input($k)] = sanitize_input($v);
        }
        return $clean;
    }
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

function get_real_ip()
{
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = trim($_SERVER['HTTP_CF_CONNECTING_IP']);
        if (filter_var($ip, FILTER_VALIDATE_IP))
            return $ip;
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function set_security_headers()
{
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data:; connect-src 'self' https://open.kickbox.com https://raw.githubusercontent.com; frame-ancestors 'none';");
    header('X-XSS-Protection: 1; mode=block');
}

function check_rate_limit($pdo, $action, $max_attempts = 10, $window_minutes = 15)
{
    $ip = get_real_ip();
    try {
        $pdo->prepare("DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)")->execute([$window_minutes]);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rate_limits WHERE action = ? AND ip = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)");
        $stmt->execute([$action, $ip, $window_minutes]);
        $count = (int) $stmt->fetchColumn();
        if ($count >= $max_attempts) {
            http_response_code(429);
            echo json_encode(['status' => 'error', 'message' => 'Too many attempts. Please wait a few minutes and try again.']);
            exit;
        }
        $pdo->prepare("INSERT INTO rate_limits (action, ip, attempts, created_at) VALUES (?, ?, 1, NOW())")->execute([$action, $ip]);
    } catch (PDOException $e) {
        error_log('Rate limit check failed: ' . $e->getMessage());
    }
}

function log_security_event($pdo, $email, $action, $user_id = null)
{
    $ip = get_real_ip();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    try {
        $stmt = $pdo->prepare("INSERT INTO security_logs (user_id, email, action, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $email, $action, $ip, $user_agent]);
    } catch (Exception $e) {
        error_log('Security log failed: ' . $e->getMessage());
    }
}

function verify_ownership($pdo, $table, $id, $user_id, $action)
{
    if (!$id)
        return false;
    $allowed_tables = ['expenses', 'user_categories', 'user_notes', 'savings_goals', 'savings_transactions', 'category_monthly_budgets'];
    if (!in_array($table, $allowed_tables, true))
        return false;
    try {
        $stmt = $pdo->prepare("SELECT user_id FROM `{$table}` WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row)
            return false;
        if (intval($row['user_id']) !== intval($user_id)) {
            log_security_event($pdo, $_SESSION['user_email'] ?? 'unknown', "idor_tampering_{$table}_{$action}", $user_id);
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            if (session_status() === PHP_SESSION_ACTIVE)
                session_destroy();
            $is_json = (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
                || strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false
                || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest'));
            $base_url = defined('BASE_URL') ? BASE_URL : '/';
            if ($is_json) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Security Violation: Tampering detected. Session terminated.', 'redirect' => $base_url . 'error.php?code=idor']);
                exit;
            }
            header('Location: ' . $base_url . 'error.php?code=idor');
            exit;
        }
        return true;
    } catch (PDOException $e) {
        error_log("verify_ownership error: " . $e->getMessage());
        return false;
    }
}

function verify_decoded_id($pdo, $token, $action)
{
    if (!function_exists('decode_id'))
        require_once __DIR__ . '/id_obfuscate.php';
    $uid = decode_id($token);
    if ($uid === null) {
        log_security_event($pdo, $_SESSION['user_email'] ?? 'unknown', "signature_tampering_{$action}");
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        if (session_status() === PHP_SESSION_ACTIVE)
            session_destroy();
        $is_json = (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
            || strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false
            || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest'));
        $base_url = defined('BASE_URL') ? BASE_URL : '/';
        if ($is_json) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Security Violation: Signature verification failed.', 'redirect' => $base_url . 'error.php?code=invalid_id']);
            exit;
        }
        header('Location: ' . $base_url . 'error.php?code=invalid_id');
        exit;
    }
    return $uid;
}

function is_disposable_email($email)
{
    static $blocked_domains = null;

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $parts = explode('@', $email);
    $domain = strtolower(trim(end($parts)));

    if ($blocked_domains === null) {
        $blocked_domains = [];
        $cache_file = __DIR__ . '/disposable_domains.txt';
        if (file_exists($cache_file)) {
            $content = file_get_contents($cache_file);
            if ($content !== false) {
                $lines = explode("\n", str_replace("\r", "", $content));
                foreach ($lines as $line) {
                    $trimmed = strtolower(trim($line));
                    if (!empty($trimmed)) {
                        $blocked_domains[$trimmed] = true;
                    }
                }
            }
        }
    }

    // 1. Check local blocklist
    if (isset($blocked_domains[$domain])) {
        return true;
    }

    // 2. DNS check with Self-healing mechanism
    $dns_functional = @checkdnsrr('gmail.com', 'MX');
    if ($dns_functional) {
        if (!@checkdnsrr($domain, 'MX')) {
            return true;
        }
    }

    return false;
}

