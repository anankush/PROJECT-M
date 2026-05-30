<?php
// Money Management — About Page
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
    <title>About | Money Management</title>
    <meta name="description" content="Learn about Money Management — a free, open-source personal finance management platform built with PHP, MySQL, and a stunning Glassmorphism UI.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/glassmorphism.css">
    <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body>
    <!-- Aurora Background -->
    <div class="aurora-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-3"></div>
    </div>
    <div class="noise-overlay"></div>

    <!-- ═══ NAVBAR ═══ -->
    <nav class="landing-nav" id="navbar">
        <a href="index.php" class="nav-logo">Money Management</a>
        <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Toggle Navigation">
            <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
        <div class="nav-links">
            <a href="index.php" class="desktop-only">Home</a>
            <a href="index.php#features" class="desktop-only">Features</a>
            <?php if ($isLoggedIn): ?>
                <a href="dashboard/index.php" class="btn btn-primary">Dashboard</a>
            <?php else: ?>
                <a href="auth/login.php">Login</a>
                <a href="auth/register.php" class="btn btn-primary">Get Started</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- ═══ ABOUT CONTENT ═══ -->
    <div class="about-container">
        <div class="reveal">
            <h1><span class="gradient-text">About Money Management</span></h1>
            <p>
                Money Management is a free, open-source personal finance management platform designed 
                to give you complete control over your money. It combines expense tracking 
                and savings goal management under one unified dashboard — all wrapped in 
                a modern, premium interface.
            </p>
        </div>

        <div class="reveal">
            <h2><i class="fas fa-lightbulb"></i> Why Money Management?</h2>
            <p>
                Managing money shouldn't require expensive software or complex spreadsheets. 
                Money Management was built with one goal: make personal finance tracking simple, 
                beautiful, and accessible to everyone. Whether you're a student tracking 
                monthly expenses or someone saving for a big goal — this platform scales 
                with your needs.
            </p>
        </div>

        <div class="reveal">
            <h2><i class="fas fa-cubes"></i> Architecture</h2>
            <p>
                Money Management follows a modular architecture. Each module (Expenses, Savings) 
                is self-contained with its own API, handlers, and UI — but they share a 
                unified authentication system and design language. The master dashboard 
                pulls data from both modules to give you a combined financial overview.
            </p>
        </div>

        <div class="reveal">
            <h2><i class="fas fa-code"></i> Tech Stack</h2>
            <div class="tech-pills">
                <span class="tech-pill"><i class="fab fa-php"></i> PHP 7.2+</span>
                <span class="tech-pill"><i class="fas fa-database"></i> MySQL / MariaDB</span>
                <span class="tech-pill"><i class="fas fa-shield-alt"></i> PDO Prepared Statements</span>
                <span class="tech-pill"><i class="fab fa-js"></i> Vanilla JavaScript</span>
                <span class="tech-pill"><i class="fas fa-palette"></i> Glassmorphism CSS</span>
                <span class="tech-pill"><i class="fas fa-chart-bar"></i> Chart.js</span>
                <span class="tech-pill"><i class="fas fa-bell"></i> SweetAlert2</span>
                <span class="tech-pill"><i class="fab fa-font-awesome"></i> Font Awesome 6</span>
                <span class="tech-pill"><i class="fab fa-github"></i> GitHub Actions CI/CD</span>
            </div>
        </div>

        <div class="reveal">
            <h2><i class="fas fa-shield-alt"></i> Security</h2>
            <p>
                Security is not an afterthought — it's built into every layer. Passwords are 
                bcrypt-hashed. All forms use CSRF tokens. Sessions are regenerated on login 
                with automatic timeouts. Every database query uses parameterized prepared 
                statements. Security headers (X-Frame-Options, CSP, nosniff) are applied 
                to every page.
            </p>
        </div>

        <div class="reveal">
            <h2><i class="fab fa-github"></i> Open Source</h2>
            <p>
                Money Management is fully open source and available on GitHub. Contributions, 
                bug reports, and feature requests are always welcome.
            </p>
            <a href="https://github.com/anankush/PROJECT-M" target="_blank" rel="noopener" class="btn btn-outline" style="margin-top: 0.5rem;">
                <i class="fab fa-github"></i> View on GitHub
            </a>
        </div>

        <div class="reveal" style="margin-top: 4rem; text-align: center;">
            <a href="index.php" class="btn btn-outline" style="margin-right: 1rem;">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
            <?php if (!$isLoggedIn): ?>
                <a href="auth/register.php" class="btn btn-primary">
                    <i class="fas fa-rocket"></i> Get Started
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══ FOOTER ═══ -->
    <footer class="landing-footer">
        <div class="footer-inner" style="justify-content: center; text-align: center;">
            <div class="footer-copy">
                made with love ❤️<br>
                &copy; <?php echo date('Y'); ?> <a href="dev.php" style="color: inherit; text-decoration: none;">PROJECT M</a>
            </div>
        </div>
    </footer>

    <!-- ═══ SCRIPTS ═══ -->
    <script>
        // Navbar scroll
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });

        // Scroll reveal
        const revealElements = document.querySelectorAll('.reveal');
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry, i) => {
                if (entry.isIntersecting) {
                    setTimeout(() => entry.target.classList.add('visible'), i * 120);
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });
        revealElements.forEach(el => revealObserver.observe(el));

        // Mobile Menu Toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navLinks = document.querySelector('.nav-links');
        if (mobileMenuBtn && navLinks) {
            mobileMenuBtn.addEventListener('click', () => {
                navLinks.classList.toggle('active');
            });
        }

        // Cursor glow
        const glow = document.createElement('div');
        glow.className = 'cursor-glow';
        document.body.appendChild(glow);
        document.addEventListener('mousemove', e => {
            requestAnimationFrame(() => {
                glow.style.transform = `translate(${e.clientX - 250}px, ${e.clientY - 250}px)`;
                glow.style.opacity = '1';
            });
        });
    </script>
</body>
</html>
