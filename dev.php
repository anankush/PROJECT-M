<?php

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
    <title>Developer | Money Management</title>
    <meta name="description" content="Connect with the developer of Money Management — a personal finance management platform.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/glassmorphism.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/landing.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="color-combo-bg"></div>
    <div class="aurora-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>
    <div class="noise-overlay"></div>

    <!-- ═══ NAVBAR ═══ -->
    <nav class="landing-nav" id="navbar">
        <a href="index.php" class="nav-logo">Money Management</a>
        <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Toggle Navigation">
            <div class="hamburger-icon">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </button>
        <div class="nav-links">
            <a href="index.php" class="desktop-only">Home</a>
            <a href="about.php" class="desktop-only">About</a>
            <?php if ($isLoggedIn): ?>
                <a href="dashboard/index.php" class="btn btn-primary">Dashboard</a>
            <?php else: ?>
                <a href="auth/login.php" class="desktop-only">Login</a>
                <a href="auth/register.php" class="btn btn-primary">Get Started</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- ═══ DEV CONTENT ═══ -->
    <div class="dev-wrapper">
        <div class="dev-content">

            <div class="reveal visible dev-header">
                <div class="hero-badge" style="margin: 0 auto 1.5rem;">
                    <i class="fas fa-code"></i>
                    Developer Profile
                </div>
                <h1><span class="gradient-text">Say Hello 👋</span></h1>
                <p>Connect with the developer of Money Management. Open to collaborations, feedback, and friendly conversations.</p>
            </div>

            <div class="dev-grid reveal visible">
                <a href="https://www.linkedin.com/in/itznayan" target="_blank" rel="noopener" class="glass-card dev-card card-home">
                    <i class="fab fa-linkedin dev-card-icon"></i>
                    <div class="dev-card-title">LinkedIn</div>
                    <div class="dev-card-desc">Connect professionally — open to networking and collaboration.</div>
                </a>

                <a href="https://github.com/anankush/" target="_blank" rel="noopener" class="glass-card dev-card card-git">
                    <i class="fab fa-github dev-card-icon"></i>
                    <div class="dev-card-title">GitHub</div>
                    <div class="dev-card-desc">Explore open-source projects and contributions.</div>
                </a>
            </div>

        </div>

        <footer class="landing-footer">
            <div class="footer-inner" style="justify-content: center; text-align: center;">
                <div class="footer-copy">
                    made with love ❤️<br>
                    &copy; <?php echo date('Y'); ?> <a href="admin_portal.php" style="color: inherit; text-decoration: none;">PROJECT M</a>
                </div>
            </div>
        </footer>
    </div>

    <script>
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });

        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navLinks = document.querySelector('.nav-links');
        if (mobileMenuBtn && navLinks) {
            mobileMenuBtn.addEventListener('click', () => {
                const isOpen = navLinks.classList.toggle('active');
                mobileMenuBtn.classList.toggle('open', isOpen);
            });
            navLinks.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    navLinks.classList.remove('active');
                    mobileMenuBtn.classList.remove('open');
                });
            });
        }

        const glow = document.createElement('div');
        glow.className = 'cursor-glow';
        document.body.appendChild(glow);
        document.addEventListener('mousemove', e => {
            requestAnimationFrame(() => {
                glow.style.transform = `translate(${e.clientX - 250}px, ${e.clientY - 250}px)`;
                glow.style.opacity = '1';
            });
        });
        document.addEventListener('mouseleave', () => { glow.style.opacity = '0'; });
    </script>
</body>
</html>
