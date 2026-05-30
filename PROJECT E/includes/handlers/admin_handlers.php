<?php
require_once __DIR__ . '/../security.php';
require_once __DIR__ . '/../db.php';

function handle_check_session($pdo) {
    $currency = '₹';
    $language = 'en';
    $total_budget = 0.00;

    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT currency, language, total_budget FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $u = $stmt->fetch();
        if ($u) {
            $currency = $u['currency'];
            $language = $u['language'];
            $total_budget = $u['total_budget'];
        }
    } elseif (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] && isset($_SESSION['admin_id'])) {
        $stmt = $pdo->prepare("SELECT currency, language FROM admin_users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $a = $stmt->fetch();
        if ($a) {
            $currency = $a['currency'];
            $language = $a['language'];
        }
    }

    echo json_encode([
        'status' => 'success',
        'is_admin' => isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true,
        'is_user' => isset($_SESSION['user_id']),
        'email' => $_SESSION['email'] ?? null,
        'currency' => $currency,
        'language' => $language,
        'total_budget' => $total_budget
    ]);
}

function handle_get_admin_stats($pdo) {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    try {
        $month = sanitize_input($_GET['month'] ?? '');

        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $total_users = $stmt->fetch()['count'];

        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE DATE(last_active_at) = CURDATE()");
        $daily_active = $stmt->fetch()['count'];

        if (!empty($month)) {
            $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE entry_date LIKE ?");
            $stmt->execute([$month . '-%']);
            $platform_total = $stmt->fetch()['total'] ?: 0;

            $stmt = $pdo->prepare("
                SELECT u.id, u.email, u.currency, COALESCE(SUM(e.amount), 0) as total_spent, u.last_active_at
                FROM users u
                LEFT JOIN expenses e ON u.id = e.user_id AND e.entry_date LIKE ?
                GROUP BY u.id
                ORDER BY total_spent DESC
            ");
            $stmt->execute([$month . '-%']);
            $user_breakdown = $stmt->fetchAll();
        } else {
            $stmt = $pdo->query("SELECT SUM(amount) as total FROM expenses");
            $platform_total = $stmt->fetch()['total'] ?: 0;

            $stmt = $pdo->query("
                SELECT u.id, u.email, u.currency, COALESCE(SUM(e.amount), 0) as total_spent, u.last_active_at
                FROM users u
                LEFT JOIN expenses e ON u.id = e.user_id
                GROUP BY u.id
                ORDER BY total_spent DESC
            ");
            $user_breakdown = $stmt->fetchAll();
        }

        foreach ($user_breakdown as &$user) {
            $user['encoded_id'] = encode_id($user['id']);
        }

        echo json_encode([
            'status' => 'success',
            'total_users' => $total_users,
            'daily_active' => $daily_active,
            'platform_total' => $platform_total,
            'user_breakdown' => $user_breakdown
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch stats.']);
    }
}

function handle_update_admin_settings($pdo) {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin'] || !isset($_SESSION['admin_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $currency = sanitize_input($input['currency'] ?? '₹');
    $language = sanitize_input($input['language'] ?? 'en');

    try {
        $stmt = $pdo->prepare("UPDATE admin_users SET currency = ?, language = ? WHERE id = ?");
        $stmt->execute([$currency, $language, $_SESSION['admin_id']]);
        echo json_encode(['status' => 'success', 'message' => 'Settings saved successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save settings.']);
    }
}

function handle_delete_user_account($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

    if ($is_admin && isset($input['target_user_id'])) {
        $uid = decode_id(sanitize_input($input['target_user_id']));
        if (!$uid) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
            return;
        }
    } elseif (isset($_SESSION['user_id'])) {
        $password = $input['password'] ?? '';
        if (empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Password is required to delete your account']);
            return;
        }
        $uid = $_SESSION['user_id'];
        $user = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $user->execute([$uid]);
        $user = $user->fetch();
        if (!$user || !verify_password($password, $user['password'])) {
            echo json_encode(['status' => 'error', 'message' => 'Incorrect password']);
            return;
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM expenses WHERE user_id = ?")->execute([$uid]);
        $pdo->prepare("DELETE FROM category_monthly_budgets WHERE user_id = ?")->execute([$uid]);
        $pdo->prepare("DELETE FROM user_categories WHERE user_id = ?")->execute([$uid]);
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
        $pdo->commit();

        unset($_SESSION['user_id']);
        unset($_SESSION['email']);
        echo json_encode(['status' => 'success', 'message' => 'Account deleted successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete account.']);
    }
}

function handle_delete_admin_account($pdo) {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin'] || !isset($_SESSION['admin_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    try {
        $aid = $_SESSION['admin_id'];
        $pdo->prepare("DELETE FROM admin_users WHERE id = ?")->execute([$aid]);

        unset($_SESSION['is_admin']);
        unset($_SESSION['admin_id']);
        echo json_encode(['status' => 'success', 'message' => 'Admin account deleted successfully']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete admin account.']);
    }
}
