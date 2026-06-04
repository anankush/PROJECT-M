<?php
/**
 * Money Management — Scheduled Notifications Cron Handler
 * Runs scheduled tasks like sending month-end spending vs savings reports.
 * Secured via X-Cron-Secret header verification.
 */

$secret = $_SERVER['HTTP_X_CRON_SECRET'] ?? '';

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/push_sender.php';

if (!defined('PUSH_CRON_SECRET') || empty($secret) || $secret !== PUSH_CRON_SECRET) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
    exit;
}

header('Content-Type: application/json');

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$isMonthEnd = (date('m', strtotime($today)) !== date('m', strtotime($tomorrow)));

$response = [
    'status' => 'success',
    'actions' => []
];

try {
    if ($isMonthEnd) {
        $monthName = date('F Y');
        $monthQuery = date('Y-m');

        // Fetch all users who have subscriptions
        $users = $pdo->query("SELECT DISTINCT user_id FROM push_subscriptions")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($users as $userId) {
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

            sendMonthlySummary($pdo, intval($userId), $totalSpent, $totalSaved, $monthName);
        }
        $response['actions'][] = 'Month-end summaries sent';
    } else {
        $response['actions'][] = 'Not month-end, skipped summaries';
    }

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
