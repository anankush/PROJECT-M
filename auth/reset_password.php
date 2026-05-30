<?php
// ProjectM/auth/reset_password.php
require_once '../includes/db.php';
require_once '../includes/csrf.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
set_security_headers();

if (isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])) {
    header('Location: ../dashboard/index.php');
    exit;
}

if (!isset($_SESSION['reset_email'])) {
    header('Location: forgot_password.php');
    exit;
}

$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $input = json_decode(file_get_contents('php://input'), true);
    $otp = trim($input['otp'] ?? '');
    $new_password = $input['new_password'] ?? '';

    if (empty($otp) || empty($new_password)) {
        echo json_encode(['status' => 'error', 'message' => 'OTP and New Password required']);
        exit;
    }

    if (!preg_match('/^\d{6}$/', $otp)) {
        echo json_encode(['status' => 'error', 'message' => 'Valid 6-digit OTP required']);
        exit;
    }

    if (strlen($new_password) < 8) {
        echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND otp = ? AND expires_at > NOW() LIMIT 1");
        $stmt->execute([$email, $otp]);
        
        if ($stmt->fetch()) {
            // OTP is valid — update password
            $hashedPass = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $update->execute([$hashedPass, $email]);
            
            // Clean up OTP
            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

            // Clear reset session
            unset($_SESSION['reset_email']);

            echo json_encode(['status' => 'success', 'redirect' => 'login.php']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid or expired OTP']);
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
    <title>Set New Password | PROJECT M</title>
    <?php echo get_csrf_meta_tag(); ?>
    <link rel="stylesheet" href="../assets/css/glassmorphism.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <a href="forgot_password.php" class="back-home-btn">
        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Back
    </a>
    <div class="aurora-bg">
        <div class="orb orb-1"></div><div class="orb orb-2"></div>
    </div>
    <div class="noise-overlay"></div>

    <div class="auth-container">
        <div class="glass-card auth-card fadeInUp">
            <div class="auth-avatar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <div class="auth-logo">
                <h1>New Password</h1>
                <p>Enter the OTP sent to <?php echo htmlspecialchars($email); ?></p>
            </div>
            <form id="resetForm" onsubmit="handleReset(event)">
                <div class="form-group">
                    <label>6-Digit OTP</label>
                    <input type="text" id="otp" required placeholder="000000" maxlength="6" pattern="\d{6}">
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" id="new_password" required placeholder="Enter new strong password">
                </div>
                <button type="submit" class="btn btn-primary auth-submit">Reset Password</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/csrf.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        async function handleReset(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            btn.innerHTML = 'Verifying...';
            btn.disabled = true;

            const otp = document.getElementById('otp').value;
            const new_password = document.getElementById('new_password').value;

            try {
                const res = await fetch('reset_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({otp, new_password})
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast('Password reset successfully! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 2000);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Reset failed', 'error');
            } finally {
                btn.innerHTML = 'Reset Password';
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
