<?php
// Exp/api/summary.php
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$uid      = $_SESSION['user_id'];
$month    = sanitize_input($_GET['month'] ?? date('Y-m'));
$currency = $_SESSION['currency'] ?? '₹';

// Validate YYYY-MM format
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid month format. Use YYYY-MM']);
    exit;
}

try {
    // Total budget from all categories for the selected month
    $stmtBudget = $pdo->prepare("
        SELECT COALESCE(SUM(COALESCE(mb.budget, c.budget)), 0) as total_budget
        FROM user_categories c
        LEFT JOIN category_monthly_budgets mb ON c.id = mb.category_id AND mb.budget_month = ?
        WHERE c.user_id = ?
    ");
    $stmtBudget->execute([$month, $uid]);
    $budget = $stmtBudget->fetchColumn();

    // Total expenditure for selected month
    $stmtSpent = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) as total_spent
        FROM expenses
        WHERE user_id = ? AND DATE_FORMAT(entry_date, '%Y-%m') = ?
    ");
    $stmtSpent->execute([$uid, $month]);
    $spent = $stmtSpent->fetchColumn();

    // Category breakdown for donut chart
    $stmtBreakdown = $pdo->prepare("
        SELECT c.category_name, COALESCE(SUM(e.amount), 0) as spent
        FROM user_categories c
        LEFT JOIN expenses e ON c.id = e.category_id AND DATE_FORMAT(e.entry_date, '%Y-%m') = ?
        WHERE c.user_id = ?
        GROUP BY c.id
        HAVING spent > 0
    ");
    $stmtBreakdown->execute([$month, $uid]);
    $breakdown = $stmtBreakdown->fetchAll(PDO::FETCH_ASSOC);

    // Monthly totals over last 6 months for combined bar chart
    $stmtMonthly = $pdo->prepare("
        SELECT DATE_FORMAT(entry_date, '%Y-%m') as month,
               SUM(amount) as total_spent
        FROM expenses
        WHERE user_id = ?
          AND entry_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY month
        ORDER BY month ASC
    ");
    $stmtMonthly->execute([$uid]);
    $monthly = $stmtMonthly->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status'       => 'success',
        'month'        => $month,
        'currency'     => $currency,
        'total_budget' => floatval($budget),
        'total_spent'  => floatval($spent),
        'balance'      => floatval($budget) - floatval($spent),
        'breakdown'    => $breakdown,
        'monthly'      => $monthly
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
