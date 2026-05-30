<?php
// ProjectM/auth/register.php
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
    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Email and password required']);
        exit;
    }

    if (strlen($password) < 8) {
        echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Email already registered']);
            exit;
        }

        // Generate OTP
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        // Delete any existing OTP for this email
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

        // Insert new OTP
        $stmt = $pdo->prepare("INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $otp, $expires_at]);

        // Store pending registration in session
        $_SESSION['pending_reg'] = [
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ];

        // Send Email
        $body = "Your Registration OTP for Money Management is: $otp\n\nIt will expire in 5 minutes.";
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
    <title>Register | Money Management</title>
    <?php echo get_csrf_meta_tag(); ?>
    <link rel="stylesheet" href="../assets/css/glassmorphism.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <div class="aurora-bg">
        <div class="orb orb-1"></div><div class="orb orb-2"></div>
    </div>
    <div class="noise-overlay"></div>

    <div class="auth-container">
        <div class="glass-card auth-card fadeInUp">
            <div class="auth-logo">
                <h1>Money Management</h1>
                <p>Create your new account.</p>
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
    <script src="../assets/js/csrf.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        async function handleRegister(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            btn.innerHTML = 'Sending OTP...';
            btn.disabled = true;

            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            try {
                const res = await fetch('register.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({email, password})
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
