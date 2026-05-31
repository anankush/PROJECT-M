<?php
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/csrf.php';
require_once '../../includes/functions.php';
require_once '../includes/handlers/sav_handlers.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'check_session') {
    verify_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
}

if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}
check_session_timeout();

if (!isset($_SESSION['sav_db_migrated'])) {
    check_savings_db_migration($pdo);
    $_SESSION['sav_db_migrated'] = true;
}

switch ($action) {
    case 'check_session':
        echo json_encode(['is_user' => true, 'email' => $_SESSION['user_name'], 'currency' => $_SESSION['currency'] ?? '₹']);
        break;
    case 'get_goals':
        handle_get_goals($pdo);
        break;
    case 'add_goal':
        handle_add_goal($pdo);
        break;
    case 'update_goal':
        handle_update_goal($pdo);
        break;
    case 'delete_goal':
        handle_delete_goal($pdo);
        break;
    case 'add_deposit':
        handle_add_deposit($pdo);
        break;
    case 'get_history':
        handle_get_history($pdo);
        break;
    case 'get_average_expense':
        handle_get_average_expense($pdo);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
        break;
}
