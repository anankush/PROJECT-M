<?php
// ============================================================
// PROJECT M: Central Configuration
// Shared by root gateway, PROJECT E, and PROJECT S
// ============================================================

date_default_timezone_set('Asia/Kolkata');

// Environment helper — reads OS env vars
function env(string $key, string $default = ''): string {
    return getenv($key) ?: $default;
}

// Load secrets injected by GitHub Actions CI/CD (production)
$secrets_file = __DIR__ . '/secrets.php';
if (file_exists($secrets_file)) {
    require_once $secrets_file;
}

// Base URL detection — local dev vs InfinityFree live
$http_host = strtolower($_SERVER['HTTP_HOST'] ?? '');
$is_local   = ($http_host === 'localhost' || $http_host === '127.0.0.1' || str_contains($http_host, 'localhost'));

// Database credentials — auto-detect local vs live
define('DB_HOST', defined('DB_HOST') ? DB_HOST : env('DB_HOST', '127.0.0.1'));
define('DB_NAME', defined('DB_NAME') ? DB_NAME : env('DB_NAME', $is_local ? 'expense_management' : 'if0_41843901_money_management'));
define('DB_USER', defined('DB_USER') ? DB_USER : env('DB_USER', 'root'));
define('DB_PASS', defined('DB_PASS') ? DB_PASS : env('DB_PASS', ''));

// APP_SECRET: used to sign sessions, CSRF tokens, and encoded IDs
$app_secret = defined('APP_SECRET_VAL') ? APP_SECRET_VAL : env('APP_SECRET');
if (empty($app_secret)) {
    $secret_file = __DIR__ . '/.secret';
    if (file_exists($secret_file)) {
        $app_secret = trim(file_get_contents($secret_file));
    }
    if (empty($app_secret)) {
        $app_secret = bin2hex(random_bytes(32));
        file_put_contents($secret_file, $app_secret, LOCK_EX);
    }
}
define('APP_SECRET', $app_secret);

// Session and security config
define('SESSION_LIFETIME', 900);       // 15 minutes idle timeout
define('MAX_LOGIN_ATTEMPTS', 5);       // Brute-force lockout threshold
define('LOCKOUT_TIME', 900);           // Lockout duration in seconds

// Google Apps Script URL for OTP emails (shared from PROJECT E)
define('GOOGLE_SCRIPT_URL', 'https://script.google.com/macros/s/AKfycbyIhD4yG4OlABSXFKvoCDtarvoX-dILBJH2_Mdt-5mJ6B4vhITeoUh2GNfeb2OZg682eQ/exec');

// ROOT BASE URL — the PROJECT M root (login.php, dashboard.php live here)
define('ROOT_BASE_URL', $is_local ? '/PROJECT M/' : '/');

// Module base URLs
define('BASE_URL_E', $is_local ? '/PROJECT M/PROJECT E/' : '/PROJECT E/');
define('BASE_URL_S', $is_local ? '/PROJECT M/PROJECT S/' : '/PROJECT S/');

// Live domain whitelist
define('ALLOWED_LIVE_HOST', 'moneymgmt.is-best.net');
