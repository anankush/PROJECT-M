<?php
$pageTitle = 'Admin Register | Expense Management';
require_once '../includes/header.php';
$base = BASE_URL;
validate_url_access();

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
                <h1>Admin Registration</h1>
                <p>Create an admin account</p>
            </div>

            <div id="registerError" class="auth-error"></div>

            <form id="adminRegisterForm" class="auth-form">
                <div class="form-group">
                    <label for="email">Admin Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter admin email" required autocomplete="email">
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

                <div class="form-group">
                    <label for="admin_key">Admin Key</label>
                    <input type="password" id="admin_key" name="admin_key" placeholder="Enter the admin registration key" required>
                </div>

                <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); color: #fbbf24; padding: 0.85rem 1rem; border-radius: var(--radius-md); font-size: 0.85rem; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-info-circle"></i>
                    <span>You must know the admin key to proceed</span>
                </div>

                <button type="submit" class="btn btn-primary auth-submit" style="background: linear-gradient(135deg, #67e8f9, #34d399); box-shadow: 0 4px 20px rgba(103, 232, 249, 0.3);">
                    <i class="fas fa-user-shield"></i> Create Admin Account
                </button>
            </form>

            <div class="auth-links">
                <a href="login.php">Already have an admin account? Login</a>
                <a href="<?php echo $base; ?>index.php"><i class="fas fa-home"></i> Back to Home</a>
            </div>
        </div>
    </div>

    <script>const API_URL = '<?php echo $base; ?>includes/api.php';</script>
    <script src="<?php echo $base; ?>assets/js/input_validation.js"></script>
    <script src="<?php echo $base; ?>assets/js/admin_register.js"></script>
</body>
</html>
