<?php

function check_savings_db_migration($pdo) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `savings_goals` LIKE 'category'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE `savings_goals` ADD COLUMN `category` varchar(50) NOT NULL DEFAULT 'others'");
        }
        $stmt = $pdo->query("SHOW COLUMNS FROM `savings_goals` LIKE 'theme_color'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE `savings_goals` ADD COLUMN `theme_color` varchar(50) NOT NULL DEFAULT 'purple'");
        }
        $stmt = $pdo->query("SHOW COLUMNS FROM `savings_goals` LIKE 'priority'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE `savings_goals` ADD COLUMN `priority` varchar(20) NOT NULL DEFAULT 'medium'");
        }
        $stmt = $pdo->query("SHOW COLUMNS FROM `savings_transactions` LIKE 'notes'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE `savings_transactions` ADD COLUMN `notes` varchar(255) DEFAULT NULL");
        }
    } catch (PDOException $e) {
    }
}

function handle_get_goals($pdo) {
    $uid = $_SESSION['user_id'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT g.id, g.goal_name, g.target_amount, g.deadline, g.created_at, g.category, g.theme_color, g.priority,
                   COALESCE(SUM(CASE WHEN t.type='deposit' THEN t.amount ELSE -t.amount END), 0) as current_amount
            FROM savings_goals g
            LEFT JOIN savings_transactions t ON g.id = t.goal_id
            WHERE g.user_id = ?
            GROUP BY g.id
            ORDER BY g.id DESC
        ");
        $stmt->execute([$uid]);
        $goals = $stmt->fetchAll();
        
        echo json_encode(['status' => 'success', 'data' => $goals]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch goals']);
    }
}

function handle_add_goal($pdo) {
    $uid = $_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    $goal_name = trim(htmlspecialchars($input['goal_name'] ?? '', ENT_QUOTES, 'UTF-8'));
    $target_amount = floatval($input['target_amount'] ?? 0);
    $deadline = !empty($input['deadline']) ? $input['deadline'] : null;
    $category = trim(htmlspecialchars($input['category'] ?? 'others', ENT_QUOTES, 'UTF-8'));
    $theme_color = trim(htmlspecialchars($input['theme_color'] ?? 'purple', ENT_QUOTES, 'UTF-8'));
    $priority = trim(htmlspecialchars($input['priority'] ?? 'medium', ENT_QUOTES, 'UTF-8'));

    if (empty($goal_name) || $target_amount <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Valid Goal name and Target Amount are required.']);
        return;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO savings_goals (user_id, goal_name, target_amount, deadline, category, theme_color, priority) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$uid, $goal_name, $target_amount, $deadline, $category, $theme_color, $priority]);
        echo json_encode(['status' => 'success', 'message' => 'Goal created successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create goal.']);
    }
}

