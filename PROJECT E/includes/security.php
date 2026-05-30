<?php
require_once __DIR__ . '/config.php';

function session_start_secure()
{
    if (session_status() === PHP_SESSION_ACTIVE)
        return;

    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    $params = [
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => $is_https,
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    session_set_cookie_params($params);
    session_start();
}

function check_session_timeout()
{
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
        return;
    }

    $inactive_time = time() - $_SESSION['last_activity'];
    if ($inactive_time > SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Session expired']);
            exit;
        }
        // Redirect to central PROJECT M login
        header('Location: ../../login.php?error=session_expired');
        exit;
    }

    $_SESSION['last_activity'] = time();
}

function regenerate_session()
{
    session_regenerate_id(true);
}

function generate_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token)
{
    if (empty($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Your session has expired.Try again']);
        exit;
    }
}

function get_csrf_meta_tag()
{
    $token = generate_csrf_token();
    return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
}

function sanitize_input($value)
{
    if (is_array($value)) {
        return sanitize_array($value);
    }
    $value = trim($value);
    if (function_exists('mb_check_encoding') && function_exists('mb_convert_encoding')) {
        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'auto');
        }
    }
    return htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
}

function sanitize_array($arr)
{
    $clean = [];
    foreach ($arr as $key => $value) {
        $clean[sanitize_input($key)] = sanitize_input($value);
    }
    return $clean;
}

define('RATE_LIMIT_DIR', sys_get_temp_dir() . '/project_e_ratelimits');
global $_rate_limit_use_file;
$_rate_limit_use_file = null;

function _rate_limit_can_use_file()
{
    global $_rate_limit_use_file;
    if ($_rate_limit_use_file !== null) return $_rate_limit_use_file;
    $test_file = RATE_LIMIT_DIR . '/.test';
    @mkdir(RATE_LIMIT_DIR, 0700, true);
    $_rate_limit_use_file = (@file_put_contents($test_file, 'test', LOCK_EX) !== false);
    if ($_rate_limit_use_file) @unlink($test_file);
    return $_rate_limit_use_file;
}

function _get_rate_limit_path($action, $ip)
{
    return RATE_LIMIT_DIR . '/' . md5($action . '_' . $ip) . '.json';
}

function _read_rate_limit($action, $ip)
{
    if (!_rate_limit_can_use_file()) return _db_read_rate_limit($action, $ip);
    $path = _get_rate_limit_path($action, $ip);
    if (!file_exists($path)) return null;
    $data = json_decode(file_get_contents($path), true);
    if (!$data) return null;
    if (time() - ($data['time'] ?? 0) > LOCKOUT_TIME * 2) {
        @unlink($path);
        return null;
    }
    return $data;
}

function _write_rate_limit($action, $ip, $data)
{
    if (!_rate_limit_can_use_file()) { _db_write_rate_limit($action, $ip, $data); return; }
    $path = _get_rate_limit_path($action, $ip);
    @file_put_contents($path, json_encode($data), LOCK_EX);
}

function _db_read_rate_limit($action, $ip)
{
    try {
        global $pdo;
        if (!$pdo) return null;
        $stmt = $pdo->prepare("SELECT attempts, created_at FROM rate_limits WHERE action = ? AND ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)");
        $stmt->execute([$action, $ip, LOCKOUT_TIME * 2]);
        $row = $stmt->fetch();
        if (!$row) return null;
        return ['attempts' => (int)$row['attempts'], 'time' => strtotime($row['created_at'])];
    } catch (Exception $e) {
        return null;
    }
}

function _db_write_rate_limit($action, $ip, $data)
{
    try {
        global $pdo;
        if (!$pdo) return;
        $pdo->prepare("REPLACE INTO rate_limits (action, ip, attempts, created_at) VALUES (?, ?, ?, FROM_UNIXTIME(?))")
            ->execute([$action, $ip, $data['attempts'], $data['time']]);
    } catch (Exception $e) {
    }
}

