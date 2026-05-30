<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/handlers/auth_handlers.php';
require_once __DIR__ . '/handlers/record_handlers.php';
require_once __DIR__ . '/handlers/category_handlers.php';
require_once __DIR__ . '/handlers/admin_handlers.php';

session_start_secure();
set_security_headers();
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

$read_only_actions = [
    'check_session', 'get_categories', 'get_records',
    'get_total_expenditure', 'get_cumulative_stats',
    'get_admin_stats', 'get_user_categories_admin', 'get_note'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !in_array($action, $read_only_actions)) {
    $csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    verify_csrf_token($csrf_token);
}

switch ($action) {
    case 'user_login':            handle_user_login($pdo); break;
    case 'user_register':         handle_user_register($pdo); break;
    case 'user_logout':           handle_user_logout($pdo); break;
    case 'change_password':       handle_change_password($pdo); break;
    case 'request_password_reset': handle_request_password_reset($pdo); break;
    case 'verify_otp_only':       handle_verify_otp_only($pdo); break;
    case 'reset_password_final':  handle_reset_password_final($pdo); break;
    case 'update_settings':       handle_update_settings($pdo); break;
    case 'admin_login':           handle_admin_login($pdo); break;
    case 'admin_register':        handle_admin_register($pdo); break;
    case 'admin_logout':          handle_admin_logout($pdo); break;
    case 'admin_change_password': handle_admin_change_password($pdo); break;
    case 'admin_change_key':     handle_admin_change_key($pdo); break;

    case 'get_records':           handle_get_records($pdo); break;
    case 'add_record':            handle_add_record($pdo); break;
    case 'update_record':         handle_update_record($pdo); break;
    case 'delete_record':         handle_delete_record($pdo); break;
    case 'import_data':           handle_import_data($pdo); break;
    case 'export_data':           handle_export_data($pdo); break;
    case 'get_total_expenditure': handle_get_total_expenditure($pdo); break;
    case 'get_cumulative_stats':  handle_get_cumulative_stats($pdo); break;

    case 'get_categories':        handle_get_categories($pdo); break;
    case 'add_category':          handle_add_category($pdo); break;
    case 'rename_category':       handle_rename_category($pdo); break;
    case 'delete_category':       handle_delete_category($pdo); break;
    case 'update_category_budget': handle_update_category_budget($pdo); break;
    case 'get_user_categories_admin': handle_get_user_categories_admin($pdo); break;
    case 'get_note':              handle_get_note($pdo); break;
    case 'save_note':             handle_save_note($pdo); break;

    case 'check_session':         handle_check_session($pdo); break;
    case 'get_admin_stats':       handle_get_admin_stats($pdo); break;
    case 'update_admin_settings': handle_update_admin_settings($pdo); break;
    case 'delete_user_account':   handle_delete_user_account($pdo); break;
    case 'delete_admin_account':  handle_delete_admin_account($pdo); break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
}
