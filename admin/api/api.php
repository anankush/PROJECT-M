<?php
// admin/api/api.php
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/csrf.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// Check session role first
if ($action === 'check_session') {
    check_rate_limit($pdo, 'admin_session_check', 60, 1);
    if (isset($_SESSION['admin_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        echo json_encode([
            'status' => 'success',
            'is_admin' => true,
            'email' => $_SESSION['user_email'],
            'currency' => $_SESSION['currency'] ?? '₹'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'is_admin' => false]);
    }
    exit;
}

// Enforce admin for all other endpoints
require_admin();

// Enforce CSRF validation for modifying actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
}

switch ($action) {
    case 'get_system_stats':
        try {
            // 1. Total users
            $uStmt = $pdo->query("SELECT COUNT(*) FROM users");
            $total_users = (int)$uStmt->fetchColumn();

            // 2. Active users today (active in last 24 hours)
            $aStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE last_active_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
            $active_users = (int)$aStmt->fetchColumn();

            // 3. System totals (Budget, Spent, Saved)
            $bStmt = $pdo->query("SELECT SUM(budget) FROM user_categories");
            $system_budget = (float)$bStmt->fetchColumn();

            $eStmt = $pdo->query("SELECT SUM(amount) FROM expenses");
            $system_spent = (float)$eStmt->fetchColumn();

            $sStmt = $pdo->query("SELECT SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END) FROM savings_transactions");
            $system_saved = (float)$sStmt->fetchColumn();
            if ($system_saved < 0) $system_saved = 0.00;

            // 4. Failed logins (last 24 hours)
            $fStmt = $pdo->query("SELECT COUNT(*) FROM security_logs WHERE action IN ('login_failed', 'admin_login_failed') AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
            $failed_logins = (int)$fStmt->fetchColumn();

            echo json_encode([
                'status' => 'success',
                'data' => [
                    'total_users' => $total_users,
                    'active_users' => $active_users,
                    'system_budget' => $system_budget,
                    'system_spent' => $system_spent,
                    'system_saved' => $system_saved,
                    'failed_logins' => $failed_logins
                ]
            ]);
        } catch (Exception $e) {
            error_log('[Admin API:get_system_stats] ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch system stats.']);
        }
        break;

    case 'get_users':
        try {
            $stmt = $pdo->query("
                SELECT 
                    u.id, 
                    u.email, 
                    u.created_at, 
                    u.last_active_at, 
                    u.status, 
                    u.total_budget,
                    (SELECT COUNT(*) FROM user_categories WHERE user_id = u.id) as total_categories,
                    (SELECT COUNT(*) FROM expenses WHERE user_id = u.id) as total_expenses,
                    (SELECT COUNT(*) FROM savings_goals WHERE user_id = u.id) as total_goals
                FROM users u
                ORDER BY u.created_at DESC
            ");
            $users = $stmt->fetchAll();
            echo json_encode(['status' => 'success', 'data' => $users]);
        } catch (Exception $e) {
            error_log('[Admin API:get_users] ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch users.']);
        }
        break;

    case 'toggle_user_status':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'POST method required']);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $user_id = (int)($input['user_id'] ?? 0);
        $block = (bool)($input['block'] ?? false);

        if (!$user_id) {
            echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
            exit;
        }

        try {
            // Get user email for logging
            $uStmt = $pdo->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
            $uStmt->execute([$user_id]);
            $user = $uStmt->fetch();
            if (!$user) {
                echo json_encode(['status' => 'error', 'message' => 'User not found']);
                exit;
            }

            $new_status = $block ? 'blocked' : 'active';
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $user_id]);

            // Log administrative action
            $log_action = $block ? 'user_blocked' : 'user_unblocked';
            log_security_event($pdo, $user['email'], $log_action, $_SESSION['admin_id']);

            echo json_encode(['status' => 'success', 'message' => 'User status updated successfully']);
        } catch (Exception $e) {
            error_log('[Admin API:toggle_user_status] ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Failed to update user status.']);
        }
        break;

    case 'get_security_logs':
        try {
            // Prune security logs older than 30 days
            $pdo->query("DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");

            $stmt = $pdo->query("SELECT id, email, action, ip_address, user_agent, created_at FROM security_logs ORDER BY created_at DESC LIMIT 100");
            $logs = $stmt->fetchAll();
            echo json_encode(['status' => 'success', 'data' => $logs]);
        } catch (Exception $e) {
            error_log('[Admin API:get_security_logs] ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch security logs.']);
        }
        break;

    case 'get_analytics':
        try {
            // 1. User registration trend (last 12 months)
            $regStmt = $pdo->query("
                SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
                FROM users 
                GROUP BY month 
                ORDER BY month ASC 
                LIMIT 12
            ");
            $reg_trend = $regStmt->fetchAll();

            // 2. User logins trend (last 12 months)
            $loginStmt = $pdo->query("
                SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
                FROM security_logs 
                WHERE action IN ('login_success', 'admin_login_success')
                GROUP BY month 
                ORDER BY month ASC 
                LIMIT 12
            ");
            $login_trend = $loginStmt->fetchAll();

            // 3. Security Event Distribution
            $secStmt = $pdo->query("
                SELECT action, COUNT(*) as count 
                FROM security_logs 
                GROUP BY action
            ");
            $sec_dist = $secStmt->fetchAll();

            echo json_encode([
                'status' => 'success',
                'data' => [
                    'registration_trend' => $reg_trend,
                    'login_trend' => $login_trend,
                    'security_distribution' => $sec_dist
                ]
            ]);
        } catch (Exception $e) {
            error_log('[Admin API:get_analytics] ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch analytics data.']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
