<?php
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$uid = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(g.target_amount), 0) as total_target,
            COALESCE(SUM(
                COALESCE((SELECT SUM(CASE WHEN t.type='deposit' THEN t.amount ELSE -t.amount END)
                          FROM savings_transactions t WHERE t.goal_id = g.id), 0)
            ), 0) as total_saved
        FROM savings_goals g
        WHERE g.user_id = ?
    ");
    $stmt->execute([$uid]);
    $totals = $stmt->fetch();
    $stmtGoals = $pdo->prepare("
        SELECT g.goal_name, g.target_amount,
               COALESCE(SUM(CASE WHEN t.type='deposit' THEN t.amount ELSE -t.amount END), 0) as current_amount
        FROM savings_goals g
        LEFT JOIN savings_transactions t ON g.id = t.goal_id
        WHERE g.user_id = ?
        GROUP BY g.id
        ORDER BY g.id ASC
    ");
    $stmtGoals->execute([$uid]);
    $goals = $stmtGoals->fetchAll(PDO::FETCH_ASSOC);
    $stmtMonthly = $pdo->prepare("
        SELECT DATE_FORMAT(transaction_date, '%Y-%m') as month,
               SUM(CASE WHEN type='deposit' THEN amount ELSE -amount END) as net_saved
        FROM savings_transactions
        WHERE user_id = ?
          AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY month
        ORDER BY month ASC
    ");
    $stmtMonthly->execute([$uid]);
    $monthly = $stmtMonthly->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status'       => 'success',
        'total_target' => floatval($totals['total_target']),
        'total_saved'  => floatval($totals['total_saved']),
        'balance'      => floatval($totals['total_target']) - floatval($totals['total_saved']),
        'goals'        => $goals,
        'monthly'      => $monthly
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
