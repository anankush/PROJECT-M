<?php
/**
 * Money Management — Scheduled Notifications Cron Handler
 * Runs scheduled tasks like sending month-end spending vs savings reports.
 * Secured via X-Cron-Secret header verification.
 */

$secret = $_SERVER['HTTP_X_CRON_SECRET'] ?? $_GET['secret'] ?? '';

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/push_sender.php';
require_once __DIR__ . '/../includes/mailer.php';

if (!defined('PUSH_CRON_SECRET') || empty($secret) || $secret !== PUSH_CRON_SECRET) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
    exit;
}

header('Content-Type: application/json');

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$isMonthEnd = (date('m', strtotime($today)) !== date('m', strtotime($tomorrow)));
$force = isset($_GET['force']) && $_GET['force'] === '1';

$response = [
    'status' => 'success',
    'actions' => []
];

try {
    if ($isMonthEnd || $force) {
        $monthName = date('F Y');
        $monthQuery = date('Y-m');

        // Fetch all active users (not blocked)
        $users = $pdo->query("SELECT id, email FROM users WHERE status != 'blocked'")->fetchAll(PDO::FETCH_ASSOC);

        $emailsSent = 0;
        $pushesSent = 0;

        foreach ($users as $user) {
            $userId = intval($user['id']);
            $email = $user['email'];

            // Check if user has monthly summary notifications enabled
            if (!getUserPushPref($pdo, $userId, 'monthly_summary')) {
                continue;
            }

            // Calculate spent this month
            $stmtSpent = $pdo->prepare("SELECT SUM(amount) FROM expenses WHERE user_id = ? AND entry_date LIKE ?");
            $stmtSpent->execute([$userId, $monthQuery . '-%']);
            $totalSpent = floatval($stmtSpent->fetchColumn());

            // Calculate saved this month
            $stmtSaved = $pdo->prepare("
                SELECT SUM(CASE WHEN type='deposit' THEN amount ELSE -amount END) 
                FROM savings_transactions 
                WHERE user_id = ? AND transaction_date LIKE ?
            ");
            $stmtSaved->execute([$userId, $monthQuery . '-%']);
            $totalSaved = floatval($stmtSaved->fetchColumn());

            // Fetch expense breakdown for this month
            $stmtBreakdown = $pdo->prepare("
                SELECT c.category_name, COALESCE(SUM(e.amount), 0) as spent
                FROM user_categories c
                LEFT JOIN expenses e ON c.id = e.category_id AND e.entry_date LIKE ?
                WHERE c.user_id = ?
                GROUP BY c.id
                HAVING spent > 0
            ");
            $stmtBreakdown->execute([$monthQuery . '-%', $userId]);
            $expensesBreakdown = $stmtBreakdown->fetchAll(PDO::FETCH_ASSOC);

            // Fetch savings breakdown for this month
            $stmtSavingsBreakdown = $pdo->prepare("
                SELECT g.goal_name,
                       COALESCE(SUM(CASE WHEN t.type='deposit' THEN t.amount ELSE -t.amount END), 0) as net_saved
                FROM savings_goals g
                INNER JOIN savings_transactions t ON g.id = t.goal_id
                WHERE g.user_id = ? AND t.transaction_date LIKE ?
                GROUP BY g.id
                HAVING net_saved != 0
            ");
            $stmtSavingsBreakdown->execute([$userId, $monthQuery . '-%']);
            $savingsBreakdown = $stmtSavingsBreakdown->fetchAll(PDO::FETCH_ASSOC);

            // 1. Send Push Notification (if subscription exists)
            sendMonthlySummary($pdo, $userId, $totalSpent, $totalSaved, $monthName);
            $pushesSent++;

            // 2. Send Beautiful Summary Email
            $userName = explode('@', $email)[0];
            $emailBody = get_monthly_summary_email_body($userName, $monthName, $totalSpent, $totalSaved, $expensesBreakdown, $savingsBreakdown);
            $ok = send_email($email, "Monthly Activity Update - " . $monthName, $emailBody);
            if ($ok) {
                $emailsSent++;
            }
        }
        $response['actions'][] = "Month-end summaries processed. Emails sent: {$emailsSent}, Pushes triggered: {$pushesSent}";
    } else {
        $response['actions'][] = 'Not month-end, skipped summaries';
    }

    echo json_encode($response);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
