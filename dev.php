<?php
// dev.php - Development & Quick Links
require_once 'includes/db.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';
set_security_headers();

$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['admin_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dev Links | PROJECT M</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/glassmorphism.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/landing.css?v=<?php echo time(); ?>">
    <style>
        .dev-links-container {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
            max-width: 320px;
            margin: 3rem auto;
        }
        .dev-links-container .btn {
            justify-content: center;
            padding: 1rem;
            font-size: 1rem;
            border-radius: 12px;
        }
    </style>
</head>
<body>
    <div class="color-combo-bg"></div>
    <div class="aurora-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-3"></div>
    </div>
    <div class="noise-overlay"></div>

    <nav class="landing-nav" id="navbar">
        <a href="index.php" class="nav-logo">PROJECT M</a>
        <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Toggle Navigation">
            <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
        <div class="nav-links">
            <a href="index.php" class="desktop-only">Home</a>
            <a href="about.php" class="desktop-only">About</a>
        </div>
    </nav>

    <div class="about-container" style="text-align: center; min-height: 80vh; display: flex; flex-direction: column; justify-content: center;">
        <div class="reveal visible">
            <h1><span class="gradient-text">Developer Links</span></h1>
            <p>Access all major sections of the platform.</p>
            
            <div class="dev-links-container">
                <a href="index.php" class="btn btn-outline">Home Page</a>
                <a href="about.php" class="btn btn-outline">About Page</a>
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard/index.php" class="btn btn-primary">Dashboard</a>
                <?php else: ?>
                    <a href="auth/login.php" class="btn btn-outline">Login</a>
                    <a href="auth/register.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
                <a href="https://github.com/anankush/PROJECT-M" target="_blank" rel="noopener" class="btn btn-outline">
                    <i class="fab fa-github"></i> GitHub Repository
                </a>
            </div>
        </div>
    </div>

    <!-- ═══ FOOTER ═══ -->
    <footer class="landing-footer" style="position: absolute; bottom: 0; width: 100%; border-top: none; background: transparent;">
        <div class="footer-inner" style="justify-content: center; text-align: center;">
            <div class="footer-copy">
                &copy; <?php echo date('Y'); ?> <a href="dev.php" style="color: inherit; text-decoration: none;">PROJECT M</a>, made with love ❤️
            </div>
        </div>
    </footer>

    <script>
        // Mobile Menu Toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navLinks = document.querySelector('.nav-links');
        if (mobileMenuBtn && navLinks) {
            mobileMenuBtn.addEventListener('click', () => {
                navLinks.classList.toggle('active');
            });
        }
    </script>
</body>
</html>
