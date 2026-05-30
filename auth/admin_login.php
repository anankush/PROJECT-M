<?php
// ProjectM/auth/admin_login.php
require_once '../includes/db.php';
require_once '../includes/csrf.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
set_security_headers();

if (isset($_SESSION['admin_id'])) {
    header('Location: ../dashboard/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $admin_key = $input['admin_key'] ?? '';

    if (empty($email) || empty($password) || empty($admin_key)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields required']);
        exit;
    }

    try {
        // Verify admin super key
        $keyStmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'super_password' LIMIT 1");
        $keyStmt->execute();
        $setting = $keyStmt->fetch();

        if (!$setting || !password_verify($admin_key, $setting['setting_value'])) {
            password_verify('dummy', '$2y$10$dummyhashtopreventtimingattacks.......');
            echo json_encode(['status' => 'error', 'message' => 'Invalid Admin Key']);
            exit;
        }

        // Verify admin credentials
        $stmt = $pdo->prepare("SELECT id, email, password, currency FROM admin_users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']      = $admin['id'];
            $_SESSION['role']          = 'admin';
            $_SESSION['user_name']     = 'Administrator';
            $_SESSION['user_email']    = $admin['email'];
            $_SESSION['currency']      = $admin['currency'] ?? '₹';
            $_SESSION['last_activity'] = time();
            echo json_encode(['status' => 'success', 'redirect' => '../dashboard/index.php']);
            exit;
        }

        password_verify('dummy', '$2y$10$dummyhashtopreventtimingattacks.......');
        echo json_encode(['status' => 'error', 'message' => 'Invalid credentials']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Login failed. Please try again.']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Money Management</title>
    <?php echo get_csrf_meta_tag(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/glassmorphism.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <div class="aurora-bg">
        <div class="orb orb-1"></div><div class="orb orb-3"></div>
    </div>
    <div class="noise-overlay"></div>

    <div class="auth-container">
        <div class="glass-card auth-card fadeInUp">
            <div class="auth-logo">
                <h1>ADMIN PORTAL</h1>
                <p>Authorized personnel only.</p>
            </div>
            <form id="loginForm" onsubmit="handleAdminLogin(event)">
                <div class="form-group">
                    <label>Admin Email</label>
                    <input type="email" id="email" required placeholder="Enter admin email" autocomplete="email">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="password" required placeholder="Enter password" autocomplete="current-password">
                </div>
                <div class="form-group">
                    <label>Admin Key</label>
                    <input type="password" id="admin_key" required placeholder="Enter admin key" autocomplete="off">
                </div>
                <button type="submit" class="btn btn-primary auth-submit">Login as Admin</button>
            </form>
            <div class="auth-links">
                <a href="login.php">Back to User Login</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/csrf.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        async function handleAdminLogin(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Verifying...';
            btn.disabled = true;

            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const admin_key = document.getElementById('admin_key').value;

            try {
                const res = await fetch('admin_login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({email, password, admin_key})
                });
                const data = await res.json();
                if (data.status === 'success') {
                    window.location.href = data.redirect;
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Login failed', 'error');
            } finally {
                btn.innerHTML = 'Login as Admin';
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
