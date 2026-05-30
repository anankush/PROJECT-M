<?php
// ============================================================
// PROJECT M: Login Page
// ============================================================

require_once __DIR__ . '/includes/security.php';
session_start_secure();
set_security_headers();
validate_domain_access();

// Already logged in — redirect to dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . ROOT_BASE_URL . 'dashboard.php');
    exit;
}

$csrf_token = generate_csrf_token();
$error      = htmlspecialchars($_GET['error'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — PROJECT M</title>
    <meta name="description" content="Sign in to PROJECT M — your unified financial management hub.">
    <?= get_csrf_meta_tag() ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= ROOT_BASE_URL ?>assets/css/auth.css">
</head>
<body>
    <div class="aurora-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>
    <div class="noise-overlay"></div>

    <main class="auth-container">
        <div class="auth-card glass">
            <div class="auth-logo">
                <span class="logo-icon"><i class="fas fa-layer-group"></i></span>
                <h1 class="logo-text">PROJECT <span class="logo-m">M</span></h1>
                <p class="logo-sub">Unified Financial Hub</p>
            </div>

            <?php if ($error === 'session_expired'): ?>
            <div class="alert alert-warning" role="alert">
                <i class="fas fa-clock"></i> Your session expired. Please log in again.
            </div>
            <?php endif; ?>

            <div id="authMessage" class="alert" style="display:none;" role="alert"></div>

            <!-- Tab Switcher -->
            <div class="auth-tabs" role="tablist">
                <button class="tab-btn active" id="loginTab" onclick="switchTab('login')" role="tab" aria-selected="true">Login</button>
                <button class="tab-btn" id="registerTab" onclick="switchTab('register')" role="tab" aria-selected="false">Register</button>
            </div>

            <!-- Login Form -->
            <form id="loginForm" class="auth-form" novalidate>
                <div class="form-group">
                    <label for="loginEmail"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="loginEmail" name="email" class="form-input" placeholder="you@example.com" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label for="loginPassword"><i class="fas fa-lock"></i> Password</label>
                    <div class="input-wrap">
                        <input type="password" id="loginPassword" name="password" class="form-input" placeholder="••••••••" required autocomplete="current-password">
                        <button type="button" class="toggle-pass" onclick="togglePass('loginPassword')" aria-label="Toggle password visibility">
                            <i class="fas fa-eye" id="loginPassIcon"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-auth" id="loginBtn">
                    <span class="btn-text">Sign In</span>
                    <span class="btn-spinner" style="display:none;"><i class="fas fa-spinner fa-spin"></i></span>
                </button>
            </form>

            <!-- Register Form -->
            <form id="registerForm" class="auth-form" style="display:none;" novalidate>
                <div class="form-group">
                    <label for="regEmail"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="regEmail" name="email" class="form-input" placeholder="you@example.com" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label for="regPassword"><i class="fas fa-lock"></i> Password</label>
                    <div class="input-wrap">
                        <input type="password" id="regPassword" name="password" class="form-input" placeholder="Min. 8 characters" required autocomplete="new-password">
                        <button type="button" class="toggle-pass" onclick="togglePass('regPassword')" aria-label="Toggle password visibility">
                            <i class="fas fa-eye" id="regPassIcon"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="regConfirm"><i class="fas fa-shield-halved"></i> Confirm Password</label>
                    <div class="input-wrap">
                        <input type="password" id="regConfirm" name="confirm_password" class="form-input" placeholder="Re-enter password" required autocomplete="new-password">
                    </div>
                </div>
                <button type="submit" class="btn-auth" id="registerBtn">
                    <span class="btn-text">Create Account</span>
                    <span class="btn-spinner" style="display:none;"><i class="fas fa-spinner fa-spin"></i></span>
                </button>
            </form>
        </div>
    </main>

    <script src="<?= ROOT_BASE_URL ?>assets/js/auth.js"></script>
</body>
</html>
