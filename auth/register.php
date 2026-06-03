<?php
require_once '../includes/db.php';
require_once '../includes/csrf.php';
require_once '../includes/auth_check.php';
require_once '../includes/mailer.php';
require_once '../includes/functions.php';
set_security_headers();

if (isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])) {
    header('Location: ../dashboard/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    check_rate_limit($pdo, 'register', 5, 15);
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

    if (is_disposable_email($email)) {
        log_security_event($pdo, $email, 'bypass_tempmail_attempt');
        echo json_encode(['status' => 'error', 'message' => 'Temporary / Disposable email addresses are not allowed. Please use a real email provider.']);
        exit;
    }

    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password)) {
        echo json_encode(['status' => 'error', 'message' => 'Password must contain at least 8 characters, 1 uppercase, 1 lowercase, 1 number, and 1 special character']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Email already registered']);
            exit;
        }

        $otp = sprintf("%06d", random_int(100000, 999999));
        $pdo->exec("DELETE FROM password_resets WHERE expires_at <= NOW()");
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
        $stmt = $pdo->prepare("INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 2 MINUTE))");
        $stmt->execute([$email, $otp]);

        $_SESSION['pending_reg'] = [
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ];

        $body = get_otp_email_body($otp, false);
        send_email($email, "Registration OTP", $body);

        echo json_encode(['status' => 'success', 'redirect' => 'otp_verify.php']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Registration failed. Try again.']);
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
    <title>Register | Money Management</title>
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
        <div class="orb orb-2"></div>
    </div>
    <div class="noise-overlay"></div>

    <div class="auth-container">
        <div class="glass-card auth-card fadeInUp">
            <div class="auth-avatar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
            </div>
            <div class="auth-logo">
                <h1>Create Account</h1>
                <p>Join Money Management.</p>
            </div>
            <form id="regForm" onsubmit="handleRegister(event)">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" id="email" required placeholder="Enter your email">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="password" required placeholder="Choose a strong password">
                </div>
                <button type="submit" class="btn btn-primary auth-submit">Send OTP</button>
            </form>
            <div class="auth-links">
                <a href="login.php">Already have an account? Login</a>
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
            btn.innerHTML = 'Sending OTP...';
            btn.disabled = true;

            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            try {
                btn.innerHTML = 'Verifying Email...';
                const isTemp = await isDisposableEmail(email);
                if (isTemp) {
                    showToast('Temporary / Disposable email addresses are not allowed.', 'error');
                    btn.innerHTML = 'Send OTP';
                    btn.disabled = false;
                    return;
                }
            } catch (err) {
                console.error("Email verification skipped due to error:", err);
            }

            btn.innerHTML = 'Sending OTP...';

            try {
                const res = await fetch('register.php', {
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
                showToast('Registration failed', 'error');
            } finally {
                btn.innerHTML = 'Send OTP';
                btn.disabled = false;
            }
        }
    </script>
</body>

</html>