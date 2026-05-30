<?php
// Exp/api/api.php
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/csrf.php';
require_once '../../includes/functions.php';
require_once '../../includes/id_obfuscate.php';

require_once '../includes/handlers/category_handlers.php';
require_once '../includes/handlers/record_handlers.php';

header('Content-Type: application/json');

// All API requests MUST have CSRF except checking session status
$action = $_GET['action'] ?? '';
if ($action !== 'check_session') {
    verify_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

switch ($action) {
    case 'check_session':
        handle_check_session($pdo);
        break;
    
    // Categories
    case 'get_categories':
        handle_get_categories($pdo);
        break;
    case 'add_category':
        handle_add_category($pdo);
        break;
    case 'rename_category':
        handle_rename_category($pdo);
        break;
    case 'delete_category':
        handle_delete_category($pdo);
        break;
    case 'update_category_budget':
        handle_update_category_budget($pdo);
        break;
    case 'get_user_categories_admin':
        handle_get_user_categories_admin($pdo);
        break;
    case 'get_note':
        handle_get_note($pdo);
        break;
    case 'save_note':
        handle_save_note($pdo);
        break;

    // Records
    case 'get_records':
        handle_get_records($pdo);
        break;
    case 'add_record':
        handle_add_record($pdo);
        break;
    case 'update_record':
        handle_update_record($pdo);
        break;
    case 'delete_record':
        handle_delete_record($pdo);
        break;
    case 'save_schema':
        handle_save_schema($pdo);
        break;
    case 'get_cumulative_stats':
        handle_get_cumulative_stats($pdo);
        break;
    case 'get_total_expenditure':
        handle_get_total_expenditure($pdo);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
        break;
}
