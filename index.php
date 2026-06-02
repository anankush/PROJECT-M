<?php

require_once 'includes/db.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';
set_security_headers();

$isUserLoggedIn = isset($_SESSION['user_id']);
$isAdminLoggedIn = isset($_SESSION['admin_id']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Money Management — Your Complete Financial Command Center</title>
    <meta name="description"
        content="Track expenses, grow savings, and master your money with Money Management. A premium personal finance management platform with real-time analytics.">
    <meta name="keywords" content="expense tracker, savings goals, money management, finance dashboard, budget planner">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/glassmorphism.css">
    <link rel="stylesheet" href="assets/css/landing.css">
    <style><?php include 'assets/css/ai_chat.css'; ?></style>
</head>

<body>
    <!-- Colorful Animated Background Overlay -->
    <div class="color-combo-bg"></div>

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
        <a href="index.php" class="nav-logo">Money Management</a>
        <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Toggle Navigation">
            <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
        <div class="nav-links">
            <a href="#features" class="desktop-only">Features</a>
            <a href="#how-it-works" class="desktop-only">How It Works</a>
            <a href="about.php" class="desktop-only">About</a>
            <a href="dev.php" class="desktop-only">Dev</a>
            <?php if ($isUserLoggedIn): ?>
                <a href="dashboard/index.php" class="btn btn-primary">Go to Dashboard</a>
            <?php elseif ($isAdminLoggedIn): ?>
                <a href="admin/index.php" class="btn btn-primary">Go to Admin Panel</a>
            <?php else: ?>
                <a href="auth/login.php" class="btn btn-outline login-btn">Login</a>
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
                <?php if ($isUserLoggedIn): ?>
                    <a href="dashboard/index.php" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Open Dashboard
                    </a>
                <?php elseif ($isAdminLoggedIn): ?>
                    <a href="admin/index.php" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Open Admin Panel
                    </a>
                <?php else: ?>
                    <a href="auth/register.php" class="btn btn-primary">
                        <i class="fas fa-rocket"></i> Get Started — It's Free
                    </a>
                    <a href="auth/login.php" class="btn btn-outline login-btn">
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
                    <svg viewBox="0 0 64 64" width="50" height="50" xmlns="http://www.w3.org/2000/svg">
                      <defs>
                        <linearGradient id="expGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                          <stop offset="0%" stop-color="#ff758c" />
                          <stop offset="100%" stop-color="#ff7eb3" />
                        </linearGradient>
                      </defs>
                      <rect x="8" y="16" width="48" height="32" rx="6" fill="url(#expGrad)" />
                      <rect x="8" y="24" width="48" height="8" fill="#fff" opacity="0.3" />
                      <circle cx="32" cy="32" r="6" fill="#fff" />
                    </svg>
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
                    <svg viewBox="0 0 64 64" width="50" height="50" xmlns="http://www.w3.org/2000/svg">
                      <defs>
                        <linearGradient id="savGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                          <stop offset="0%" stop-color="#43e97b" />
                          <stop offset="100%" stop-color="#38f9d7" />
                        </linearGradient>
                      </defs>
                      <circle cx="32" cy="32" r="24" fill="url(#savGrad)" />
                      <path d="M32 18v12M26 24h12M25 42a8 8 0 0 0 14 0" stroke="#fff" stroke-width="4" stroke-linecap="round" fill="none" />
                    </svg>
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
                    <svg viewBox="0 0 64 64" width="50" height="50" xmlns="http://www.w3.org/2000/svg">
                      <defs>
                        <linearGradient id="dashGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                          <stop offset="0%" stop-color="#4facfe" />
                          <stop offset="100%" stop-color="#00f2fe" />
                        </linearGradient>
                      </defs>
                      <rect x="12" y="32" width="10" height="20" rx="3" fill="url(#dashGrad)" />
                      <rect x="27" y="16" width="10" height="36" rx="3" fill="url(#dashGrad)" />
                      <rect x="42" y="24" width="10" height="28" rx="3" fill="url(#dashGrad)" />
                    </svg>
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
                <div class="security-icon">
                    <svg viewBox="0 0 64 64" width="32" height="32" xmlns="http://www.w3.org/2000/svg">
                      <rect x="16" y="28" width="32" height="24" rx="4" fill="#f59e0b"/>
                      <path d="M22 28V18a10 10 0 0 1 20 0v10" stroke="#f59e0b" stroke-width="4" stroke-linecap="round" fill="none"/>
                      <circle cx="32" cy="40" r="3" fill="#fff"/>
                    </svg>
                </div>
                <div>
                    <h4>Encrypted Passwords</h4>
                    <p>bcrypt hashing with timing-safe verification protects every account.</p>
                </div>
            </div>
            <div class="glass-card security-item reveal">
                <div class="security-icon">
                    <svg viewBox="0 0 64 64" width="32" height="32" xmlns="http://www.w3.org/2000/svg">
                      <path d="M32 10L14 18v16c0 14 18 20 18 20s18-6 18-20V18z" fill="#10b981"/>
                      <path d="M26 34l4 4 8-10" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                    </svg>
                </div>
                <div>
                    <h4>CSRF Protection</h4>
                    <p>Every form and API call is guarded against cross-site request forgery.</p>
                </div>
            </div>
            <div class="glass-card security-item reveal">
                <div class="security-icon">
                    <svg viewBox="0 0 64 64" width="32" height="32" xmlns="http://www.w3.org/2000/svg">
                      <circle cx="32" cy="22" r="10" fill="#3b82f6"/>
                      <path d="M16 48c0-8 8-14 16-14s16 6 16 14" stroke="#3b82f6" stroke-width="6" stroke-linecap="round" fill="none"/>
                    </svg>
                </div>
                <div>
                    <h4>Session Security</h4>
                    <p>Auto-timeout, session fixation prevention, and secure cookie flags.</p>
                </div>
            </div>
            <div class="glass-card security-item reveal">
                <div class="security-icon">
                    <svg viewBox="0 0 64 64" width="32" height="32" xmlns="http://www.w3.org/2000/svg">
                      <ellipse cx="32" cy="18" rx="20" ry="8" fill="#8b5cf6"/>
                      <path d="M12 18v28c0 4.4 9 8 20 8s20-3.6 20-8V18" stroke="#8b5cf6" stroke-width="6" fill="none"/>
                      <ellipse cx="32" cy="32" rx="20" ry="8" stroke="#8b5cf6" stroke-width="4" fill="none"/>
                    </svg>
                </div>
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
            <p>Join Money Management today and start tracking every rupee with confidence.</p>
            <div class="hero-buttons">
                <?php if ($isUserLoggedIn): ?>
                    <a href="dashboard/index.php" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Go to Dashboard
                    </a>
                <?php elseif ($isAdminLoggedIn): ?>
                    <a href="admin/index.php" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Go to Admin Panel
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
        <div class="footer-inner" style="justify-content: center; text-align: center;">
            <div class="footer-copy">
                made with love ❤️<br>
                &copy; <?php echo date('Y'); ?> <a href="admin_portal.php" style="color: inherit; text-decoration: none;">PROJECT M</a>
            </div>
        </div>
    </footer>

    <!-- ═══ SCRIPTS ═══ -->
    <script>
        
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });

        
        document.querySelectorAll('a[href^="#"]').forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                const target = document.querySelector(link.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        
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

        
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navLinks = document.querySelector('.nav-links');
        if (mobileMenuBtn && navLinks) {
            mobileMenuBtn.addEventListener('click', () => {
                navLinks.classList.toggle('active');
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

    <!-- Gemini AI Chatbot -->
    <div id="aiChatBubble" title="Ask AI Assistant">
        <svg viewBox="0 0 24 24" width="30" height="30" fill="url(#geminiGrad)" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 0 6px rgba(6, 182, 212, 0.5)); display: block;">
            <defs>
                <linearGradient id="geminiGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#9d4edd" />
                    <stop offset="50%" stop-color="#8b5cf6" />
                    <stop offset="100%" stop-color="#06b6d4" />
                </linearGradient>
            </defs>
            <path d="M12 2C12 7.5 16.5 12 22 12C16.5 12 12 16.5 12 22C12 16.5 7.5 12 2 12C7.5 12 12 7.5 12 2Z" />
        </svg>
    </div>

    <div id="aiChatWindow">
        <div class="ai-chat-header">
            <div class="ai-chat-title">
                <div class="ai-avatar-container">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="url(#geminiGradHeader)" xmlns="http://www.w3.org/2000/svg" style="display: block;">
                        <defs>
                            <linearGradient id="geminiGradHeader" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#9d4edd" />
                                <stop offset="100%" stop-color="#06b6d4" />
                            </linearGradient>
                        </defs>
                        <path d="M12 2C12 7.5 16.5 12 22 12C16.5 12 12 16.5 12 22C12 16.5 7.5 12 2 12C7.5 12 12 7.5 12 2Z" />
                    </svg>
                </div>
                <div class="ai-chat-title-text">
                    <h4>ZNODA AI ASSISTANT</h4>
                    <span>Active</span>
                </div>
            </div>
            <button class="ai-chat-close" id="aiChatClose"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="ai-chat-messages" id="aiChatMessages"></div>
        
        <div class="ai-chat-suggestions">
            <div class="ai-pill">What is Project M?</div>
            <div class="ai-pill">Is it secure?</div>
            <div class="ai-pill">How do I start?</div>
        </div>
        
        <div class="ai-chat-footer">
            <div class="ai-input-wrapper">
                <input type="text" id="aiMessageInput" placeholder="Type a message..." autocomplete="off">
                <button class="ai-send-btn" id="aiSendBtn"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

    <script>
        window.aiChatConfig = {
            isLoggedIn: false,
            csrfToken: '',
            apiEndpoint: 'api/ai_chat.php',
            actionEndpoint: ''
        };
    </script>
    <script><?php include 'assets/js/ai_chat.js'; ?></script>
</body>
</html>