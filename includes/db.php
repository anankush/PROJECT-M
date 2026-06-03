<?php
date_default_timezone_set('Asia/Kolkata');

if (file_exists(__DIR__ . '/secrets.php')) {
    require_once __DIR__ . '/secrets.php';
}
if (file_exists(__DIR__ . '/env.php')) {
    require_once __DIR__ . '/env.php';
}

$required_constants = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
$missing = [];
foreach ($required_constants as $constant) {
    if (!defined($constant))
        $missing[] = $constant;
}
if (!empty($missing)) {
    error_log('DB config error: Missing definitions for ' . implode(', ', $missing));
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'System configuration error. Please contact support.']);
    exit;
}

if (!defined('SESSION_LIFETIME'))
    define('SESSION_LIFETIME', 900);
if (!defined('BASE_URL'))
    define('BASE_URL', '/');

error_reporting(0);
ini_set('display_errors', 0);

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    $pdo->exec("SET time_zone = '+05:30'");
} catch (PDOException $e) {
    http_response_code(500);
    if (
        strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
        || strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false
    ) {
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    } else {
        $base_path = defined('BASE_URL') ? BASE_URL : '/';
        header('Location: ' . $base_path . 'error.php?code=db');
    }
    exit;
}