function handle_update_goal($pdo) {
    $uid = $_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    $goal_id = intval($input['goal_id'] ?? 0);
    $goal_name = trim(htmlspecialchars($input['goal_name'] ?? '', ENT_QUOTES, 'UTF-8'));
    $target_amount = floatval($input['target_amount'] ?? 0);
    $deadline = !empty($input['deadline']) ? $input['deadline'] : null;
    $category = trim(htmlspecialchars($input['category'] ?? 'others', ENT_QUOTES, 'UTF-8'));
    $theme_color = trim(htmlspecialchars($input['theme_color'] ?? 'purple', ENT_QUOTES, 'UTF-8'));
    $priority = trim(htmlspecialchars($input['priority'] ?? 'medium', ENT_QUOTES, 'UTF-8'));
    
    if ($goal_id <= 0 || empty($goal_name) || $target_amount <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Valid Goal ID, Name and Target Amount are required.']);
        return;
    }
    
    try {
        if (!verify_ownership($pdo, 'savings_goals', $goal_id, $uid, 'update_goal')) {
            echo json_encode(['status' => 'error', 'message' => 'Goal not found.']);
            return;
        }
        
        $stmt = $pdo->prepare("UPDATE savings_goals SET goal_name = ?, target_amount = ?, deadline = ?, category = ?, theme_color = ?, priority = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$goal_name, $target_amount, $deadline, $category, $theme_color, $priority, $goal_id, $uid]);
        echo json_encode(['status' => 'success', 'message' => 'Goal updated successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update goal.']);
    }
}

function handle_delete_goal($pdo) {
    $uid = $_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    $goal_id = intval($input['goal_id'] ?? 0);

    if ($goal_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Goal ID.']);
        return;
    }

    try {
        if (!verify_ownership($pdo, 'savings_goals', $goal_id, $uid, 'delete_goal')) {
            echo json_encode(['status' => 'error', 'message' => 'Goal not found.']);
            return;
        }
        $stmt = $pdo->prepare("DELETE FROM savings_goals WHERE id = ? AND user_id = ?");
        $stmt->execute([$goal_id, $uid]);
        echo json_encode(['status' => 'success', 'message' => 'Goal deleted successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete goal.']);
    }
}

function handle_add_deposit($pdo) {
    $uid = $_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    $goal_id = intval($input['goal_id'] ?? 0);
    $amount = floatval($input['amount'] ?? 0);
    $type = $input['type'] ?? 'deposit';
    $date = $input['date'] ?? date('Y-m-d');
    $notes = trim(htmlspecialchars($input['notes'] ?? '', ENT_QUOTES, 'UTF-8'));
    if (empty($notes)) {
        $notes = null;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $date = date('Y-m-d');
    }

    if ($goal_id <= 0 || $amount <= 0 || !in_array($type, ['deposit', 'withdraw'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input for transaction.']);
        return;
    }

    try {
        if (!verify_ownership($pdo, 'savings_goals', $goal_id, $uid, 'add_deposit')) {
            echo json_encode(['status' => 'error', 'message' => 'Goal not found.']);
            return;
        }

        $stmt = $pdo->prepare("INSERT INTO savings_transactions (goal_id, user_id, amount, type, transaction_date, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$goal_id, $uid, $amount, $type, $date, $notes]);

        // Trigger push notification goal completion check
        try {
            if ($type === 'deposit') {
                $stmtGoal = $pdo->prepare("SELECT goal_name, target_amount FROM savings_goals WHERE id = ? AND user_id = ?");
                $stmtGoal->execute([$goal_id, $uid]);
                $goal = $stmtGoal->fetch();
                if ($goal) {
                    $goalName = $goal['goal_name'];
                    $targetAmount = floatval($goal['target_amount']);

                    $stmtNew = $pdo->prepare("
                        SELECT COALESCE(SUM(CASE WHEN type='deposit' THEN amount ELSE -amount END), 0) 
                        FROM savings_transactions 
                        WHERE goal_id = ? AND user_id = ?
                    ");
                    $stmtNew->execute([$goal_id, $uid]);
                    $newTotal = floatval($stmtNew->fetchColumn());

                    $prevTotal = $newTotal - $amount;

                    if ($prevTotal < $targetAmount && $newTotal >= $targetAmount) {
                        require_once __DIR__ . '/../../../includes/push_sender.php';
                        sendGoalAchieved($pdo, $uid, $goalName, $targetAmount);
                    }
                }
            }
        } catch (Exception $e) {}
        
        echo json_encode(['status' => 'success', 'message' => 'Transaction recorded successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to record transaction.']);
    }
}

function handle_get_history($pdo) {
    $uid = $_SESSION['user_id'];
    $goal_id = isset($_GET['goal_id']) && $_GET['goal_id'] !== 'all' ? intval($_GET['goal_id']) : null;
    
    try {
        if ($goal_id) {
            verify_ownership($pdo, 'savings_goals', $goal_id, $uid, 'get_history');
            $stmt = $pdo->prepare("
                SELECT t.id, t.amount, t.type, t.transaction_date, t.notes, g.goal_name 
                FROM savings_transactions t
                JOIN savings_goals g ON t.goal_id = g.id
                WHERE t.user_id = ? AND t.goal_id = ?
                ORDER BY t.transaction_date DESC, t.id DESC
            ");
            $stmt->execute([$uid, $goal_id]);
        } else {
            $stmt = $pdo->prepare("
                SELECT t.id, t.amount, t.type, t.transaction_date, t.notes, g.goal_name 
                FROM savings_transactions t
                JOIN savings_goals g ON t.goal_id = g.id
                WHERE t.user_id = ?
                ORDER BY t.transaction_date DESC, t.id DESC
            ");
            $stmt->execute([$uid]);
        }
        $history = $stmt->fetchAll();
        
        echo json_encode(['status' => 'success', 'data' => $history]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch history.']);
    }
}

function handle_get_average_expense($pdo) {
    $uid = $_SESSION['user_id'];
    try {
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount) / 3, 0) as avg_expense
            FROM expenses
            WHERE user_id = ?
              AND entry_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
        ");
        $stmt->execute([$uid]);
        $row = $stmt->fetch();
        $avg = floatval($row['avg_expense'] ?? 0);
        
        if ($avg <= 0) {
            $stmtAll = $pdo->prepare("
                SELECT COALESCE(SUM(amount) / GREATEST(1, COUNT(DISTINCT DATE_FORMAT(entry_date, '%Y-%m'))), 0) as avg_expense
                FROM expenses
                WHERE user_id = ?
            ");
            $stmtAll->execute([$uid]);
            $rowAll = $stmtAll->fetch();
            $avg = floatval($rowAll['avg_expense'] ?? 0);
        }
        
        echo json_encode(['status' => 'success', 'avg_expense' => $avg]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to calculate average expense']);
    }
}
