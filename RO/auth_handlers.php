<?php
require_once __DIR__ . '/../security.php';
require_once __DIR__ . '/../db.php';

function handle_user_login($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = sanitize_input($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Username and password required']);
        return;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!check_rate_limit('user_login', $ip)) {
        echo json_encode(['status' => 'error', 'message' => 'Too many attempts. Please try again later.']);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        $verify = $user ? verify_password($password, $user['password']) : false;
        if ($user && $verify) {
            if ($verify === 'needs_upgrade' || password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                upgrade_password($user['id'], $password, $pdo);
            }

            regenerate_session();
            unset($_SESSION['csrf_token']);
            generate_csrf_token();

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['last_activity'] = time();

            $update = $pdo->prepare("UPDATE users SET last_active_at = CURRENT_TIMESTAMP WHERE id = ?");
            $update->execute([$user['id']]);

            clear_attempts('user_login', $ip);
            echo json_encode(['status' => 'success', 'message' => 'Logged in successfully']);
        } else {
            record_attempt('user_login', $ip);
            echo json_encode(['status' => 'error', 'message' => 'Invalid credentials']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Login failed. Please try again.']);
    }
}

function validate_password_strength($password) {
    if (strlen($password) < 8) return 'Password must be at least 8 characters';
    if (!preg_match('/[a-z]/', $password)) return 'Password must contain lowercase letter';
    if (!preg_match('/[A-Z]/', $password)) return 'Password must contain uppercase letter';
    if (!preg_match('/[0-9]/', $password)) return 'Password must contain a number';
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) return 'Password must contain special character';
    return true;
}

function handle_user_register($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = sanitize_input($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Username and password required']);
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
        return;
    }

    if (!validate_email_domain($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Please use genuine Email Address']);
        return;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!check_rate_limit('user_register', $ip)) {
        echo json_encode(['status' => 'error', 'message' => 'Too many attempts. Please try again later.']);
        return;
    }

    $strength_check = validate_password_strength($password);
    if ($strength_check !== true) {
        echo json_encode(['status' => 'error', 'message' => $strength_check]);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            record_attempt('user_register', $ip);
            echo json_encode(['status' => 'error', 'message' => 'Username already exists.']);
            return;
        }

        $hashedPassword = hash_password($password);
        $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        $stmt->execute([$email, $hashedPassword]);
        clear_attempts('user_register', $ip);
        echo json_encode(['status' => 'success', 'message' => 'Registered successfully!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Registration failed. Please try again.']);
    }
}

function handle_user_logout($pdo) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    echo json_encode(['status' => 'success']);
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

    $strength_check = validate_password_strength($new_password);
    if ($strength_check !== true) {
        echo json_encode(['status' => 'error', 'message' => $strength_check]);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user || !verify_password($old_password, $user['password'])) {
            echo json_encode(['status' => 'error', 'message' => 'Incorrect current password']);
            return;
        }

        $newHash = hash_password($new_password);
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->execute([$newHash, $_SESSION['user_id']]);
        regenerate_session();
        unset($_SESSION['csrf_token']);
        generate_csrf_token();
        echo json_encode(['status' => 'success', 'message' => 'Password updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update password.']);
    }
}

function handle_request_password_reset($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = sanitize_input($input['email'] ?? '');
    $role = sanitize_input($input['role'] ?? 'user');

    if (empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Username is required']);
        return;
    }

    try {
        if ($role === 'admin') {
            $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE email = ?");
            $stmt->execute([$email]);
            if (!$stmt->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'Not registered yet. Register now.']);
                return;
            }
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if (!$stmt->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'Not registered yet. Register now.']);
                return;
            }
        }

        $cleanup = $pdo->prepare("DELETE FROM password_resets WHERE email = ? AND expires_at < NOW()");
        $cleanup->execute([$email]);

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!check_rate_limit('otp_request_' . $email, $ip, 3)) {
            echo json_encode(['status' => 'error', 'message' => 'Too many OTP requests. Please wait before requesting again.']);
            return;
        }
        record_attempt('otp_request_' . $email, $ip);

        $check = $pdo->prepare("SELECT expires_at FROM password_resets WHERE email = ? AND expires_at > NOW()");
        $check->execute([$email]);
        $existing = $check->fetch();

        if ($existing) {
            $remaining = strtotime($existing['expires_at']) - time();
            echo json_encode([
                'status' => 'rate_limited',
                'message' => 'OTP already sent. Please wait before requesting a new one.',
                'remaining' => $remaining
            ]);
            return;
        }

        $otp = sprintf("%06d", mt_rand(1, 999999));
        $now = date('Y-m-d H:i:s');
        $expires_at = date('Y-m-d H:i:s', strtotime('+2 minutes'));

        $stmt = $pdo->prepare("INSERT INTO password_resets (email, otp, expires_at, created_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$email, $otp, $expires_at, $now]);

        $email_body = "Your password reset OTP is: $otp\n\nIt will expire in 2 minutes.\n\nIf you did not request this, please ignore this email.";
        $sent = send_email($email, 'Expense Management - Password Reset OTP', $email_body);

        $remaining = strtotime($expires_at) - time();
        if ($sent) {
            echo json_encode(['status' => 'success', 'message' => 'OTP sent to your email.', 'remaining' => $remaining]);
        } else {
            echo json_encode(['status' => 'success', 'message' => 'OTP generated. Check your email.', 'remaining' => $remaining]);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error. Please try again.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'An error occurred. Please try again.']);
    }
}

