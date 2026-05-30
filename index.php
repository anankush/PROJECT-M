<?php
// PROJECT M — Landing Page
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
    <title>PROJECT M — Your Complete Financial Command Center</title>
    <meta name="description" content="Track expenses, grow savings, and master your money with PROJECT M. A premium personal finance management platform with real-time analytics.">
    <meta name="keywords" content="expense tracker, savings goals, money management, finance dashboard, budget planner">
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
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
        <div class="orb orb-4"></div>
    </div>
    <div class="noise-overlay"></div>

    <!-- ═══ NAVBAR ═══ -->
    <nav class="landing-nav" id="navbar">
        <a href="index.php" class="nav-logo">PROJECT M</a>
        <div class="nav-links">
            <a href="#features" class="desktop-only">Features</a>
            <a href="#how-it-works" class="desktop-only">How It Works</a>
            <a href="about.php" class="desktop-only">About</a>
            <?php if ($isLoggedIn): ?>
                <a href="dashboard/index.php" class="btn btn-primary">Go to Dashboard</a>
            <?php else: ?>
                <a href="auth/login.php">Login</a>
                <a href="auth/register.php" class="btn btn-primary">Get Started</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- ═══ HERO ═══ -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fas fa-bolt"></i>
                Free &amp; Open Source
            </div>
            <h1>
                Track Expenses.<br>
                Grow Savings.<br>
                <span class="gradient-text">Master Money.</span>
            </h1>
            <p>
                Your complete financial command center. Organize every rupee with 
                custom categories, set savings goals, and watch your progress 
                unfold with beautiful real-time analytics.
            </p>
            <div class="hero-buttons">
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard/index.php" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Open Dashboard
                    </a>
                <?php else: ?>
                    <a href="auth/register.php" class="btn btn-primary">
                        <i class="fas fa-rocket"></i> Get Started — It's Free
                    </a>
                    <a href="auth/login.php" class="btn btn-outline">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                <?php endif; ?>
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="hero-stat-value">100%</div>
                    <div class="hero-stat-label">Free Forever</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-value">256-bit</div>
                    <div class="hero-stat-label">Encryption</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-value">∞</div>
                    <div class="hero-stat-label">Custom Fields</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ FEATURES ═══ -->
    <section class="landing-section" id="features">
        <div class="section-header reveal">
            <h2>Everything You Need to<br><span class="gradient-text">Take Control</span></h2>
            <p>Three powerful modules working together to give you a complete picture of your finances.</p>
        </div>
        <div class="features-grid">
            <!-- Expense Tracking -->
            <div class="glass-card feature-card reveal">
                <div class="feature-icon exp">
                    <i class="fas fa-wallet"></i>
                </div>
                <h3>Expense Tracking</h3>
                <p>Organize spending into custom sections with monthly budgets and detailed records.</p>
                <ul class="feature-list">
                    <li><i class="fas fa-check-circle"></i> Unlimited custom categories</li>
                    <li><i class="fas fa-check-circle"></i> Custom data fields per section</li>
                    <li><i class="fas fa-check-circle"></i> Monthly budget tracking</li>
                    <li><i class="fas fa-check-circle"></i> Import &amp; Export JSON backups</li>
                </ul>
            </div>
            <!-- Savings Goals -->
            <div class="glass-card feature-card reveal">
                <div class="feature-icon sav">
                    <i class="fas fa-piggy-bank"></i>
                </div>
                <h3>Savings Goals</h3>
                <p>Set targets, make deposits, and watch your savings grow with visual progress tracking.</p>
                <ul class="feature-list">
                    <li><i class="fas fa-check-circle"></i> Create unlimited goals</li>
                    <li><i class="fas fa-check-circle"></i> Deposit &amp; withdraw tracking</li>
                    <li><i class="fas fa-check-circle"></i> Deadline reminders</li>
                    <li><i class="fas fa-check-circle"></i> Progress bar visualization</li>
                </ul>
            </div>
            <!-- Dashboard -->
            <div class="glass-card feature-card reveal">
                <div class="feature-icon dash">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Live Dashboard</h3>
                <p>See everything at a glance — combined analytics across expenses and savings.</p>
                <ul class="feature-list">
                    <li><i class="fas fa-check-circle"></i> Real-time stat cards</li>
                    <li><i class="fas fa-check-circle"></i> Chart.js powered analytics</li>
                    <li><i class="fas fa-check-circle"></i> Monthly breakdown graphs</li>
                    <li><i class="fas fa-check-circle"></i> Cross-module integration</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- ═══ HOW IT WORKS ═══ -->
    <section class="landing-section" id="how-it-works">
        <div class="section-header reveal">
            <h2>Get Started in<br><span class="gradient-text">3 Simple Steps</span></h2>
            <p>From signup to full financial clarity in under a minute.</p>
        </div>
        <div class="steps-grid">
            <div class="step-card reveal">
                <div class="step-number">1</div>
                <h3>Create Account</h3>
                <p>Sign up with your email. Verify via OTP and you're in — no payment required, ever.</p>
            </div>
            <div class="step-card reveal">
                <div class="step-number">2</div>
                <h3>Add Your Data</h3>
                <p>Create expense sections, set budgets, add savings goals — customize everything.</p>
            </div>
            <div class="step-card reveal">
                <div class="step-number">3</div>
                <h3>Track & Grow</h3>
                <p>Watch your dashboard come alive with charts, stats, and actionable insights.</p>
            </div>
        </div>
    </section>

    <!-- ═══ SECURITY ═══ -->
    <section class="landing-section" id="security">
        <div class="section-header reveal">
            <h2>Built With<br><span class="gradient-text">Security First</span></h2>
            <p>Your financial data deserves enterprise-grade protection.</p>
        </div>
        <div class="security-grid">
            <div class="glass-card security-item reveal">
                <div class="security-icon"><i class="fas fa-lock"></i></div>
                <div>
                    <h4>Encrypted Passwords</h4>
                    <p>bcrypt hashing with timing-safe verification protects every account.</p>
                </div>
            </div>
            <div class="glass-card security-item reveal">
                <div class="security-icon"><i class="fas fa-shield-alt"></i></div>
                <div>
                    <h4>CSRF Protection</h4>
                    <p>Every form and API call is guarded against cross-site request forgery.</p>
                </div>
            </div>
            <div class="glass-card security-item reveal">
                <div class="security-icon"><i class="fas fa-user-shield"></i></div>
                <div>
                    <h4>Session Security</h4>
                    <p>Auto-timeout, session fixation prevention, and secure cookie flags.</p>
                </div>
            </div>
            <div class="glass-card security-item reveal">
                <div class="security-icon"><i class="fas fa-database"></i></div>
                <div>
                    <h4>Prepared Statements</h4>
                    <p>All database queries use parameterized PDO — zero SQL injection risk.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ CTA ═══ -->
    <section class="cta-section">
        <div class="glass-card cta-card reveal">
            <h2>Ready to Master<br>Your Finances?</h2>
            <p>Join PROJECT M today and start tracking every rupee with confidence.</p>
            <div class="hero-buttons">
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard/index.php" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Go to Dashboard
                    </a>
                <?php else: ?>
                    <a href="auth/register.php" class="btn btn-primary">
                        <i class="fas fa-rocket"></i> Create Free Account
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ═══ FOOTER ═══ -->
    <footer class="landing-footer">
        <div class="footer-inner">
            <div class="footer-links">
                <a href="about.php">About</a>
                <a href="#features">Features</a>
                <a href="auth/login.php">Login</a>
                <a href="auth/register.php">Register</a>
                <a href="https://github.com/anankush/PROJECT-M" target="_blank" rel="noopener">
                    <i class="fab fa-github"></i> GitHub
                </a>
            </div>
            <div class="footer-copy">
                &copy; <?php echo date('Y'); ?> PROJECT M. Built with <i class="fas fa-heart" style="color: #ef4444;"></i>
            </div>
        </div>
    </footer>

    <!-- ═══ SCRIPTS ═══ -->
    <script>
        // Navbar scroll effect
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                const target = document.querySelector(link.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Scroll reveal animation
        const revealElements = document.querySelectorAll('.reveal');
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry, i) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.classList.add('visible');
                    }, i * 100);
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });

        revealElements.forEach(el => revealObserver.observe(el));

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
        document.addEventListener('mouseleave', () => { glow.style.opacity = '0'; });
    </script>
</body>
</html>
