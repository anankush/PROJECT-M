<?php
// ============================================================
// PROJECT M: Central Security Module
// Session management, CSRF, rate limiting, sanitization
// ============================================================

require_once __DIR__ . '/config.php';

// ── Session ─────────────────────────────────────────────────

function session_start_secure(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;

    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $is_https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function check_session_timeout(): void {
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
        return;
    }

    if ((time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        $is_api = isset($_SERVER['HTTP_X_CSRF_TOKEN'])
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
        if ($is_api) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Session expired']);
            exit;
        }
        header('Location: ' . ROOT_BASE_URL . 'login.php?error=session_expired');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function regenerate_session(): void {
    session_regenerate_id(true);
}

// ── CSRF ─────────────────────────────────────────────────────

function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(string $token): void {
    if (empty($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token. Please refresh and try again.']);
        exit;
    }
}

function get_csrf_meta_tag(): string {
    return '<meta name="csrf-token" content="' . htmlspecialchars(generate_csrf_token()) . '">';
}

// ── Sanitization ─────────────────────────────────────────────

function sanitize_input(mixed $value): mixed {
    if (is_array($value)) {
        return array_map('sanitize_input', $value);
    }
    $value = trim((string)$value);
    if (function_exists('mb_check_encoding') && !mb_check_encoding($value, 'UTF-8')) {
        $value = mb_convert_encoding($value, 'UTF-8', 'auto');
    }
    return htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
}

// ── Rate Limiting ─────────────────────────────────────────────

define('RATE_LIMIT_DIR', sys_get_temp_dir() . '/project_m_ratelimits');

function _rate_limit_can_use_file(): bool {
    static $result = null;
    if ($result !== null) return $result;
    @mkdir(RATE_LIMIT_DIR, 0700, true);
    $test = RATE_LIMIT_DIR . '/.test';
    $result = (@file_put_contents($test, 'x', LOCK_EX) !== false);
    if ($result) @unlink($test);
    return $result;
}

function _rl_path(string $action, string $ip): string {
    return RATE_LIMIT_DIR . '/' . md5($action . '_' . $ip) . '.json';
}

function _read_rate_limit(string $action, string $ip): ?array {
    if (!_rate_limit_can_use_file()) return _db_read_rl($action, $ip);
    $path = _rl_path($action, $ip);
    if (!file_exists($path)) return null;
    $data = json_decode(file_get_contents($path), true);
    if (!$data || (time() - ($data['time'] ?? 0)) > LOCKOUT_TIME * 2) { @unlink($path); return null; }
    return $data;
}

function _write_rate_limit(string $action, string $ip, array $data): void {
    if (!_rate_limit_can_use_file()) { _db_write_rl($action, $ip, $data); return; }
    @file_put_contents(_rl_path($action, $ip), json_encode($data), LOCK_EX);
}

function _db_read_rl(string $action, string $ip): ?array {
    try {
        global $pdo;
        if (!$pdo) return null;
        $stmt = $pdo->prepare("SELECT attempts, created_at FROM rate_limits WHERE action = ? AND ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)");
        $stmt->execute([$action, $ip, LOCKOUT_TIME * 2]);
        $row = $stmt->fetch();
        return $row ? ['attempts' => (int)$row['attempts'], 'time' => strtotime($row['created_at'])] : null;
    } catch (Exception) { return null; }
}

function _db_write_rl(string $action, string $ip, array $data): void {
    try {
        global $pdo;
        if ($pdo) $pdo->prepare("REPLACE INTO rate_limits (action, ip, attempts, created_at) VALUES (?, ?, ?, FROM_UNIXTIME(?))")
            ->execute([$action, $ip, $data['attempts'], $data['time']]);
    } catch (Exception) {}
}

function check_rate_limit(string $action, string $ip, int $max = MAX_LOGIN_ATTEMPTS): bool {
    $data = _read_rate_limit($action, $ip);
    if (!$data) return true;
    if ($data['attempts'] >= $max) {
        if ((time() - $data['time']) < LOCKOUT_TIME) return false;
        clear_attempts($action, $ip);
    }
    return true;
}

function record_attempt(string $action, string $ip): void {
    $data = _read_rate_limit($action, $ip);
    _write_rate_limit($action, $ip, $data ? ['attempts' => $data['attempts'] + 1, 'time' => $data['time']] : ['attempts' => 1, 'time' => time()]);
}

function clear_attempts(string $action, string $ip): void {
    if (!_rate_limit_can_use_file()) {
        try { global $pdo; if ($pdo) $pdo->prepare("DELETE FROM rate_limits WHERE action = ? AND ip = ?")->execute([$action, $ip]); } catch (Exception) {}
        return;
    }
    $path = _rl_path($action, $ip);
    if (file_exists($path)) @unlink($path);
}

// ── Password Helpers ──────────────────────────────────────────

function hash_password(string $plain): string {
    return password_hash($plain, PASSWORD_DEFAULT);
}

function verify_password(string $plain, string $hash): bool|string {
    if (password_verify($plain, $hash)) return true;
    if ($plain === $hash) return 'needs_upgrade'; // plain-text upgrade path
    return false;
}

function upgrade_password(int $user_id, string $plain, PDO $pdo, string $role = 'user'): void {
    $table = $role === 'admin' ? 'admin_users' : 'users';
    $pdo->prepare("UPDATE {$table} SET password = ? WHERE id = ?")
        ->execute([hash_password($plain), $user_id]);
}

// ── Email via Google Apps Script ──────────────────────────────

function send_email(string $to, string $subject, string $body): bool {
    if (empty(GOOGLE_SCRIPT_URL)) return false;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => GOOGLE_SCRIPT_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_POSTFIELDS     => http_build_query(['email' => $to, 'subject' => $subject, 'body' => $body]),
    ]);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_exec($ch);
    curl_close($ch);
    return empty($err) && $code < 400;
}

// ── Domain Access Guard ───────────────────────────────────────

function validate_domain_access(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    $host = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
    if ($host === 'localhost' || $host === '127.0.0.1' || str_contains($host, 'localhost')) return;
    if ($host !== ALLOWED_LIVE_HOST) {
        http_response_code(404);
        $f = __DIR__ . '/../404.php';
        if (file_exists($f)) require $f; else echo '404 Not Found';
        exit;
    }
}

// ── Security Headers ──────────────────────────────────────────

function set_security_headers(): void {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://unpkg.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; frame-ancestors 'none';");
    header('X-XSS-Protection: 1; mode=block');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

// ── ID Encoding (tamper-proof) ────────────────────────────────

function encode_id(int $id): string {
    $id   = (string)$id;
    $hmac = hash_hmac('sha256', $id, APP_SECRET);
    return $id . '.' . $hmac;
}

function decode_id(string $token): ?int {
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2 || !is_numeric($parts[0])) return null;
    return hash_equals(hash_hmac('sha256', $parts[0], APP_SECRET), $parts[1]) ? (int)$parts[0] : null;
}