function check_rate_limit($action, $ip, $max_attempts = MAX_LOGIN_ATTEMPTS)
{
    $data = _read_rate_limit($action, $ip);
    if (!$data) return true;

    if ($data['attempts'] >= $max_attempts) {
        if (time() - $data['time'] < LOCKOUT_TIME) {
            return false;
        }
        clear_attempts($action, $ip);
        return true;
    }

    return true;
}

function record_attempt($action, $ip)
{
    $data = _read_rate_limit($action, $ip);
    if (!$data) {
        _write_rate_limit($action, $ip, ['attempts' => 1, 'time' => time()]);
    } else {
        $data['attempts']++;
        _write_rate_limit($action, $ip, $data);
    }
}

function clear_attempts($action, $ip)
{
    if (!_rate_limit_can_use_file()) {
        try {
            global $pdo;
            if ($pdo) $pdo->prepare("DELETE FROM rate_limits WHERE action = ? AND ip = ?")->execute([$action, $ip]);
        } catch (Exception $e) {}
        return;
    }
    $path = _get_rate_limit_path($action, $ip);
    if (file_exists($path)) @unlink($path);
}

function send_email($to, $subject, $body)
{
    $url = GOOGLE_SCRIPT_URL;
    if (empty($url)) return false;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_POSTFIELDS => http_build_query([
            'email' => $to,
            'subject' => $subject,
            'body' => $body
        ])
    ]);
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("Email send failed: $error");
        return false;
    }
    if ($http_code >= 400) {
        error_log("Email send HTTP error: $http_code");
        return false;
    }
    return true;
}

function encode_id($id)
{
    $id = (string) (int) $id;
    $hmac = hash_hmac('sha256', $id, APP_SECRET);
    return $id . '.' . $hmac;
}

function decode_id($token)
{
    if (empty($token) || !is_string($token)) return null;
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) return null;
    [$id, $hmac] = $parts;
    if (!is_numeric($id)) return null;
    $expected = hash_hmac('sha256', $id, APP_SECRET);
    if (!hash_equals($expected, $hmac)) return null;
    return (int) $id;
}

function validate_domain_access()
{
    // Domain restriction removed for live server compatibility
    return;
}

function validate_url_access($allowed_params = [])
{
    $base = BASE_URL;
    $safe_params = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'fbclid', 'gclid', 'ref', 'source'];
    $allowed = array_merge($allowed_params, $safe_params);

    if (!empty($_GET)) {
        foreach (array_keys($_GET) as $key) {
            if (!in_array($key, $allowed)) {
                header('Location: ' . $base . '404.php');
                exit;
            }
        }
    }
}

function validate_email_domain($email)
{
    $allowed_domains = [
        'gmail.com',
        'outlook.com',
        'hotmail.com',
        'yahoo.com',
        'yahoo.co.in',
        'protonmail.com',
        'proton.me',
        'icloud.com',
        'me.com',
        'mac.com',
        'live.com',
        'msn.com',
        'aol.com',
        'zoho.com',
        'mail.com',
        'yandex.com',
        'gmx.com',
        'fastmail.com',
    ];

    $domain = strtolower(substr(strrchr($email, '@'), 1));
    return in_array($domain, $allowed_domains);
}

function hash_password($plain)
{
    return password_hash($plain, PASSWORD_DEFAULT);
}

function verify_password($plain, $hash)
{
    if (password_verify($plain, $hash)) {
        return true;
    }
    if ($plain === $hash) {
        return 'needs_upgrade';
    }
    return false;
}

function upgrade_password($user_id, $plain, $pdo, $role = 'user')
{
    $new_hash = hash_password($plain);
    $table = $role === 'admin' ? 'admin_users' : 'users';
    $stmt = $pdo->prepare("UPDATE {$table} SET password = ? WHERE id = ?");
    $stmt->execute([$new_hash, $user_id]);
}

function set_security_headers()
{
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; frame-ancestors 'none';");
    header('X-XSS-Protection: 1; mode=block');
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}
