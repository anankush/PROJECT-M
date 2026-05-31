<?php
require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/csrf.php';
require_once '../includes/functions.php';
require_once '../Exp/includes/Model.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

check_session_timeout();

$uid = $_SESSION['user_id'];
$currency = $_SESSION['currency'] ?? '₹';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'generate_ott') {
    $module = sanitize_input($_GET['module'] ?? '');
    if ($module === 'exp' || $module === 'sav') {
        $token = generate_ott($module);
        echo json_encode(['status' => 'success', 'token' => $token]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid module name.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    verify_csrf_token($token);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'quick_entry') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request data.']);
        exit;
    }

    $type = sanitize_input($input['type'] ?? '');
    $amount = floatval($input['amount'] ?? 0);
    $date = sanitize_input($input['date'] ?? date('Y-m-d'));

    if ($amount <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Amount must be greater than zero.']);
        exit;
    }

    try {
        if ($type === 'expense') {
            $cat_id = intval($input['category_id'] ?? 0);
            $desc = sanitize_input($input['description'] ?? '');

            if ($cat_id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Please select a valid category.']);
                exit;
            }

            $check = $pdo->prepare("SELECT id FROM user_categories WHERE id = ? AND user_id = ?");
            $check->execute([$cat_id, $uid]);
            if (!$check->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid category selected.']);
                exit;
            }

            $time = date('H:i:s');
            $stmt = $pdo->prepare("INSERT INTO expenses (user_id, category_id, entry_date, entry_time, amount, description, custom_data) VALUES (?, ?, ?, ?, ?, ?, '[]')");
            $stmt->execute([$uid, $cat_id, $date, $time, $amount, $desc]);

            echo json_encode(['status' => 'success', 'message' => 'Expense logged successfully.']);
            exit;

        } elseif ($type === 'savings') {
            $goal_id = intval($input['goal_id'] ?? 0);
            $notes = sanitize_input($input['notes'] ?? '');

            if ($goal_id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Please select a valid savings goal.']);
                exit;
            }

            // Verify goal belongs to this user
            $check = $pdo->prepare("SELECT id FROM savings_goals WHERE id = ? AND user_id = ?");
            $check->execute([$goal_id, $uid]);
            if (!$check->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid savings goal selected.']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO savings_transactions (goal_id, user_id, amount, type, transaction_date, notes) VALUES (?, ?, ?, 'deposit', ?, ?)");
            $stmt->execute([$goal_id, $uid, $amount, $date, $notes]);

            echo json_encode(['status' => 'success', 'message' => 'Savings deposit logged successfully.']);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid entry type.']);
            exit;
        }
    } catch (PDOException $e) {
        // Log real error server-side, never expose to client
        error_log('[Dashboard API:quick_entry] ' . $e->getMessage());

        // Risky DB error → terminate session immediately and redirect to error page
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) session_destroy();

        $base_url = defined('BASE_URL') ? BASE_URL : '/';
        http_response_code(500);
        echo json_encode([
            'status'   => 'error',
            'message'  => 'A critical error occurred. Session terminated.',
            'redirect' => $base_url . 'error.php?code=db'
        ]);
        exit;
    }
}

$month = sanitize_input($_GET['month'] ?? 'all');

