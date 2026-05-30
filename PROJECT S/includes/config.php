<?php
// PROJECT S config — delegates to central PROJECT M config
$central_config = dirname(__DIR__, 2) . '/includes/config.php';
if (file_exists($central_config)) {
    require_once $central_config;
} else {
    date_default_timezone_set('Asia/Kolkata');
    function env($key, $default = '') { return getenv($key) ?: $default; }
    $sf = __DIR__ . '/secrets.php'; if (file_exists($sf)) require_once $sf;
    define('DB_HOST', defined('DB_HOST') ? DB_HOST : env('DB_HOST', '127.0.0.1'));
    define('DB_NAME', defined('DB_NAME') ? DB_NAME : env('DB_NAME', 'expense_management'));
    define('DB_USER', defined('DB_USER') ? DB_USER : env('DB_USER', 'root'));
    define('DB_PASS', defined('DB_PASS') ? DB_PASS : env('DB_PASS', ''));
    $s = env('APP_SECRET'); if (empty($s)) { $sf2 = __DIR__.'/.secret'; $s = file_exists($sf2) ? trim(file_get_contents($sf2)) : bin2hex(random_bytes(32)); file_put_contents($sf2, $s, LOCK_EX); }
    define('APP_SECRET', $s);
    define('SESSION_LIFETIME', 900); define('MAX_LOGIN_ATTEMPTS', 5); define('LOCKOUT_TIME', 900);
    define('ROOT_BASE_URL', '/'); define('ALLOWED_LIVE_HOST', 'moneymgmt.is-best.net');
}

// PROJECT S specific base URL
$http_host = strtolower($_SERVER['HTTP_HOST'] ?? '');
$is_local  = ($http_host === 'localhost' || $http_host === '127.0.0.1' || str_contains($http_host, 'localhost'));
define('BASE_URL_S_SELF', $is_local ? '/PROJECT M/PROJECT S/' : '/PROJECT S/');
