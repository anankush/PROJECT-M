<?php
// error.php
// Set default timezone to Indian Standard Time (IST)
date_default_timezone_set('Asia/Kolkata');

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    // PHP 7.2 compatible cookie params
    session_set_cookie_params(
        900,                    // lifetime
        '/',                    // path
        '',                     // domain
        false,                  // secure
        true                    // httponly
    );
    session_name('PROJECTM_SID');
    session_start();
}

// Clear all session data (Session Close)
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}
session_destroy();

$error_msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : 'An unexpected system error occurred.';
$error_type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'System Error';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $error_type; ?> | PROJECT M</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/glassmorphism.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <style>
        .error-card {
            border-color: rgba(239, 68, 68, 0.3) !important;
            box-shadow: 0 8px 32px 0 rgba(239, 68, 68, 0.15) !important;
        }
        .error-icon {
            background: linear-gradient(135deg, #ef4444, #991b1b) !important;
            box-shadow: 0 10px 25px rgba(239, 68, 68, 0.4) !important;
        }
        .gradient-text-error {
            background: linear-gradient(to right, #ef4444, #f87171);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .btn-error {
            background: linear-gradient(135deg, #ef4444, #991b1b) !important;
            box-shadow: 0 4px 20px rgba(239, 68, 68, 0.4) !important;
            border: 1px solid rgba(239, 68, 68, 0.3) !important;
            color: white !important;
        }
        .btn-error:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(239, 68, 68, 0.5) !important;
        }
    </style>
</head>
<body>
    <!-- Aurora Background -->
    <div class="aurora-bg">
        <div class="orb orb-1" style="background: radial-gradient(circle, rgba(239,68,68,0.4) 0%, rgba(239,68,68,0) 70%);"></div>
        <div class="orb orb-3" style="background: radial-gradient(circle, rgba(153,27,27,0.4) 0%, rgba(153,27,27,0) 70%);"></div>
    </div>
    <div class="noise-overlay"></div>

    <div class="auth-container">
        <div class="glass-card auth-card error-card fadeInUp">
            <div class="auth-avatar error-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="32" height="32" style="color: white; margin-top: 10px;">
                    <polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
            </div>
            <div class="auth-logo">
                <h1 class="gradient-text-error"><?php echo $error_type; ?></h1>
                <p style="margin-top: 15px; font-size: 1.05rem; line-height: 1.5; color: var(--text-primary);"><?php echo $error_msg; ?></p>
                <p style="margin-top: 10px; font-size: 0.85rem; color: var(--text-muted);">The session has been terminated for your safety.</p>
            </div>
            <div style="margin-top: 25px; text-align: center;">
                <a href="index.php" class="btn btn-error" style="text-decoration: none; padding: 12px 28px; border-radius: var(--radius-l); display: inline-flex; align-items: center; gap: 8px; font-weight: 600;">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>
