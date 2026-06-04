<?php
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/csrf.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

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

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
}

switch ($action) {
    case 'get_system_stats':
        try {
            $uStmt = $pdo->query("SELECT COUNT(*) FROM users");
            $total_users = (int) $uStmt->fetchColumn();

            $aStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE last_active_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
            $active_users = (int) $aStmt->fetchColumn();

            $bStmt = $pdo->query("SELECT SUM(budget) FROM user_categories");
            $system_budget = (float) $bStmt->fetchColumn();

            $eStmt = $pdo->query("SELECT SUM(amount) FROM expenses");
            $system_spent = (float) $eStmt->fetchColumn();

            $sStmt = $pdo->query("SELECT SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END) FROM savings_transactions");
            $system_saved = (float) $sStmt->fetchColumn();
            if ($system_saved < 0)
                $system_saved = 0.00;

            $fStmt = $pdo->query("SELECT COUNT(*) FROM security_logs WHERE action IN ('login_failed', 'admin_login_failed') AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
            $failed_logins = (int) $fStmt->fetchColumn();

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
        $user_id = (int) ($input['user_id'] ?? 0);
        $block = (bool) ($input['block'] ?? false);

        if (!$user_id) {
            echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
            exit;
        }

        try {

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


            $log_action = $block ? 'user_blocked' : 'user_unblocked';
            log_security_event($pdo, $user['email'], $log_action, $_SESSION['admin_id']);

            echo json_encode(['status' => 'success', 'message' => 'User status updated successfully']);
        } catch (Exception $e) {
            error_log('[Admin API:toggle_user_status] ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Failed to update user status.']);
        }
        break;

    case 'delete_user':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'POST method required']);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $user_id = (int)($input['user_id'] ?? 0);

        if (!$user_id) {
            echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
            exit;
        }

        try {
            $uStmt = $pdo->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
            $uStmt->execute([$user_id]);
            $user = $uStmt->fetch();
            if (!$user) {
                echo json_encode(['status' => 'error', 'message' => 'User not found']);
                exit;
            }

            $pdo->beginTransaction();

            $queries = [
                "DELETE FROM category_monthly_budgets WHERE user_id = ?",
                "DELETE FROM monthly_overall_budgets WHERE user_id = ?",
                "DELETE FROM expenses WHERE user_id = ?",
                "DELETE FROM savings_transactions WHERE user_id = ?",
                "DELETE FROM savings_goals WHERE user_id = ?",
                "DELETE FROM user_categories WHERE user_id = ?",
                "DELETE FROM user_notes WHERE user_id = ?",
                "DELETE FROM push_subscriptions WHERE user_id = ?",
                "DELETE FROM push_preferences WHERE user_id = ?",
                "DELETE FROM security_logs WHERE user_id = ?"
            ];

            foreach ($queries as $query) {
                try {
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([$user_id]);
                } catch (Exception $e) {
                    // Safe execution if tables are missing or not matching schema
                }
            }

            try {
                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
                $stmt->execute([$user['email']]);
            } catch (Exception $e) {}

            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);

            $pdo->commit();

            log_security_event($pdo, $user['email'], 'user_deleted_by_admin', $_SESSION['admin_id']);

            echo json_encode(['status' => 'success', 'message' => 'User and all associated data deleted successfully']);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[Admin API:delete_user] ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete user and associated data.']);
        }
        break;

    case 'get_security_logs':
        try {
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
            $regStmt = $pdo->query("
                SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
                FROM users 
                GROUP BY month 
                ORDER BY month ASC 
                LIMIT 12
            ");
            $reg_trend = $regStmt->fetchAll();

            $loginStmt = $pdo->query("
                SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
                FROM security_logs 
                WHERE action IN ('login_success', 'admin_login_success')
                GROUP BY month 
                ORDER BY month ASC 
                LIMIT 12
            ");
            $login_trend = $loginStmt->fetchAll();

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

    case 'get_blocklist_status':
        $cache_file = __DIR__ . '/../../includes/disposable_domains.txt';
        $exists = file_exists($cache_file);
        $count = 0;
        $last_sync = 'Never';

        if ($exists) {
            $lines = file($cache_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $count = is_array($lines) ? count($lines) : 0;
            $last_sync = date('Y-m-d H:i:s', filemtime($cache_file));
        }

        echo json_encode([
            'status' => 'success',
            'exists' => $exists,
            'count' => $count,
            'last_sync' => $last_sync
        ]);
        break;

    case 'sync_email_blocklist':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'POST method required']);
            exit;
        }

        $cache_file = __DIR__ . '/../../includes/disposable_domains.txt';
        $url = 'https://raw.githubusercontent.com/disposable-email-domains/disposable-email-domains/master/disposable_email_blocklist.conf';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $list = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($list && $http_code === 200) {
            $bytes = @file_put_contents($cache_file, $list);
            if ($bytes !== false) {
                $lines = file($cache_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Live blocklist synced successfully!',
                    'count' => is_array($lines) ? count($lines) : 0,
                    'last_sync' => date('Y-m-d H:i:s', filemtime($cache_file))
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to save blocklist locally. Permission error?']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch blocklist from live source. Check outbound internet connection.']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
