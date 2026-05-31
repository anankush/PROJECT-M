<?php

function handle_update_settings($pdo)
{
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

        $_SESSION['currency'] = $currency;

        echo json_encode(['status' => 'success', 'message' => 'Settings saved successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save settings.']);
    }
}

function handle_change_password($pdo)
{
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

    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $new_password)) {
        echo json_encode(['status' => 'error', 'message' => 'Password must contain at least 8 characters, 1 uppercase, 1 lowercase, 1 number, and 1 special character']);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'Incorrect current password']);
            return;
        }

        $isValid = password_verify($old_password, $user['password']);

        if (!$isValid) {
            echo json_encode(['status' => 'error', 'message' => 'Incorrect current password']);
            return;
        }

        $newHash = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->execute([$newHash, $_SESSION['user_id']]);

        session_regenerate_id(true);
        unset($_SESSION['csrf_token']);
        generate_csrf_token();

        echo json_encode(['status' => 'success', 'message' => 'Password updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update password.']);
    }
}

function handle_delete_user_account($pdo)
{
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

        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'Incorrect password. Account deletion aborted.']);
            return;
        }

        $isValid = password_verify($password, $user['password']);

        if (!$isValid) {
            echo json_encode(['status' => 'error', 'message' => 'Incorrect password. Account deletion aborted.']);
            return;
        }

        $email = $user['email'];

        $pdo->beginTransaction();

        $pdo->prepare("DELETE FROM expenses WHERE user_id = ?")->execute([$uid]);
        $pdo->prepare("DELETE FROM user_notes WHERE user_id = ?")->execute([$uid]);
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
        $pdo->commit();

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

function handle_check_session($pdo)
{
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['is_user' => false]);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT email, currency, total_budget FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo json_encode([
                'status' => 'success',
                'is_user' => true,
                'email' => $user['email'],
                'currency' => $user['currency'],
                'total_budget' => $user['total_budget']
            ]);
        } else {
            echo json_encode(['is_user' => false]);
        }
    } catch (PDOException $e) {
        echo json_encode(['is_user' => false]);
    }
}

function handle_send_reset_otp($pdo)
{
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    try {
        $uid = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$uid]);
        $user = $stmt->fetch();
        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            return;
        }
        $email = $user['email'];

        $otp = sprintf("%06d", random_int(100000, 999999));
        $pdo->exec("DELETE FROM password_resets WHERE expires_at <= NOW()");
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
        $stmt = $pdo->prepare("INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 2 MINUTE))");
        $stmt->execute([$email, $otp]);
        $_SESSION['reset_email'] = $email;
        require_once '../../includes/mailer.php';
        $body = get_otp_email_body($otp, true);
        send_email($email, "Password Reset OTP", $body);

        echo json_encode(['status' => 'success', 'message' => 'OTP sent successfully']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to process request. Please try again.']);
    }
}

function handle_verify_reset_otp($pdo)
{
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $otp = trim($input['otp'] ?? '');

    if (empty($otp) || !preg_match('/^\d{6}$/', $otp)) {
        echo json_encode(['status' => 'error', 'message' => 'Valid 6-digit OTP required']);
        return;
    }

    $email = $_SESSION['reset_email'] ?? '';
    if (empty($email)) {
        $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        $email = $user ? $user['email'] : '';
    }

    if (empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Email not found']);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND otp = ? AND expires_at > NOW() LIMIT 1");
        $stmt->execute([$email, $otp]);

        if ($stmt->fetch()) {
            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

            $_SESSION['otp_verified'] = true;
            echo json_encode(['status' => 'success', 'message' => 'OTP verified successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid or expired OTP']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Verification failed.']);
    }
}

function handle_reset_password_with_otp($pdo)
{
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    if (empty($_SESSION['otp_verified'])) {
        echo json_encode(['status' => 'error', 'message' => 'Please verify OTP first']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $new_password = $input['new_password'] ?? '';

    if (empty($new_password)) {
        echo json_encode(['status' => 'error', 'message' => 'Password is required']);
        return;
    }

    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $new_password)) {
        echo json_encode(['status' => 'error', 'message' => 'Password must contain at least 8 characters, 1 uppercase, 1 lowercase, 1 number, and 1 special character']);
        return;
    }

    try {
        $newHash = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->execute([$newHash, $_SESSION['user_id']]);

        unset($_SESSION['otp_verified']);
        unset($_SESSION['reset_email']);
        session_regenerate_id(true);
        unset($_SESSION['csrf_token']);
        generate_csrf_token();

        echo json_encode(['status' => 'success', 'message' => 'Password reset successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to reset password.']);
    }
}
