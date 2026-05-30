<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'get_admin_stats') {
    try {
        // Total Users
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $total_users = $stmt->fetch()['total'];

        // Platform Total Expenditure
        $stmt = $pdo->query("SELECT SUM(amount) as platform_total FROM expenses");
        $platform_total = $stmt->fetch()['platform_total'] ?? 0;

        // User Breakdown
        $stmt = $pdo->query("
            SELECT u.id, u.email, 
                   COALESCE(SUM(e.amount), 0) as total_spent 
            FROM users u 
            LEFT JOIN expenses e ON u.id = e.user_id 
            GROUP BY u.id, u.email
        ");
        $user_breakdown = $stmt->fetchAll();

        echo json_encode([
            'status' => 'success',
            'total_users' => $total_users,
            'daily_active' => 'N/A', // Assuming no robust session tracking for now
            'platform_total' => $platform_total,
            'user_breakdown' => $user_breakdown
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    exit;
}

if ($action === 'delete_user') {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $data['target_user_id'] ?? 0;
    $csrf = $data['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit;
    }

    try {
        // Cascading deletes should be handled by foreign keys, but we can do it manually to be safe.
        // Actually, the database schema might not have ON DELETE CASCADE. Let's delete manually.
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("DELETE FROM expenses WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        $stmt = $pdo->prepare("DELETE FROM savings_transactions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        $stmt = $pdo->prepare("DELETE FROM savings_goals WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        $stmt = $pdo->prepare("DELETE FROM categories WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'User and all data deleted']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete user']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
exit;