function handle_verify_otp_only($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = sanitize_input($input['email'] ?? '');
    $otp = sanitize_input($input['otp'] ?? '');

    if (empty($email) || empty($otp)) {
        echo json_encode(['status' => 'error', 'message' => 'OTP is required']);
        return;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!check_rate_limit('otp_verify_' . $email, $ip, 3)) {
        echo json_encode(['status' => 'error', 'message' => 'Too many OTP attempts. Please wait before trying again.']);
        return;
    }

    try {
        $pdo->prepare("DELETE FROM password_resets WHERE email = ? AND expires_at < NOW()")->execute([$email]);

        $current_time = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND otp = ? AND expires_at >= ?");
        $stmt->execute([$email, $otp, $current_time]);

        if ($stmt->fetch()) {
            $del = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
            $del->execute([$email]);

            clear_attempts('otp_verify_' . $email, $ip);
            $_SESSION['otp_verified_email'] = $email;
            echo json_encode(['status' => 'success', 'message' => 'OTP Verified. Proceed to reset password.']);
        } else {
            record_attempt('otp_verify_' . $email, $ip);
            echo json_encode(['status' => 'error', 'message' => 'Invalid or expired OTP']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Verification failed.']);
    }
}

function handle_reset_password_final($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = sanitize_input($input['email'] ?? '');
    $new_password = $input['new_password'] ?? '';
    $role = sanitize_input($input['role'] ?? 'user');

    if (empty($email) || empty($new_password)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        return;
    }

    if (!isset($_SESSION['otp_verified_email']) || $_SESSION['otp_verified_email'] !== $email) {
        echo json_encode(['status' => 'error', 'message' => 'OTP verification required before password reset']);
        return;
    }

    $strength_check = validate_password_strength($new_password);
    if ($strength_check !== true) {
        echo json_encode(['status' => 'error', 'message' => $strength_check]);
        return;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!check_rate_limit('otp_verify_' . $email, $ip)) {
        echo json_encode(['status' => 'error', 'message' => 'Too many attempts. Please request a new OTP.']);
        return;
    }

    try {
        $hashed = hash_password($new_password);

        if ($role === 'admin') {
            $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE email = ?");
            $stmt->execute([$hashed, $email]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashed, $email]);
        }

        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->execute([$email]);

        clear_attempts('otp_verify_' . $email, $ip);
        unset($_SESSION['otp_verified_email']);
        echo json_encode(['status' => 'success', 'message' => 'Password reset successfully!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Reset failed.']);
    }
}

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
        echo json_encode(['status' => 'success', 'message' => 'Settings saved successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save settings.']);
    }
}

