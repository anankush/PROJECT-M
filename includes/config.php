<?php
// includes/config.php
session_start();

// Load secrets if exists (this file should NOT be pushed to github)
$secrets_file = __DIR__ . '/secrets.php';
if (file_exists($secrets_file)) {
    require_once $secrets_file;
}

// Fallback to defaults or environment variables
define('DB_HOST', defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: '127.0.0.1'));
define('DB_USER', defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'root'));
define('DB_PASS', defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') ?: ''));
define('DB_NAME', defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'money_management'));
// Dynamically determine BASE_URL
$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$dir = str_replace('\\', '/', dirname(__DIR__));
$base_url = str_replace($doc_root, '', $dir);
$base_url = str_replace(' ', '%20', $base_url);
define('BASE_URL', $base_url);

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// Redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
}

// CSRF Protection
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (empty($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

