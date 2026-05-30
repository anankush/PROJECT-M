<?php
// Exp/includes/handlers/user_handlers.php

function handle_update_settings($pdo) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $currency = sanitize_input($input['currency'] ?? '₹');
    $language = sanitize_input($input['language'] ?? 'en');
    $total_budget = isset($input['total_budget']) ? floatval($input['total_budget']) : null;

    try {
        if ($total_budget !== null) {
            $stmt = $pdo->prepare("UPDATE users SET currency = ?, language = ?, total_budget = ? WHERE id = ?");
            $stmt->execute([$currency, $language, $total_budget, $_SESSION['user_id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET currency = ?, language = ? WHERE id = ?");
            $stmt->execute([$currency, $language, $_SESSION['user_id']]);
        }
        
        // Update session currency
        $_SESSION['currency'] = $currency;
        
        echo json_encode(['status' => 'success', 'message' => 'Settings saved successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save settings.']);
    }
}

function handle_change_password($pdo) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $old_password = $input['old_password'] ?? '';
    $new_password = $input['new_password'] ?? '';

    if (empty($old_password) || empty($new_password)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        return;
    }

    // Basic strength check
    if (strlen($new_password) < 8) {
        echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters']);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        // Check if legacy plaintext or hashed
        $isValid = false;
        if ($user) {
            if ($old_password === $user['password']) {
                $isValid = true;
            } else {
                $isValid = password_verify($old_password, $user['password']);
            }
        }

        if (!$isValid) {
            echo json_encode(['status' => 'error', 'message' => 'Incorrect current password']);
            return;
        }

        $newHash = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->execute([$newHash, $_SESSION['user_id']]);
        
        // Regenerate CSRF/Session
        session_regenerate_id(true);
        unset($_SESSION['csrf_token']);
        generate_csrf_token();
        
        echo json_encode(['status' => 'success', 'message' => 'Password updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update password.']);
    }
}

function handle_delete_user_account($pdo) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $password = $input['password'] ?? '';

    if (empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Password is required to delete account']);
        return;
    }

    try {
        $uid = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT id, email, password FROM users WHERE id = ?");
        $stmt->execute([$uid]);
        $user = $stmt->fetch();

        $isValid = false;
        if ($user) {
            if ($password === $user['password']) {
                $isValid = true;
            } else {
                $isValid = password_verify($password, $user['password']);
            }
        }

        if (!$isValid) {
            echo json_encode(['status' => 'error', 'message' => 'Incorrect password. Account deletion aborted.']);
            return;
        }

        $email = $user['email'];

        $pdo->beginTransaction();

        // Tables without CASCADE
        $pdo->prepare("DELETE FROM expenses WHERE user_id = ?")->execute([$uid]);
        $pdo->prepare("DELETE FROM user_notes WHERE user_id = ?")->execute([$uid]);
        
        // Clean up resets
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

        // Main users table (will cascade to categories, budgets, savings goals, etc)
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);

        $pdo->commit();

        // Destroy session
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();

        echo json_encode(['status' => 'success', 'message' => 'Account deleted successfully']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete account.']);
    }
}
