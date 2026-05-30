<?php
$pageTitle = 'Member Login | Expense Management';
require_once '../includes/header.php';
$base = BASE_URL;
validate_url_access(['error']);

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>

<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/global.css">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/auth.css">
</head>

<body>
    <div class="aurora-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>
    <div class="noise-overlay"></div>

    <div class="auth-container">
        <div class="auth-card glass-card">
            <div class="auth-logo">
                <div class="logo-icon"><i class="fas fa-chart-pie"></i></div>
                <h1>Member Login</h1>
                <p>Access your expense dashboard</p>
            </div>

            <div id="loginError" class="auth-error"></div>
            <div id="loginSuccess" class="auth-success"></div>

            <form id="loginForm" class="auth-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required autocomplete="email">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                        <button type="button" class="password-toggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary auth-submit">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="auth-links">
                <a href="javascript:void(0)" onclick="forgotPassword('<?php echo $base; ?>includes/api.php', '<?php echo generate_csrf_token(); ?>', 'user')" style="color: var(--danger);"><i class="fas fa-key"></i> Forgot Password?</a>
                <a href="register.php">New here? Create an account</a>
                <a href="<?php echo $base; ?>index.php"><i class="fas fa-home"></i> Back to Home</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>const API_URL = '<?php echo $base; ?>includes/api.php';</script>
    <script src="<?php echo $base; ?>assets/js/input_validation.js"></script>
    <script src="<?php echo $base; ?>assets/js/forgot_password.js"></script>
    <script src="<?php echo $base; ?>assets/js/user_login.js"></script>
</body>
</html>
