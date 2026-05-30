<?php
require_once __DIR__ . '/config.php';

error_reporting(0);
ini_set('display_errors', 0);

try {
    $charset = 'utf8mb4';
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=$charset",
            DB_USER,
            DB_PASS
        );
    } catch (PDOException $e) {
        $charset = 'utf8';
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=$charset",
            DB_USER,
            DB_PASS
        );
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    try {
        $tz = date('P');
        $pdo->exec("SET time_zone = '$tz'");
    } catch (PDOException $e) {
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed. Please try again later or contact support.'
    ]);
    exit;
}
