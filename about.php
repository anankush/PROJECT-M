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
    <title>About | Money Management</title>
    <meta name="description" content="Learn about Money Management — a free, open-source personal finance management platform built with PHP, MySQL, and a stunning Glassmorphism UI. Security-hardened and built for real-world use.">
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
            <a href="index.php#features" class="desktop-only">Features</a>
            <a href="dev.php" class="desktop-only">Dev</a>
            <?php if ($isLoggedIn): ?>
                <a href="dashboard/index.php" class="btn btn-primary">Dashboard</a>
            <?php else: ?>
                <a href="auth/login.php" class="desktop-only">Login</a>
                <a href="auth/register.php" class="btn btn-primary">Get Started</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- ═══ ABOUT CONTENT ═══ -->
    <div class="about-container">

        <!-- Hero -->
        <div class="reveal">
            <h1><span class="gradient-text">About PROJECT M</span></h1>
            <p class="about-hero-sub">
                A free, open-source personal finance command center — built to give you complete control over every rupee. 
                Combining expense tracking and savings goals under one unified, security-hardened dashboard.
            </p>
        </div>

        <!-- Why -->
        <div class="about-section reveal">
            <div class="about-section-title">
                <i class="fas fa-lightbulb"></i>
                Why PROJECT M?
            </div>
            <div class="about-card">
                <p>
                    Managing money shouldn't require expensive software or complex spreadsheets. 
                    PROJECT M was built with one goal: make personal finance tracking simple, beautiful, and accessible to everyone. 
                    Whether you're a student tracking monthly expenses or saving toward a life goal — this platform scales with your needs.
                </p>
            </div>
        </div>

        <!-- Architecture -->
        <div class="about-section reveal">
            <div class="about-section-title">
                <i class="fas fa-cubes"></i>
                Architecture
            </div>
            <div class="about-card">
                <p>
                    PROJECT M follows a modular architecture. Each module (Expenses, Savings) is self-contained with its own API, handlers, and UI — 
                    but they share a unified authentication system, CSRF layer, and design language. 
                    The master dashboard pulls cross-module data to give you a complete combined financial overview in real time.
                </p>
            </div>
        </div>

        <!-- Tech Stack -->
        <div class="about-section reveal">
            <div class="about-section-title">
                <i class="fas fa-code"></i>
                Tech Stack
            </div>
            <div class="tech-pills">
                <span class="tech-pill"><i class="fab fa-php"></i> PHP 8.0+</span>
                <span class="tech-pill"><i class="fas fa-database"></i> MySQL / MariaDB</span>
                <span class="tech-pill"><i class="fas fa-shield-alt"></i> PDO Prepared Statements</span>
                <span class="tech-pill"><i class="fab fa-js"></i> Vanilla JavaScript (ES6+)</span>
                <span class="tech-pill"><i class="fas fa-palette"></i> Glassmorphism CSS</span>
                <span class="tech-pill"><i class="fas fa-chart-bar"></i> Chart.js</span>
                <span class="tech-pill"><i class="fas fa-bell"></i> SweetAlert2</span>
                <span class="tech-pill"><i class="fab fa-font-awesome"></i> Font Awesome 6</span>
                <span class="tech-pill"><i class="fab fa-github"></i> GitHub Actions CI/CD</span>
                <span class="tech-pill"><i class="fas fa-robot"></i> Gemini AI Integration</span>
            </div>
        </div>

        <!-- Security -->
        <div class="about-section reveal">
            <div class="about-section-title">
                <i class="fas fa-shield-alt"></i>
                Security — Built-in, Not Bolted-on
            </div>
            <div class="about-card" style="margin-bottom: 1.25rem;">
                <p>
                    Security is not an afterthought — it's built into every layer of the stack. 
                    Every input is validated, every query is parameterized, every page is protected. 
                    The platform has been reviewed against common pentesting attack vectors and hardened accordingly.
                </p>
            </div>
            <div class="audit-grid">
                <div class="audit-item">
                    <div class="audit-item-icon"><i class="fas fa-key"></i></div>
                    <div class="audit-item-text">
                        <strong>bcrypt Password Hashing</strong>
                        <span>Timing-safe password verification using industry-standard bcrypt</span>
                    </div>
                </div>
                <div class="audit-item">
                    <div class="audit-item-icon"><i class="fas fa-shield-virus"></i></div>
                    <div class="audit-item-text">
                        <strong>CSRF Protection</strong>
                        <span>Tokens on every form and API endpoint — no cross-site forgery possible</span>
                    </div>
                </div>
                <div class="audit-item">
                    <div class="audit-item-icon"><i class="fas fa-database"></i></div>
                    <div class="audit-item-text">
                        <strong>SQL Injection Prevention</strong>
                        <span>100% PDO prepared statements — zero raw query execution</span>
                    </div>
                </div>
                <div class="audit-item">
                    <div class="audit-item-icon"><i class="fas fa-user-lock"></i></div>
                    <div class="audit-item-text">
                        <strong>Session Security</strong>
                        <span>Auto-timeout, fixation prevention, secure cookie flags, concurrent session detection</span>
                    </div>
                </div>
                <div class="audit-item">
                    <div class="audit-item-icon"><i class="fas fa-fingerprint"></i></div>
                    <div class="audit-item-text">
                        <strong>IDOR Prevention</strong>
                        <span>Server-side ownership verification on every data operation</span>
                    </div>
                </div>
                <div class="audit-item">
                    <div class="audit-item-icon"><i class="fas fa-code"></i></div>
                    <div class="audit-item-text">
                        <strong>XSS Prevention</strong>
                        <span>All output encoded via <code>htmlspecialchars()</code> and <code>escapeHtml()</code></span>
                    </div>
                </div>
                <div class="audit-item">
                    <div class="audit-item-icon"><i class="fas fa-window-minimize"></i></div>
                    <div class="audit-item-text">
                        <strong>Clickjacking Blocked</strong>
                        <span>X-Frame-Options: DENY + Content-Security-Policy headers active</span>
                    </div>
                </div>
                <div class="audit-item">
                    <div class="audit-item-icon"><i class="fas fa-ban"></i></div>
                    <div class="audit-item-text">
                        <strong>Rate Limiting</strong>
                        <span>Login and sensitive actions are protected against brute-force attempts</span>
                    </div>
                </div>
                <div class="audit-item">
                    <div class="audit-item-icon"><i class="fas fa-envelope-open-text"></i></div>
                    <div class="audit-item-text">
                        <strong>Disposable Email Shield</strong>
                        <span>Dual-layer real-time check blocks temporary/burner email registrations</span>
                    </div>
                </div>
                <div class="audit-item">
                    <div class="audit-item-icon"><i class="fas fa-terminal"></i></div>
                    <div class="audit-item-text">
                        <strong>Zero Info Leakage</strong>
                        <span>No debug output, no console.error exposure, directory listing disabled</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Open Source -->
        <div class="about-section reveal">
            <div class="about-section-title">
                <i class="fab fa-github"></i>
                Open Source
            </div>
            <div class="about-card">
                <p>
                    PROJECT M is fully open source and available on GitHub. 
                    Contributions, bug reports, and feature requests are always welcome.
                </p>
                <a href="https://github.com/anankush/PROJECT-M" target="_blank" rel="noopener" class="btn btn-outline" style="margin-top: 1rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fab fa-github"></i> View on GitHub
                </a>
            </div>
        </div>

        <!-- Back / CTA -->
        <div class="reveal" style="margin-top: 3rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
            <a href="index.php" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
            <?php if (!$isLoggedIn): ?>
                <a href="auth/register.php" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-rocket"></i> Get Started Free
                </a>
            <?php endif; ?>
        </div>

    </div>

    <!-- ═══ FOOTER ═══ -->
    <footer class="landing-footer">
        <div class="footer-inner" style="justify-content: center; text-align: center;">
            <div class="footer-copy">
                made with love ❤️<br>
                &copy; <?php echo date('Y'); ?> <a href="admin_portal.php" style="color: inherit; text-decoration: none;">PROJECT M</a>
            </div>
        </div>
    </footer>

    <script>
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });

        const revealElements = document.querySelectorAll('.reveal');
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry, i) => {
                if (entry.isIntersecting) {
                    setTimeout(() => entry.target.classList.add('visible'), i * 120);
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        revealElements.forEach(el => revealObserver.observe(el));

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
