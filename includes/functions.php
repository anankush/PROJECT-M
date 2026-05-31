<?php
// includes/functions.php

function sanitize_input($value) {
    if (is_array($value)) {
        $clean = [];
        foreach ($value as $k => $v) {
            $clean[sanitize_input($k)] = sanitize_input($v);
        }
        return $clean;
    }
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

/**
 * Returns the real client IP address, InfinityFree/Cloudflare-aware.
 *
 * Priority order:
 *   1. CF-Connecting-IP  — set by Cloudflare (trusted, cannot be spoofed by clients)
 *   2. REMOTE_ADDR        — direct connection IP (fallback for local/non-CF environments)
 *
 * We deliberately ignore HTTP_X_FORWARDED_FOR because it can be forged by clients.
 * On InfinityFree, Cloudflare sits in front, so CF-Connecting-IP is the reliable source.
 */
function get_real_ip() {
    // Cloudflare sets this header and it cannot be forged by the end client
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = trim($_SERVER['HTTP_CF_CONNECTING_IP']);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    // Fallback: direct connection (localhost dev or non-Cloudflare)
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function set_security_headers() {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data:; frame-ancestors 'none';");
    header('X-XSS-Protection: 1; mode=block');
}

/**
 * IP-based rate limiter using the `rate_limits` table.
 *
 * Call this at the TOP of every POST handler that should be protected.
 * It records the attempt first, then checks the count — so every request
 * (success or failure) is counted. This prevents enumeration attacks.
 *
 * @param PDO    $pdo             Active PDO connection
 * @param string $action          A unique name for the action (e.g. 'login', 'otp_verify')
 * @param int    $max_attempts    Max allowed attempts within the time window
 * @param int    $window_minutes  Rolling time window in minutes
 */
function check_rate_limit($pdo, $action, $max_attempts = 10, $window_minutes = 15) {
    $ip = get_real_ip();

    try {
        // 1. Purge expired records to keep the table lean (no cron needed)
        $pdo->prepare(
            "DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)"
        )->execute([$window_minutes]);

        // 2. Count how many attempts this IP has made for this action in the window
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM rate_limits
             WHERE action = ? AND ip = ?
               AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)"
        );
        $stmt->execute([$action, $ip, $window_minutes]);
        $count = (int) $stmt->fetchColumn();

        // 3. Reject if limit is already reached BEFORE recording (fail-fast)
        if ($count >= $max_attempts) {
            http_response_code(429);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Too many attempts. Please wait a few minutes and try again.'
            ]);
            exit;
        }

        // 4. Record this attempt
        $pdo->prepare(
            "INSERT INTO rate_limits (action, ip, attempts, created_at) VALUES (?, ?, 1, NOW())"
        )->execute([$action, $ip]);

    } catch (PDOException $e) {
        // If rate_limits table is missing or broken, log and continue silently
        // (never block the user due to a missing rate-limit table)
        error_log('Rate limit check failed: ' . $e->getMessage());
    }
}

function log_security_event($pdo, $email, $action, $user_id = null) {
    $ip         = get_real_ip();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    try {
        $stmt = $pdo->prepare("INSERT INTO security_logs (user_id, email, action, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $email, $action, $ip, $user_agent]);
    } catch (Exception $e) {
        error_log('Security log failed: ' . $e->getMessage());
    }
}

function verify_ownership($pdo, $table, $id, $user_id, $action) {
    if (!$id) return false;
    
    // Check if the table is allowed
    $allowed_tables = ['expenses', 'user_categories', 'user_notes', 'savings_goals', 'savings_transactions', 'category_monthly_budgets'];
    if (!in_array($table, $allowed_tables, true)) {
        return false;
    }

    try {
        $stmt = $pdo->prepare("SELECT user_id FROM `{$table}` WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        
        if (!$row) {
            // Not found at all (could be deleted or bad input, not necessarily IDOR)
            return false;
        }
        
        if (intval($row['user_id']) !== intval($user_id)) {
            // IDOR ATTEMPT DETECTED!
            log_security_event($pdo, $_SESSION['user_email'] ?? 'unknown', "idor_tampering_{$table}_{$action}", $user_id);
            
            // Terminate session securely
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
            
            // Check if AJAX request
            $is_json = (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false 
                     || strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false 
                     || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest'));
            
            $base_url = defined('BASE_URL') ? BASE_URL : '/';
            if ($is_json) {
                http_response_code(403);
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Security Violation: Tampering detected. Session terminated.',
                    'redirect' => $base_url . 'error.php?code=idor'
                ]);
                exit;
            } else {
                header('Location: ' . $base_url . 'error.php?code=idor');
                exit;
            }
        }
        return true;
    } catch (PDOException $e) {
        error_log("verify_ownership error: " . $e->getMessage());
        return false;
    }
}

function verify_decoded_id($pdo, $token, $action) {
    if (!function_exists('decode_id')) {
        require_once __DIR__ . '/id_obfuscate.php';
    }
    $uid = decode_id($token);
    if ($uid === null) {
        log_security_event($pdo, $_SESSION['user_email'] ?? 'unknown', "signature_tampering_{$action}");
        
        // Terminate session securely
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $is_json = (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false 
                 || strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false 
                 || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest'));

        $base_url = defined('BASE_URL') ? BASE_URL : '/';
        if ($is_json) {
            http_response_code(403);
            echo json_encode([
                'status' => 'error', 
                'message' => 'Security Violation: Signature verification failed.',
                'redirect' => $base_url . 'error.php?code=invalid_id'
            ]);
            exit;
        } else {
            header('Location: ' . $base_url . 'error.php?code=invalid_id');
            exit;
        }
    }
    return $uid;
}

