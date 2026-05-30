<?php
// Sav/includes/handlers/sav_handlers.php

function handle_get_goals($pdo) {
    $uid = $_SESSION['user_id'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT g.id, g.goal_name, g.target_amount, g.deadline, g.created_at,
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

    if (empty($goal_name) || $target_amount <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Valid Goal name and Target Amount are required.']);
        return;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO savings_goals (user_id, goal_name, target_amount, deadline) VALUES (?, ?, ?, ?)");
        $stmt->execute([$uid, $goal_name, $target_amount, $deadline]);
        echo json_encode(['status' => 'success', 'message' => 'Goal created successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create goal.']);
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
    $type = $input['type'] ?? 'deposit'; // deposit or withdraw
    $date = $input['date'] ?? date('Y-m-d');

    // Validate date format (YYYY-MM-DD)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $date = date('Y-m-d');
    }

    if ($goal_id <= 0 || $amount <= 0 || !in_array($type, ['deposit', 'withdraw'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input for transaction.']);
        return;
    }

    try {
        // Verify goal belongs to user
        $stmtCheck = $pdo->prepare("SELECT id FROM savings_goals WHERE id = ? AND user_id = ?");
        $stmtCheck->execute([$goal_id, $uid]);
        if ($stmtCheck->rowCount() === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Goal not found.']);
            return;
        }

        $stmt = $pdo->prepare("INSERT INTO savings_transactions (goal_id, user_id, amount, type, transaction_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$goal_id, $uid, $amount, $type, $date]);
        
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
            $stmt = $pdo->prepare("
                SELECT t.id, t.amount, t.type, t.transaction_date, g.goal_name 
                FROM savings_transactions t
                JOIN savings_goals g ON t.goal_id = g.id
                WHERE t.user_id = ? AND t.goal_id = ?
                ORDER BY t.transaction_date DESC, t.id DESC
            ");
            $stmt->execute([$uid, $goal_id]);
        } else {
            $stmt = $pdo->prepare("
                SELECT t.id, t.amount, t.type, t.transaction_date, g.goal_name 
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
