<?php
// ProjectM/auth/otp_verify.php
require_once '../includes/db.php';
require_once '../includes/csrf.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
set_security_headers();

if (isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])) {
    header('Location: ../dashboard/index.php');
    exit;
}

if (!isset($_SESSION['pending_reg'])) {
    header('Location: register.php');
    exit;
}

$email = $_SESSION['pending_reg']['email'];

// Calculate time left for the OTP
$stmt = $pdo->prepare("SELECT UNIX_TIMESTAMP(expires_at) - UNIX_TIMESTAMP(NOW()) as time_left FROM password_resets WHERE email = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$email]);
$otp_record = $stmt->fetch();
$time_left = $otp_record ? max(0, (int)$otp_record['time_left']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    // Rate limit: max 5 OTP guesses per IP per 5 minutes (brute-force protection)
    check_rate_limit($pdo, 'otp_verify', 5, 5);
    $input = json_decode(file_get_contents('php://input'), true);
    $otp = trim($input['otp'] ?? '');

    if (empty($otp) || !preg_match('/^\d{6}$/', $otp)) {
        echo json_encode(['status' => 'error', 'message' => 'Valid 6-digit OTP required']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND otp = ? AND expires_at > NOW() LIMIT 1");
        $stmt->execute([$email, $otp]);
        
        if ($stmt->fetch()) {
            // OTP is valid — clean up
            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

            // Check if email was registered by someone else during the OTP lifecycle
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $checkStmt->execute([$email]);
            if ($checkStmt->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'Email already registered']);
                exit;
            }

            // Create user
            $hashedPass = $_SESSION['pending_reg']['password'];
            $insert = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
            $insert->execute([$email, $hashedPass]);
            
            $userId = $pdo->lastInsertId();

            // Auto login with session fixation prevention
            session_regenerate_id(true);
            unset($_SESSION['admin_id']); // Clear any active admin session to prevent contamination
            unset($_SESSION['is_admin']);
            $_SESSION['user_id']       = $userId;
            $_SESSION['role']          = 'user';
            $_SESSION['user_name']     = explode('@', $email)[0];
            $_SESSION['user_email']    = $email;
            $_SESSION['currency']      = '₹';
            $_SESSION['last_activity'] = time();
            $_SESSION['logout_token']  = bin2hex(random_bytes(16));
            
            unset($_SESSION['pending_reg']);

            echo json_encode(['status' => 'success', 'redirect' => '../dashboard/index.php']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid or expired OTP']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Verification failed']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP | Money Management</title>
    <?php echo get_csrf_meta_tag(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/glassmorphism.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/auth.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="aurora-bg">
        <div class="orb orb-2"></div><div class="orb orb-4"></div>
    </div>
    <div class="noise-overlay"></div>

    <div class="auth-container">
        <div class="glass-card auth-card fadeInUp">
            <div class="auth-logo">
                <h1>Verify OTP</h1>
                <p>Enter the 6-digit code sent to <?php echo htmlspecialchars($email); ?></p>
                <p id="otp-timer" style="color: var(--danger); font-weight: 600; margin-top: 0.8rem; font-size: 0.95rem;"></p>
            </div>
            <form id="otpForm" onsubmit="handleVerify(event)">
                <div class="form-group">
                    <label>6-Digit OTP</label>
                    <input type="text" id="otp" required placeholder="123456" maxlength="6" pattern="\d{6}" inputmode="numeric" autocomplete="one-time-code">
                </div>
                <button type="submit" class="btn btn-primary auth-submit">Verify & Create Account</button>
            </form>
            <div class="auth-links">
                <a href="register.php">Wrong email? Go back</a>
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
            const btn = document.querySelector('.auth-submit');
            
            if (timeLeft <= 0) {
                timerEl.innerHTML = "OTP Expired. <a href='register.php' style='color: var(--aurora-1)'>Request again</a>";
                btn.disabled = true;
                return;
            }

            timerId = setInterval(() => {
                if (timeLeft <= 0) {
                    clearInterval(timerId);
                    timerEl.innerHTML = "OTP Expired. <a href='register.php' style='color: var(--aurora-1)'>Request again</a>";
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

        async function handleVerify(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Verifying...';
            btn.disabled = true;

            const otp = document.getElementById('otp').value;

            try {
                const res = await fetch('otp_verify.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({otp})
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast('Account created successfully!');
                    setTimeout(() => window.location.href = data.redirect, 1000);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Verification failed', 'error');
            } finally {
                btn.innerHTML = 'Verify & Create Account';
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
