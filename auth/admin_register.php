<?php
require_once '../includes/db.php';
require_once '../includes/csrf.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
set_security_headers();

if (isset($_SESSION['admin_id'])) {
    header('Location: ../admin/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $admin_key = $input['admin_key'] ?? '';

    if (empty($email) || empty($password) || empty($admin_key)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
        exit;
    }

    if (is_disposable_email($email)) {
        log_security_event($pdo, $email, 'bypass_admin_tempmail_attempt');
        echo json_encode(['status' => 'error', 'message' => 'Temporary / Disposable email addresses are not allowed. Please use a real email provider.']);
        exit;
    }

    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password)) {
        echo json_encode(['status' => 'error', 'message' => 'Password must contain at least 8 characters, 1 uppercase, 1 lowercase, 1 number, and 1 special character']);
        exit;
    }

    try {
        $keyStmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'super_password' LIMIT 1");
        $keyStmt->execute();
        $setting = $keyStmt->fetch();

        if (!$setting || !password_verify($admin_key, $setting['setting_value'])) {
            password_verify('dummy', '$2y$10$dummyhashtopreventtimingattacks.......');
            echo json_encode(['status' => 'error', 'message' => 'Invalid Admin Key']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Admin email already registered']);
            exit;
        }

        $hashedPass = password_hash($password, PASSWORD_DEFAULT);
        $insert = $pdo->prepare("INSERT INTO admin_users (email, password) VALUES (?, ?)");
        $insert->execute([$email, $hashedPass]);

        $adminId = $pdo->lastInsertId();

        session_regenerate_id(true);
        $pdo->prepare("UPDATE admin_users SET active_session_id = ? WHERE id = ?")->execute([session_id(), $adminId]);
        unset($_SESSION['user_id']);
        $_SESSION['admin_id'] = $adminId;
        $_SESSION['role'] = 'admin';
        $_SESSION['is_admin'] = true;
        $_SESSION['user_name'] = 'Administrator';
        $_SESSION['user_email'] = $email;
        $_SESSION['currency'] = '₹';
        $_SESSION['last_activity'] = time();

        echo json_encode(['status' => 'success', 'redirect' => '../admin/index.php']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Registration failed. Please try again.']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="path-depth" content="1">
    <title>Admin Registration | PROJECT M</title>
    <?php echo get_csrf_meta_tag(); ?>
    <link rel="stylesheet" href="../assets/css/glassmorphism.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/auth.css?v=<?php echo time(); ?>">
    <script src="../assets/js/email_validator.js?v=<?php echo time(); ?>"></script>
</head>

<body>
    <a href="../index.php" class="back-home-btn">
        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"
            stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Back to Home
    </a>
    <div class="aurora-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-3"></div>
    </div>
    <div class="noise-overlay"></div>

    <div class="auth-container">
        <div class="glass-card auth-card fadeInUp" style="border-color: rgba(239, 68, 68, 0.3);">
            <div class="auth-avatar"
                style="background: linear-gradient(135deg, #ef4444, #991b1b); box-shadow: 0 10px 25px rgba(239, 68, 68, 0.4);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
            </div>
            <div class="auth-logo">
                <h1
                    style="background: linear-gradient(to right, #ef4444, #f87171); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                    Register Admin</h1>
                <p>Create a new administrative account.</p>
            </div>
            <form id="regForm" onsubmit="handleRegister(event)">
                <div class="form-group">
                    <label>Admin Email</label>
                    <input type="email" id="email" required placeholder="Enter admin email">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="password" required placeholder="Choose a strong password">
                </div>
                <div class="form-group">
                    <label>Admin Key</label>
                    <input type="password" id="admin_key" required placeholder="Enter master admin key">
                </div>
                <button type="submit" class="btn btn-primary auth-submit"
                    style="background: linear-gradient(135deg, #ef4444, #991b1b); box-shadow: 0 4px 20px rgba(239, 68, 68, 0.4);">Register
                    Admin</button>
            </form>
            <div class="auth-links">
                <a href="admin_login.php">Already have an admin account? Login</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/csrf.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        async function handleRegister(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            btn.innerHTML = 'Registering...';
            btn.disabled = true;

            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const admin_key = document.getElementById('admin_key').value;

            try {
                btn.innerHTML = 'Verifying Email...';
                const isTemp = await isDisposableEmail(email);
                if (isTemp) {
                    showToast('Temporary / Disposable email addresses are not allowed.', 'error');
                    btn.innerHTML = 'Register Admin';
                    btn.disabled = false;
                    return;
                }
            } catch (err) {
                console.error("Email verification skipped due to error:", err);
            }

            btn.innerHTML = 'Registering...';

            try {
                const res = await fetch('admin_register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ email, password, admin_key })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    window.location.href = data.redirect;
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Registration failed', 'error');
            } finally {
                btn.innerHTML = 'Register Admin';
                btn.disabled = false;
            }
        }
    </script>
</body>

</html>