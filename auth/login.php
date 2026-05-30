<?php
// ProjectM/auth/login.php
require_once '../includes/db.php';
require_once '../includes/csrf.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
set_security_headers();

if (isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])) {
    header('Location: ../dashboard/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Email and password required']);
        exit;
    }

    try {
        // Check Admin
        $stmt = $pdo->prepare("SELECT id, email, password, currency FROM admin_users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']    = $admin['id'];
            $_SESSION['role']        = 'admin';
            $_SESSION['user_name']   = 'Administrator';
            $_SESSION['user_email']  = $admin['email'];
            $_SESSION['currency']    = $admin['currency'] ?? '₹';
            $_SESSION['last_activity'] = time();
            echo json_encode(['status' => 'success', 'redirect' => '../dashboard/index.php']);
            exit;
        }

        // Check User
        $stmt = $pdo->prepare("SELECT id, email, password, currency FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            // Legacy plaintext upgrade
            if ($password === $user['password']) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newHash, $user['id']]);
                $isValid = true;
            } else {
                $isValid = password_verify($password, $user['password']);
            }

            if ($isValid) {
                session_regenerate_id(true);
                $_SESSION['user_id']     = $user['id'];
                $_SESSION['role']        = 'user';
                $_SESSION['user_name']   = explode('@', $user['email'])[0];
                $_SESSION['user_email']  = $user['email'];
                $_SESSION['currency']    = $user['currency'] ?? '₹';
                $_SESSION['last_activity'] = time();
                echo json_encode(['status' => 'success', 'redirect' => '../dashboard/index.php']);
                exit;
            }
        }

        // Constant-time failure response to prevent timing attacks
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
    <title>Login | PROJECT M</title>
    <?php echo get_csrf_meta_tag(); ?>
    <link rel="stylesheet" href="../assets/css/glassmorphism.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <div class="aurora-bg">
        <div class="orb orb-1"></div><div class="orb orb-2"></div><div class="orb orb-3"></div>
    </div>
    <div class="noise-overlay"></div>

    <div class="auth-container">
        <div class="glass-card auth-card fadeInUp">
            <div class="auth-logo">
                <h1>PROJECT M</h1>
                <p>Welcome back! Please login to continue.</p>
            </div>
            <form id="loginForm" onsubmit="handleLogin(event)">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" id="email" required placeholder="Enter your email">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="password" required placeholder="Enter your password">
                </div>
                <button type="submit" class="btn btn-primary auth-submit">Login</button>
            </form>
            <div class="auth-links">
                <a href="register.php">New here? Create an account</a>
                <a href="admin_login.php">Admin Login (Advanced)</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/csrf.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        async function handleLogin(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Logging in...';
            btn.disabled = true;

            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            try {
                const res = await fetch('login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({email, password})
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
                btn.innerHTML = 'Login';
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