function handle_admin_login($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = sanitize_input($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $admin_key = $input['admin_key'] ?? '';

    if (empty($email) || empty($password) || empty($admin_key)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        return;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!check_rate_limit('admin_login', $ip)) {
        echo json_encode(['status' => 'error', 'message' => 'Too many attempts. Please try again later.']);
        return;
    }

    try {
        $keyStmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'super_password'");
        $keyStmt->execute();
        $setting = $keyStmt->fetch();

        if (!$setting || !password_verify($admin_key, $setting['setting_value'])) {
            record_attempt('admin_login', $ip);
            echo json_encode(['status' => 'error', 'message' => 'Invalid ADMIN KEY']);
            return;
        }

        $stmt = $pdo->prepare("SELECT id, email, password FROM admin_users WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if (!$admin) {
            record_attempt('admin_login', $ip);
            echo json_encode(['status' => 'error', 'message' => 'Invalid admin credentials']);
            return;
        }

        $verify = verify_password($password, $admin['password']);
        if ($verify) {
            if ($verify === 'needs_upgrade' || password_needs_rehash($admin['password'], PASSWORD_DEFAULT)) {
                upgrade_password($admin['id'], $password, $pdo, 'admin');
            }

            regenerate_session();
            unset($_SESSION['csrf_token']);
            generate_csrf_token();

            $_SESSION['is_admin'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['email'] = $admin['email'];
            $_SESSION['last_activity'] = time();

            clear_attempts('admin_login', $ip);
            echo json_encode(['status' => 'success', 'message' => 'Admin logged in successfully']);
        } else {
            record_attempt('admin_login', $ip);
            echo json_encode(['status' => 'error', 'message' => 'Invalid admin credentials']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Login failed.']);
    }
}

function handle_admin_change_key($pdo) {
    if (!isset($_SESSION['is_admin'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $old_key = $input['old_key'] ?? '';
    $new_key = $input['new_key'] ?? '';

    if (empty($old_key) || empty($new_key)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        return;
    }

    if (strlen($new_key) < 6) {
        echo json_encode(['status' => 'error', 'message' => 'New key must be at least 6 characters']);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'super_password'");
        $stmt->execute();
        $setting = $stmt->fetch();

        if (!$setting || !password_verify($old_key, $setting['setting_value'])) {
            echo json_encode(['status' => 'error', 'message' => 'Current admin key is incorrect']);
            return;
        }

        $hashedKey = hash_password($new_key);
        $update = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'super_password'");
        $update->execute([$hashedKey]);

        echo json_encode(['status' => 'success', 'message' => 'Admin key changed successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to change admin key']);
    }
}

function handle_admin_register($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = sanitize_input($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $admin_key = $input['admin_key'] ?? '';

    if (empty($email) || empty($password) || empty($admin_key)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
        return;
    }

    if (!validate_email_domain($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Please use genuine Email Address']);
        return;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!check_rate_limit('admin_register', $ip)) {
        echo json_encode(['status' => 'error', 'message' => 'Too many attempts. Please try again later.']);
        return;
    }

    $strength_check = validate_password_strength($password);
    if ($strength_check !== true) {
        echo json_encode(['status' => 'error', 'message' => $strength_check]);
        return;
    }

    $keyStmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'super_password'");
    $keyStmt->execute();
    $setting = $keyStmt->fetch();

    if (!$setting || !password_verify($admin_key, $setting['setting_value'])) {
        record_attempt('admin_register', $ip);
        echo json_encode(['status' => 'error', 'message' => 'Invalid ADMIN KEY. Registration failed.']);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            record_attempt('admin_register', $ip);
            echo json_encode(['status' => 'error', 'message' => 'Admin email already exists.']);
            return;
        }

        $hashedPassword = hash_password($password);
        $stmt = $pdo->prepare("INSERT INTO admin_users (email, password) VALUES (?, ?)");
        $stmt->execute([$email, $hashedPassword]);
        clear_attempts('admin_register', $ip);
        echo json_encode(['status' => 'success', 'message' => 'Admin Registered!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Registration failed.']);
    }
}

function handle_admin_logout($pdo) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    echo json_encode(['status' => 'success']);
}

function handle_admin_change_password($pdo) {
    if (!isset($_SESSION['is_admin']) || !isset($_SESSION['admin_id'])) {
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

    $strength_check = validate_password_strength($new_password);
    if ($strength_check !== true) {
        echo json_encode(['status' => 'error', 'message' => $strength_check]);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, password FROM admin_users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();

        if (!$admin || !verify_password($old_password, $admin['password'])) {
            echo json_encode(['status' => 'error', 'message' => 'Incorrect current password']);
            return;
        }

        $newHash = hash_password($new_password);
        $update = $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
        $update->execute([$newHash, $_SESSION['admin_id']]);
        regenerate_session();
        unset($_SESSION['csrf_token']);
        generate_csrf_token();
        echo json_encode(['status' => 'success', 'message' => 'Password updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update password.']);
    }
}
