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
    check_rate_limit($pdo, 'forgot_password', 5, 15);
    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email'] ?? '');

    if (empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Email is required']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $otp = sprintf("%06d", random_int(100000, 999999));
            $pdo->exec("DELETE FROM password_resets WHERE expires_at <= NOW()");

            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

            $stmt = $pdo->prepare("INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 2 MINUTE))");
            $stmt->execute([$email, $otp]);

            $_SESSION['reset_email'] = $email;
            log_security_event($pdo, $email, 'password_reset_request');

            $body = "Your Password Reset OTP for Money Management is: $otp\n\nIt will expire in 2 minutes.";
            send_email($email, "Password Reset OTP", $body);

            echo json_encode(['status' => 'success', 'redirect' => 'reset_password.php']);
        } else {
            echo json_encode([
                'status'  => 'success',
                'message' => 'If this email is registered, an OTP has been sent to it.'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to process request. Please try again.']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | PROJECT M</title>
    <?php echo get_csrf_meta_tag(); ?>
    <link rel="stylesheet" href="../assets/css/glassmorphism.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/auth.css?v=<?php echo time(); ?>">
</head>
<body>
    <a href="login.php" class="back-home-btn">
        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Back to Login
    </a>
    <div class="aurora-bg">
        <div class="orb orb-1"></div><div class="orb orb-2"></div>
    </div>
    <div class="noise-overlay"></div>

    <div class="auth-container">
        <div class="glass-card auth-card fadeInUp">
            <div class="auth-avatar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" style="display:none"></path>
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <div class="auth-logo">
                <h1>Reset Password</h1>
                <p>We will send an OTP to your email.</p>
            </div>
            <form id="forgotForm" onsubmit="handleForgot(event)">
                <div class="form-group">
                    <label>Registered Email</label>
                    <input type="email" id="email" required placeholder="Enter your email">
                </div>
                <button type="submit" class="btn btn-primary auth-submit">Send OTP</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/csrf.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        async function handleForgot(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            btn.innerHTML = 'Sending...';
            btn.disabled = true;

            const email = document.getElementById('email').value;

            try {
                const res = await fetch('forgot_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({email})
                });
                const data = await res.json();
                if (data.status === 'success') {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        showToast(data.message || 'If this email is registered, an OTP has been sent.', 'success');
                    }
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Request failed', 'error');
            } finally {
                btn.innerHTML = 'Send OTP';
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
