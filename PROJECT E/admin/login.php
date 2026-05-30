<?php
$pageTitle = 'Admin Login | Expense Management';
require_once '../includes/header.php';
$base = BASE_URL;
validate_url_access(['error']);

if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
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
                <div class="logo-icon" style="background: linear-gradient(135deg, #67e8f9, #34d399);"><i class="fas fa-user-shield"></i></div>
                <h1>Admin Login</h1>
                <p>Access the admin panel</p>
            </div>

            <div id="loginError" class="auth-error"></div>

            <form id="adminLoginForm" class="auth-form">
                <div class="form-group">
                    <label for="email">Admin Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter admin email" required autocomplete="email">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter password" required autocomplete="current-password">
                        <button type="button" class="password-toggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="admin_key">Admin Key</label>
                    <input type="password" id="admin_key" name="admin_key" placeholder="Enter admin key" required>
                </div>

                <button type="submit" class="btn btn-primary auth-submit" style="background: linear-gradient(135deg, #67e8f9, #34d399); box-shadow: 0 4px 20px rgba(103, 232, 249, 0.3);">
                    <i class="fas fa-shield-alt"></i> Admin Login
                </button>
            </form>

            <div class="auth-links">
                <a href="javascript:void(0)" onclick="forgotPassword('<?php echo $base; ?>includes/api.php', '<?php echo generate_csrf_token(); ?>', 'admin')" style="color: var(--danger);"><i class="fas fa-key"></i> Forgot Password?</a>
                <a href="register.php">New admin? Register here</a>
                <a href="<?php echo $base; ?>index.php"><i class="fas fa-home"></i> Back to Home</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>const API_URL = '<?php echo $base; ?>includes/api.php';</script>
    <script src="<?php echo $base; ?>assets/js/input_validation.js"></script>
    <script src="<?php echo $base; ?>assets/js/forgot_password.js"></script>
    <script src="<?php echo $base; ?>assets/js/admin_login.js"></script>
</body>
</html>