try {
    $model = new Model($pdo, 'expenses', $uid);

    // 1. Total Budget & 2. Total Expenditure
    if ($month === 'all') {
        // Budget = sum of categories' budgets
        $budgetQuery = "SELECT COALESCE(SUM(budget), 0) as total_budget FROM user_categories WHERE user_id = ?";
        $budgetResult = $model->customQuery($budgetQuery, [$uid]);
        $totalBudget = floatval($budgetResult[0]['total_budget'] ?? 0);

        // Spent = sum of all expenses all-time
        $expQuery = "SELECT COALESCE(SUM(amount), 0) as total_spent FROM expenses WHERE user_id = ?";
        $expResult = $model->customQuery($expQuery, [$uid]);
        $totalSpent = floatval($expResult[0]['total_spent'] ?? 0);

        // 3. Category Breakdown (All-Time)
        $breakdownQuery = "
            SELECT c.id as category_id, c.category_name, COALESCE(SUM(e.amount), 0) as spent
            FROM user_categories c
            LEFT JOIN expenses e ON c.id = e.category_id
            WHERE c.user_id = ?
            GROUP BY c.id
            HAVING spent > 0
        ";
        $breakdown = $model->customQuery($breakdownQuery, [$uid]);

        // Health Score calculations default to Current Month in overall mode
        $curMonth = date('Y-m');
        $curBudgetQuery = "
            SELECT COALESCE(SUM(COALESCE(mb.budget, c.budget)), 0) as total_budget
            FROM user_categories c
            LEFT JOIN category_monthly_budgets mb ON c.id = mb.category_id AND mb.budget_month = ?
            WHERE c.user_id = ?
        ";
        $curBudgetResult = $model->customQuery($curBudgetQuery, [$curMonth, $uid]);
        $curBudget = floatval($curBudgetResult[0]['total_budget'] ?? 0);

        $curExpQuery = "SELECT COALESCE(SUM(amount), 0) as total_spent FROM expenses WHERE user_id = ? AND DATE_FORMAT(entry_date, '%Y-%m') = ?";
        $curExpResult = $model->customQuery($curExpQuery, [$uid, $curMonth]);
        $curSpent = floatval($curExpResult[0]['total_spent'] ?? 0);

    } else {
        // Specific Month
        $budgetQuery = "
            SELECT COALESCE(SUM(COALESCE(mb.budget, c.budget)), 0) as total_budget
            FROM user_categories c
            LEFT JOIN category_monthly_budgets mb ON c.id = mb.category_id AND mb.budget_month = ?
            WHERE c.user_id = ?
        ";
        $budgetResult = $model->customQuery($budgetQuery, [$month, $uid]);
        $totalBudget = floatval($budgetResult[0]['total_budget'] ?? 0);

        $expQuery = "SELECT COALESCE(SUM(amount), 0) as total_spent FROM expenses WHERE user_id = ? AND DATE_FORMAT(entry_date, '%Y-%m') = ?";
        $expResult = $model->customQuery($expQuery, [$uid, $month]);
        $totalSpent = floatval($expResult[0]['total_spent'] ?? 0);

        // 3. Category Breakdown (Selected Month)
        $breakdownQuery = "
            SELECT c.id as category_id, c.category_name, COALESCE(SUM(e.amount), 0) as spent
            FROM user_categories c
            LEFT JOIN expenses e ON c.id = e.category_id AND DATE_FORMAT(e.entry_date, '%Y-%m') = ?
            WHERE c.user_id = ?
            GROUP BY c.id
            HAVING spent > 0
        ";
        $breakdown = $model->customQuery($breakdownQuery, [$month, $uid]);

        $curBudget = $totalBudget;
        $curSpent = $totalSpent;
    }

    // 4. Monthly Expenses (Last 6 Months)
    $expMonthlyQuery = "
        SELECT DATE_FORMAT(entry_date, '%Y-%m') as month, SUM(amount) as total_spent
        FROM expenses
        WHERE user_id = ? AND entry_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY month ORDER BY month ASC
    ";
    $expMonthly = $model->customQuery($expMonthlyQuery, [$uid]);

    // 5. Total Savings & Monthly Savings (Last 6 Months)
    $totalSaved = 0;
    $savMonthly = [];
    try {
        $savQuery = "
            SELECT COALESCE(SUM(CASE WHEN type='deposit' THEN amount ELSE -amount END), 0) as total_saved
            FROM savings_transactions WHERE user_id = ?
        ";
        $savResult = $model->customQuery($savQuery, [$uid]);
        $totalSaved = floatval($savResult[0]['total_saved'] ?? 0);

        $savMonthlyQuery = "
            SELECT DATE_FORMAT(transaction_date, '%Y-%m') as month, SUM(CASE WHEN type='deposit' THEN amount ELSE -amount END) as net_saved
            FROM savings_transactions
            WHERE user_id = ? AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY month ORDER BY month ASC
        ";
        $savMonthly = $model->customQuery($savMonthlyQuery, [$uid]);
    } catch (PDOException $e) {
        // Handle gracefully if table does not exist
    }

    // 6. Active Savings Goals with Progress
    $goals = [];
    try {
        $goalsQuery = "
            SELECT g.id, g.goal_name, g.target_amount, g.deadline, g.priority, g.theme_color,
                   COALESCE(SUM(CASE WHEN t.type='deposit' THEN t.amount ELSE -t.amount END), 0) as current_amount
            FROM savings_goals g
            LEFT JOIN savings_transactions t ON g.id = t.goal_id
            WHERE g.user_id = ?
            GROUP BY g.id
            ORDER BY g.created_at DESC
        ";
        $goals = $model->customQuery($goalsQuery, [$uid]);
    } catch (PDOException $e) {
        // Handle gracefully
    }

    // 7. Recent Combined Transactions Feed (Union expenses and savings)
    $recentTransactions = [];
    try {
        if ($month === 'all') {
            $recentQuery = "
                (
                    SELECT 'expense' as type, amount, entry_date as activity_date, entry_time as activity_time, description, c.category_name as context, 'expense' as subtype
                    FROM expenses e
                    LEFT JOIN user_categories c ON e.category_id = c.id
                    WHERE e.user_id = ?
                )
                UNION ALL
                (
                    SELECT 'savings' as type, amount, transaction_date as activity_date, '12:00:00' as activity_time, 
                           notes as description, g.goal_name as context, t.type as subtype
                    FROM savings_transactions t
                    LEFT JOIN savings_goals g ON t.goal_id = g.id
                    WHERE t.user_id = ?
                )
                ORDER BY activity_date DESC, activity_time DESC
                LIMIT 5
            ";
            $recentTransactions = $model->customQuery($recentQuery, [$uid, $uid]);
        } else {
            $recentQuery = "
                (
                    SELECT 'expense' as type, amount, entry_date as activity_date, entry_time as activity_time, description, c.category_name as context, 'expense' as subtype
                    FROM expenses e
                    LEFT JOIN user_categories c ON e.category_id = c.id
                    WHERE e.user_id = ? AND DATE_FORMAT(entry_date, '%Y-%m') = ?
                )
                UNION ALL
                (
                    SELECT 'savings' as type, amount, transaction_date as activity_date, '12:00:00' as activity_time, 
                           notes as description, g.goal_name as context, t.type as subtype
                    FROM savings_transactions t
                    LEFT JOIN savings_goals g ON t.goal_id = g.id
                    WHERE t.user_id = ? AND DATE_FORMAT(transaction_date, '%Y-%m') = ?
                )
                ORDER BY activity_date DESC, activity_time DESC
                LIMIT 5
            ";
            $recentTransactions = $model->customQuery($recentQuery, [$uid, $month, $uid, $month]);
        }
    } catch (PDOException $e) {
        // Handle gracefully
    }

    // 8. Financial Health Score Calculation
    $healthScore = 100;
    if ($curBudget > 0) {
        $spendRatio = $curSpent / $curBudget;
        if ($spendRatio > 1.0) {
            $healthScore -= min(40, 40 * ($spendRatio - 1) + 20);
        } else {
            $healthScore -= ($spendRatio * 25);
        }
    } else {
        if ($curSpent > 0) {
            $healthScore -= 20;
        }
    }

    if ($totalSaved > 0) {
        $healthScore += 10;
    } else {
        $healthScore -= 10;
    }

    $now = date('Y-m-d');
    $missedGoals = 0;
    $achievedGoals = 0;
    foreach ($goals as $g) {
        $current = floatval($g['current_amount']);
        $target = floatval($g['target_amount']);
        $is_achieved = ($current >= $target);
        if ($is_achieved) {
            $achievedGoals++;
        } else {
            if ($g['deadline'] && $g['deadline'] < $now) {
                $missedGoals++;
            }
        }
    }
    $healthScore -= ($missedGoals * 15);
    $healthScore += ($achievedGoals * 5);
    $healthScore = max(0, min(100, round($healthScore)));

    // 9. Net Worth
    $netWorth = $totalSaved + ($totalBudget - $totalSpent);

    // 10. Fetch Categories list (for Quick Log selection)
    $categories = [];
    try {
        $catQuery = "SELECT id, category_name FROM user_categories WHERE user_id = ? ORDER BY category_name ASC";
        $categories = $model->customQuery($catQuery, [$uid]);
    } catch (PDOException $e) {}

    echo json_encode([
        'status' => 'success',
        'currency' => $currency,
        'data' => [
            'total_budget' => $totalBudget,
            'total_spent' => $totalSpent,
            'total_saved' => $totalSaved,
            'net_worth' => $netWorth,
            'health_score' => $healthScore,
            'breakdown' => $breakdown,
            'exp_monthly' => $expMonthly,
            'sav_monthly' => $savMonthly,
            'goals' => $goals,
            'recent_transactions' => $recentTransactions,
            'categories' => $categories
        ]
    ]);

} catch (Exception $e) {
    error_log('[Dashboard API] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch dashboard data.']);
}
