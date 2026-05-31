<?php
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
    check_rate_limit($pdo, 'login', 10, 15);
    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Email and password required']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, email, password, status, currency FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            if ($password === $user['password']) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newHash, $user['id']]);
                $isValid = true;
            } else {
                $isValid = password_verify($password, $user['password']);
            }

            if ($isValid) {
                if (isset($user['status']) && $user['status'] === 'blocked') {
                    log_security_event($pdo, $email, 'login_failed', $user['id']);
                    echo json_encode(['status' => 'error', 'message' => 'Your account has been blocked by the administrator.']);
                    exit;
                }

                session_regenerate_id(true);
                $pdo->prepare("UPDATE users SET active_session_id = ? WHERE id = ?")->execute([session_id(), $user['id']]);
                unset($_SESSION['admin_id']);
                unset($_SESSION['is_admin']);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = 'user';
                $_SESSION['user_name'] = explode('@', $user['email'])[0];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['currency'] = $user['currency'] ?? '₹';
                $_SESSION['last_activity'] = time();
                $_SESSION['logout_token'] = bin2hex(random_bytes(16));
                log_security_event($pdo, $email, 'login_success', $user['id']);
                echo json_encode(['status' => 'success', 'redirect' => '../dashboard/index.php']);
                exit;
            }
        }

        log_security_event($pdo, $email, 'login_failed');
        password_verify('dummy', '$2y$10$dummyhashtopreventtimingattacks.......');
        echo json_encode(['status' => 'error', 'message' => 'Invalid credentials']);
    } catch (Exception $e) {
        log_security_event($pdo, $email, 'login_failed');
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
    <title>Login | Money Management</title>
    <?php echo get_csrf_meta_tag(); ?>
    <link rel="stylesheet" href="../assets/css/glassmorphism.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/auth.css?v=<?php echo time(); ?>">
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
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>
    <div class="noise-overlay"></div>

    <div class="auth-container">
        <div class="glass-card auth-card fadeInUp">
            <div class="auth-avatar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
            <div class="auth-logo">
                <h1>Welcome Back</h1>
                <p>Please login to continue.</p>
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
                <a href="forgot_password.php">Forgot Password?</a>
                <a href="register.php">New here? Create an account</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/csrf.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/main.js?v=<?php echo time(); ?>"></script>
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
                    body: JSON.stringify({ email, password })
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