<?php

require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/csrf.php';
require_once '../../includes/functions.php';
require_once '../../includes/id_obfuscate.php';
require_once '../includes/Model.php';

require_once '../includes/handlers/category_handlers.php';
require_once '../includes/handlers/record_handlers.php';
require_once '../includes/handlers/user_handlers.php';

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

switch ($action) {
    case 'check_session':
        handle_check_session($pdo);
        break;
    
    
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
    case 'clear_category_budget':
        handle_clear_category_budget($pdo);
        break;
    case 'get_overall_budget':
        handle_get_overall_budget($pdo);
        break;
    case 'update_overall_budget':
        handle_update_overall_budget($pdo);
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
    case 'get_cumulative_stats':
        handle_get_cumulative_stats($pdo);
        break;
    case 'get_total_expenditure':
        handle_get_total_expenditure($pdo);
        break;
        
    
    case 'update_settings':
        handle_update_settings($pdo);
        break;
    case 'change_password':
        handle_change_password($pdo);
        break;
    case 'delete_user_account':
        handle_delete_user_account($pdo);
        break;
    case 'send_reset_otp':
        handle_send_reset_otp($pdo);
        break;
    case 'verify_reset_otp':
        handle_verify_reset_otp($pdo);
        break;
    case 'reset_password_with_otp':
        handle_reset_password_with_otp($pdo);
        break;
        
    
    case 'check_existing_data':
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
        $uid = $_SESSION['user_id'];
        
        $cStmt = $pdo->prepare("SELECT COUNT(*) FROM user_categories WHERE user_id = ?");
        $cStmt->execute([$uid]);
        $cats = (int)$cStmt->fetchColumn();

        $gStmt = $pdo->prepare("SELECT COUNT(*) FROM savings_goals WHERE user_id = ?");
        $gStmt->execute([$uid]);
        $goals = (int)$gStmt->fetchColumn();

        echo json_encode(['status' => 'success', 'has_data' => ($cats > 0 || $goals > 0)]);
        break;
    case 'export_data':
        handle_export_data($pdo);
        break;
    case 'import_data':
        handle_import_data($pdo);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
        break;
}
