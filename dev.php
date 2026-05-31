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
    <title>Developer Portal | PROJECT M</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/glassmorphism.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/landing.css?v=<?php echo time(); ?>">
    <style>
        .dev-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .dev-content {
            flex: 1;
            padding: 8rem 2rem 4rem;
            max-width: 1000px;
            margin: 0 auto;
            width: 100%;
        }
        .dev-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        .dev-header h1 {
            font-size: 2.8rem;
            margin-bottom: 0.5rem;
        }
        .dev-header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }
        .dev-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        .dev-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 2.5rem 2rem;
            border-radius: 20px;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        .dev-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(139, 92, 246, 0.2);
            border-color: rgba(139, 92, 246, 0.4);
        }
        .dev-card-icon {
            font-size: 2.5rem;
            margin-bottom: 1.2rem;
            color: var(--aurora-1);
        }
        .dev-card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-family: var(--font-display);
        }
        .dev-card-desc {
            font-size: 0.9rem;
            color: var(--text-secondary);
            line-height: 1.5;
        }
        
        
        .card-admin { background: rgba(239, 68, 68, 0.05); border-color: rgba(239, 68, 68, 0.2); }
        .card-admin .dev-card-icon { color: #ef4444; }
        .card-admin:hover { box-shadow: 0 10px 30px rgba(239, 68, 68, 0.2); border-color: rgba(239, 68, 68, 0.4); }
        
        .card-git { background: rgba(255, 255, 255, 0.03); border-color: rgba(255, 255, 255, 0.1); }
        .card-git .dev-card-icon { color: #ffffff; }
        .card-git:hover { box-shadow: 0 10px 30px rgba(255, 255, 255, 0.1); border-color: rgba(255, 255, 255, 0.3); }

        .card-home { background: rgba(6, 182, 212, 0.05); border-color: rgba(6, 182, 212, 0.2); }
        .card-home .dev-card-icon { color: #06b6d4; }
        .card-home:hover { box-shadow: 0 10px 30px rgba(6, 182, 212, 0.2); border-color: rgba(6, 182, 212, 0.4); }

        .dev-footer {
            padding: 2rem;
            text-align: center;
            border-top: 1px solid var(--glass-border);
            margin-top: auto;
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

    <div class="dev-wrapper">
        <!-- Navbar -->
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

        <!-- Main Content -->
        <div class="dev-content">
            <div class="reveal visible dev-header">
                <h1><span class="gradient-text">Developer Profile</span></h1>
                <p>Connect with the developer or access the admin portal.</p>
            </div>
            
            <div class="dev-grid reveal visible">
                <!-- LinkedIn -->
                <a href="https://www.linkedin.com/in/itznayan" target="_blank" rel="noopener" class="glass-card dev-card card-home">
                    <i class="fab fa-linkedin dev-card-icon"></i>
                    <div class="dev-card-title">LinkedIn Profile</div>
                    <div class="dev-card-desc">Connect with me professionally.</div>
                </a>

                <!-- GitHub -->
                <a href="https://github.com/anankush/" target="_blank" rel="noopener" class="glass-card dev-card card-git">
                    <i class="fab fa-github dev-card-icon"></i>
                    <div class="dev-card-title">GitHub Profile</div>
                    <div class="dev-card-desc">Check out my open-source projects.</div>
                </a>

                <!-- Admin Portal -->
                <a href="admin_portal.php" class="glass-card dev-card card-admin">
                    <i class="fas fa-user-shield dev-card-icon"></i>
                    <div class="dev-card-title">Admin Portal</div>
                    <div class="dev-card-desc">Restricted access for system administrators.</div>
                </a>
            </div>
        </div>

        <!-- Footer -->
        <footer class="dev-footer">
            <div class="footer-copy">
                made with love ❤️<br>
                &copy; <?php echo date('Y'); ?> <a href="admin_portal.php" style="color: inherit; text-decoration: none;">PROJECT M</a>
            </div>
        </footer>
    </div>

    <script>
        
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
