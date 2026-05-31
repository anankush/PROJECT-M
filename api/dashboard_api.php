<?php
// api/dashboard_api.php
require_once '../includes/db.php';
require_once '../includes/auth_check.php'; // session_start_secure() + check_session_timeout()
require_once '../includes/functions.php';
require_once '../Exp/includes/Model.php';

header('Content-Type: application/json');

// require_login() calls check_session_timeout() which updates last_activity.
// This prevents browser-refresh logout when in-app API calls kept session alive.
require_login();

$uid = $_SESSION['user_id'];
$month = sanitize_input($_GET['month'] ?? date('Y-m'));
$currency = $_SESSION['currency'] ?? '₹';

try {
    $model = new Model($pdo, 'expenses', $uid);

    // 1. Total Budget
    $budgetQuery = "
        SELECT COALESCE(SUM(COALESCE(mb.budget, c.budget)), 0) as total_budget
        FROM user_categories c
        LEFT JOIN category_monthly_budgets mb ON c.id = mb.category_id AND mb.budget_month = ?
        WHERE c.user_id = ?
    ";
    $budgetResult = $model->customQuery($budgetQuery, [$month, $uid]);
    $totalBudget = floatval($budgetResult[0]['total_budget'] ?? 0);

    // 2. Total Expenditure
    $expQuery = "SELECT COALESCE(SUM(amount), 0) as total_spent FROM expenses WHERE user_id = ? AND DATE_FORMAT(entry_date, '%Y-%m') = ?";
    $expResult = $model->customQuery($expQuery, [$uid, $month]);
    $totalSpent = floatval($expResult[0]['total_spent'] ?? 0);

    // 3. Category Breakdown
    $breakdownQuery = "
        SELECT c.category_name, COALESCE(SUM(e.amount), 0) as spent
        FROM user_categories c
        LEFT JOIN expenses e ON c.id = e.category_id AND DATE_FORMAT(e.entry_date, '%Y-%m') = ?
        WHERE c.user_id = ?
        GROUP BY c.id
        HAVING spent > 0
    ";
    $breakdown = $model->customQuery($breakdownQuery, [$month, $uid]);

    // 4. Monthly Expenses (Last 6 Months)
    $expMonthlyQuery = "
        SELECT DATE_FORMAT(entry_date, '%Y-%m') as month, SUM(amount) as total_spent
        FROM expenses
        WHERE user_id = ? AND entry_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY month ORDER BY month ASC
    ";
    $expMonthly = $model->customQuery($expMonthlyQuery, [$uid]);

    // 5. Total Savings
    $totalSaved = 0;
    $savMonthly = [];
    try {
        $savQuery = "
            SELECT COALESCE(SUM(CASE WHEN type='deposit' THEN amount ELSE -amount END), 0) as total_saved
            FROM savings_transactions WHERE user_id = ?
        ";
        $savResult = $model->customQuery($savQuery, [$uid]);
        $totalSaved = floatval($savResult[0]['total_saved'] ?? 0);

        // 6. Monthly Savings (Last 6 Months)
        $savMonthlyQuery = "
            SELECT DATE_FORMAT(transaction_date, '%Y-%m') as month, SUM(CASE WHEN type='deposit' THEN amount ELSE -amount END) as net_saved
            FROM savings_transactions
            WHERE user_id = ? AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY month ORDER BY month ASC
        ";
        $savMonthly = $model->customQuery($savMonthlyQuery, [$uid]);
    } catch (PDOException $e) {
        // Savings table might not exist yet
    }

    echo json_encode([
        'status' => 'success',
        'currency' => $currency,
        'data' => [
            'total_budget' => $totalBudget,
            'total_spent' => $totalSpent,
            'total_saved' => $totalSaved,
            'breakdown' => $breakdown,
            'exp_monthly' => $expMonthly,
            'sav_monthly' => $savMonthly
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch dashboard data.']);
}
