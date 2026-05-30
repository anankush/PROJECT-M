<?php
// ============================================================
// PROJECT M: Unified Auth Processor
// Handles login and registration for the central gateway
// ============================================================

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/db.php';

session_start_secure();
set_security_headers();
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// ── Login ─────────────────────────────────────────────────────
if ($action === 'login') {
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Email and password are required.']);
        exit;
    }

    $rate_key = 'login_' . $email;
    if (!check_rate_limit($rate_key, $ip)) {
        echo json_encode(['status' => 'error', 'message' => 'Too many failed attempts. Please wait 15 minutes.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, email, password, role, currency FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        record_attempt($rate_key, $ip);
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
        exit;
    }

    $verify = verify_password($password, $user['password']);
    if ($verify === false) {
        record_attempt($rate_key, $ip);
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
        exit;
    }
    if ($verify === 'needs_upgrade') {
        upgrade_password($user['id'], $password, $pdo);
    }

    // Valid login — set session
    clear_attempts($rate_key, $ip);
    regenerate_session();

    $_SESSION['user_id']       = $user['id'];
    $_SESSION['user_email']    = $user['email'];
    $_SESSION['user_role']     = $user['role'];
    $_SESSION['user_currency'] = $user['currency'] ?? '₹';
    $_SESSION['last_activity'] = time();

    // Update last_active_at
    $pdo->prepare("UPDATE users SET last_active_at = NOW() WHERE id = ?")->execute([$user['id']]);

    echo json_encode(['status' => 'success', 'redirect' => ROOT_BASE_URL . 'dashboard.php']);
    exit;
}

// ── Register ──────────────────────────────────────────────────
if ($action === 'register') {
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($email) || empty($password) || empty($confirm)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
        exit;
    }
    if ($password !== $confirm) {
        echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
        exit;
    }
    if (strlen($password) < 8) {
        echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'This email is already registered.']);
        exit;
    }

    $hashed = hash_password($password);
    $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'user')")->execute([$email, $hashed]);
    $new_id = (int)$pdo->lastInsertId();

    regenerate_session();
    $_SESSION['user_id']       = $new_id;
    $_SESSION['user_email']    = $email;
    $_SESSION['user_role']     = 'user';
    $_SESSION['user_currency'] = '₹';
    $_SESSION['last_activity'] = time();

    echo json_encode(['status' => 'success', 'redirect' => ROOT_BASE_URL . 'dashboard.php']);
    exit;
}

// ── Logout ────────────────────────────────────────────────────
if ($action === 'logout') {
    session_start_secure();
    session_unset();
    session_destroy();
    echo json_encode(['status' => 'success', 'redirect' => ROOT_BASE_URL . 'login.php']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
