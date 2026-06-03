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

if (!isset($_SESSION['reset_email'])) {
    header('Location: forgot_password.php');
    exit;
}

$email = $_SESSION['reset_email'];

$stmt = $pdo->prepare("SELECT UNIX_TIMESTAMP(expires_at) - UNIX_TIMESTAMP(NOW()) as time_left FROM password_resets WHERE email = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$email]);
$otp_record = $stmt->fetch();
$time_left = $otp_record ? max(0, (int) $otp_record['time_left']) : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    check_rate_limit($pdo, 'reset_password', 5, 5);
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    try {
        if ($action === 'verify_otp') {
            $otp = trim($input['otp'] ?? '');
            if (!preg_match('/^\d{6}$/', $otp)) {
                echo json_encode(['status' => 'error', 'message' => 'Valid 6-digit OTP required']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND otp = ? AND expires_at > NOW() LIMIT 1");
            $stmt->execute([$email, $otp]);

            if ($stmt->fetch()) {
                $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
                $_SESSION['otp_verified'] = true;
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid or expired OTP']);
            }
            exit;
        }

        if ($action === 'set_password') {
            if (empty($_SESSION['otp_verified'])) {
                echo json_encode(['status' => 'error', 'message' => 'Please verify OTP first']);
                exit;
            }

            $new_password = $input['new_password'] ?? '';

            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $new_password)) {
                echo json_encode(['status' => 'error', 'message' => 'Password must contain at least 8 characters, 1 uppercase, 1 lowercase, 1 number, and 1 special character']);
                exit;
            }

            $hashedPass = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $update->execute([$hashedPass, $email]);

            unset($_SESSION['reset_email']);
            unset($_SESSION['otp_verified']);
            log_security_event($pdo, $email, 'password_reset_success');

            echo json_encode(['status' => 'success', 'redirect' => 'login.php']);
            exit;
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
    <meta name="path-depth" content="1">
    <title>Set New Password | Money Management</title>
    <?php echo get_csrf_meta_tag(); ?>
    <link rel="stylesheet" href="../assets/css/glassmorphism.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/auth.css?v=<?php echo time(); ?>">
</head>

<body>
    <a href="forgot_password.php" class="back-home-btn">
        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"
            stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Back
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
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>

            <!-- Step 1: Verify OTP -->
            <div id="step-otp">
                <div class="auth-logo">
                    <h1>Verify OTP</h1>
                    <p>Enter the 6-digit OTP sent to your email.</p>
                    <p id="otp-timer"
                        style="color: var(--danger); font-weight: 600; margin-top: 0.8rem; font-size: 0.95rem;"></p>
                </div>
                <form onsubmit="handleVerifyOTP(event)">
                    <div class="form-group">
                        <label>6-Digit OTP</label>
                        <input type="text" id="otp" required placeholder="000000" maxlength="6" pattern="\d{6}">
                    </div>
                    <button type="submit" class="btn btn-primary auth-submit" id="btn-verify">Verify OTP</button>
                </form>
            </div>

            <!-- Step 2: Set New Password -->
            <div id="step-password" style="display: none;">
                <div class="auth-logo">
                    <h1>New Password</h1>
                    <p>Create a secure password.</p>
                </div>
                <form onsubmit="handleSetPassword(event)">
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" id="new_password" required placeholder="Enter new password">
                        <small
                            style="color: var(--text-muted); font-size: 0.8rem; display: block; margin-top: 0.5rem; line-height: 1.4;">
                            Must include: 1 Uppercase, 1 Lowercase, 1 Number, 1 Special Character (@$!%*?&).
                        </small>
                    </div>
                    <button type="submit" class="btn btn-primary auth-submit" id="btn-reset">Update Password</button>
                </form>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/csrf.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        let timeLeft = <?php echo $time_left; ?>;
        let timerId;

        function startTimer() {
            const timerEl = document.getElementById('otp-timer');
            const btn = document.getElementById('btn-verify');

            if (timeLeft <= 0) {
                timerEl.innerHTML = "OTP Expired. <a href='forgot_password.php' style='color: var(--aurora-1)'>Request again</a>";
                btn.disabled = true;
                return;
            }

            timerId = setInterval(() => {
                if (timeLeft <= 0) {
                    clearInterval(timerId);
                    timerEl.innerHTML = "OTP Expired. <a href='forgot_password.php' style='color: var(--aurora-1)'>Request again</a>";
                    btn.disabled = true;
                } else {
                    timeLeft--;
                    const m = Math.floor(timeLeft / 60);
                    const s = timeLeft % 60;
                    timerEl.innerHTML = `Expires in 0${m}:${s < 10 ? '0' : ''}${s}`;
                }
            }, 1000);
        }

        window.onload = startTimer;
        async function handleVerifyOTP(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-verify');
            btn.innerHTML = 'Verifying...';
            btn.disabled = true;

            const otp = document.getElementById('otp').value;

            try {
                const res = await fetch('reset_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ action: 'verify_otp', otp })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    document.getElementById('step-otp').style.display = 'none';
                    document.getElementById('step-password').style.display = 'block';
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Verification failed', 'error');
            } finally {
                btn.innerHTML = 'Verify OTP';
                btn.disabled = false;
            }
        }

        async function handleSetPassword(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-reset');
            btn.innerHTML = 'Updating...';
            btn.disabled = true;

            const new_password = document.getElementById('new_password').value;

            try {
                const res = await fetch('reset_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ action: 'set_password', new_password })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast('Password updated successfully! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 2000);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Reset failed', 'error');
            } finally {
                btn.innerHTML = 'Update Password';
                btn.disabled = false;
            }
        }
    </script>
</body>

</html>