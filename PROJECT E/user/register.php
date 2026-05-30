<?php
$pageTitle = 'Register | Expense Management';
require_once '../includes/header.php';
$base = BASE_URL;
validate_url_access();

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
                <div class="logo-icon"><i class="fas fa-user-plus"></i></div>
                <h1>Create Account</h1>
                <p>Start tracking your expenses</p>
            </div>

            <div id="registerError" class="auth-error"></div>

            <form id="registerForm" class="auth-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required autocomplete="email">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Create a strong password" required autocomplete="new-password">
                        <button type="button" class="password-toggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar"></div>
                    </div>
                    <span class="password-hint">Min 8 chars, uppercase, lowercase, number & special char</span>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required autocomplete="new-password">
                        <button type="button" class="password-toggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary auth-submit">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>

            <div class="auth-links">
                <a href="login.php">Already have an account? Login</a>
                <a href="<?php echo $base; ?>index.php"><i class="fas fa-home"></i> Back to Home</a>
            </div>
        </div>
    </div>

    <script>const API_URL = '<?php echo $base; ?>includes/api.php';</script>
    <script src="<?php echo $base; ?>assets/js/input_validation.js"></script>
    <script src="<?php echo $base; ?>assets/js/user_register.js"></script>
</body>
</html>
