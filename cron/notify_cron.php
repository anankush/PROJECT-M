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

            // 1. Send Push Notification (if subscription exists)
            sendMonthlySummary($pdo, $userId, $totalSpent, $totalSaved, $monthName);
            $pushesSent++;

            // 2. Send Beautiful Summary Email
            $userName = explode('@', $email)[0];
            $emailBody = get_monthly_summary_email_body($userName, $monthName, $totalSpent, $totalSaved);
            $ok = send_email($email, "Monthly Financial Summary - " . $monthName, $emailBody);
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
