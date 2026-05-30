<?php
// PROJECT E config — delegates to the central PROJECT M config
// This ensures DB credentials, APP_SECRET, BASE URLs, and session constants
// are all sourced from a single place.
$central_config = dirname(__DIR__, 2) . '/includes/config.php';
if (file_exists($central_config)) {
    require_once $central_config;
} else {
    // Fallback for standalone PROJECT E usage (local dev without PROJECT M root)
    date_default_timezone_set('Asia/Kolkata');

    function env($key, $default = '') {
        return getenv($key) ?: $default;
    }

    $secrets_file = __DIR__ . '/secrets.php';
    if (file_exists($secrets_file)) require_once $secrets_file;

    define('DB_HOST', defined('DB_HOST') ? DB_HOST : env('DB_HOST', '127.0.0.1'));
    define('DB_NAME', defined('DB_NAME') ? DB_NAME : env('DB_NAME', 'expense_management'));
    define('DB_USER', defined('DB_USER') ? DB_USER : env('DB_USER', 'root'));
    define('DB_PASS', defined('DB_PASS') ? DB_PASS : env('DB_PASS', ''));

    $app_secret = defined('APP_SECRET_VAL') ? APP_SECRET_VAL : env('APP_SECRET');
    if (empty($app_secret)) {
        $secret_file = __DIR__ . '/.secret';
        if (file_exists($secret_file)) $app_secret = trim(file_get_contents($secret_file));
        if (empty($app_secret)) { $app_secret = bin2hex(random_bytes(32)); file_put_contents($secret_file, $app_secret, LOCK_EX); }
    }
    define('APP_SECRET', $app_secret);
    define('SESSION_LIFETIME', 900);
    define('MAX_LOGIN_ATTEMPTS', 5);
    define('LOCKOUT_TIME', 900);
    define('GOOGLE_SCRIPT_URL', 'https://script.google.com/macros/s/AKfycbyIhD4yG4OlABSXFKvoCDtarvoX-dILBJH2_Mdt-5mJ6B4vhITeoUh2GNfeb2OZg682eQ/exec');
    define('ROOT_BASE_URL', '/');
    define('ALLOWED_LIVE_HOST', 'moneymgmt.is-best.net');
}

// PROJECT E specific base URL (for internal asset links)
$http_host = strtolower($_SERVER['HTTP_HOST'] ?? '');
$is_local  = ($http_host === 'localhost' || $http_host === '127.0.0.1' || str_contains($http_host, 'localhost'));
define('BASE_URL', $is_local ? '/PROJECT M/PROJECT E/' : '/PROJECT E/');
define('GOOGLE_SCRIPT_URL', defined('GOOGLE_SCRIPT_URL') ? GOOGLE_SCRIPT_URL : '');
