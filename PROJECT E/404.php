<?php
$pageTitle = 'Page Not Found';
require_once 'includes/header_root.php';
$base = BASE_URL;
http_response_code(404);
?>

<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/global.css">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/auth.css">
</head>

<style>
    @keyframes floatIcon {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-12px); }
    }
    @keyframes glitch404 {
        0%, 100% { text-shadow: 0 0 40px rgba(139,92,246,0.4), 2px 2px 0 rgba(6,182,212,0.3); }
        25% { text-shadow: -2px 0 rgba(236,72,153,0.5), 2px 2px 0 rgba(6,182,212,0.3); }
        50% { text-shadow: 2px 0 rgba(6,182,212,0.5), -2px -2px 0 rgba(139,92,246,0.3); }
        75% { text-shadow: -1px 1px 0 rgba(236,72,153,0.4), 1px -1px 0 rgba(6,182,212,0.4); }
    }
    @keyframes fadeSlideUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes pulseBtn {
        0%, 100% { box-shadow: 0 6px 30px rgba(139,92,246,0.4); }
        50% { box-shadow: 0 6px 40px rgba(139,92,246,0.7), 0 0 60px rgba(139,92,246,0.2); }
    }
    .error-icon {
        animation: floatIcon 3s ease-in-out infinite;
    }
    .error-code {
        animation: glitch404 4s ease-in-out infinite;
    }
    .auth-card {
        animation: fadeSlideUp 0.8s ease both;
    }
    .btn-primary {
        animation: pulseBtn 2.5s ease-in-out infinite;
    }
</style>

<body>
    <div class="aurora-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
    </div>
    <div class="noise-overlay"></div>

    <div class="auth-container">
        <div class="auth-card glass-card" style="text-align: center;">
            <div class="error-icon" style="font-size: 4rem; color: var(--aurora-1); margin-bottom: 1rem;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1 class="error-code" style="font-family: var(--font-display); font-size: 3rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--text-primary);">404</h1>
            <p style="color: var(--text-secondary); margin-bottom: 2rem; font-size: 1rem; animation: fadeSlideUp 0.8s ease 0.3s both;">Page not found</p>
            <a href="<?php echo $base; ?>index.php" class="btn btn-primary" style="animation: pulseBtn 2.5s ease-in-out infinite, fadeSlideUp 0.8s ease 0.5s both;">
                <i class="fas fa-home"></i> Go Home
            </a>
        </div>
    </div>
</body>
</html>
