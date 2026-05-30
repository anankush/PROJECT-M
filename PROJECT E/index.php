<?php
$pageTitle = 'EXPENSE MANAGEMENT | Home';
require_once 'includes/header_root.php';
$base = BASE_URL;
?>

<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/global.css">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/index.css">
</head>

<body>
    <div class="aurora-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
        <div class="orb orb-4"></div>
    </div>
    <div class="noise-overlay"></div>

    <!-- NAV -->
    <nav class="nav">
        <a href="<?php echo $base; ?>" class="nav-brand">
            <div class="brand-icon"><i class="fas fa-chart-pie"></i></div>
            EXPENSE <span class="brand-text-sub">MANAGEMENT</span>
        </a>
        <div class="nav-links">
            <a href="<?php echo $base; ?>about.php" class="nav-btn nav-btn-ghost"><i class="fas fa-book-open"></i> <span>How To Use</span></a>
            <a href="https://github.com/anankush" target="_blank" class="nav-btn nav-btn-ghost" title="GitHub"><i class="fab fa-github"></i></a>
        </div>
    </nav>

    <!-- HERO -->
    <section class="hero">
        <div class="hero-badge"><i class="fas fa-shield-alt"></i>&nbsp;Secure &amp; Modern</div>
        <h1 class="hero-title">Expense Management<br>Made Simple</h1>
        <p class="hero-subtitle">A centralized, secure financial tracking solution for teams and individuals — anytime, anywhere.</p>
        <div class="hero-buttons">
            <a href="<?php echo $base; ?>user/login.php" class="hero-btn hero-btn-primary"><i class="fas fa-users"></i> Member Portal</a>
            <a href="<?php echo $base; ?>admin/login.php" class="hero-btn hero-btn-secondary"><i class="fas fa-user-shield"></i> Admin Portal</a>
        </div>
    </section>

    <!-- FEATURES -->
    <section class="features-section">
        <div class="section-header">
            <h2>Everything You Need</h2>
            <p>Built-in features for complete financial transparency</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-folder-open"></i></div>
                <h4>Custom Sections</h4>
                <p>Create unlimited categories like Food, Rent, Utilities — organize your way.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-calendar-alt"></i></div>
                <h4>Month-wise Tracking</h4>
                <p>Filter by any month and year. Set different budgets each month.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                <h4>Custom Fields</h4>
                <p>Add dynamic fields like "Paid By" or "Reference" for richer records.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-lock"></i></div>
                <h4>Secure Auth</h4>
                <p>Bcrypt passwords, OTP recovery, and session management.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-file-export"></i></div>
                <h4>JSON Backup</h4>
                <p>Export all data anytime. Import back in one click — full portability.</p>
            </div>
        </div>
    </section>

    <!-- ABOUT CTA -->
    <section class="about-cta-section">
        <div class="about-cta-card">
            <div>
                <h3>New here? Read the guide first.</h3>
                <p>Step-by-step walkthrough showing how to create sections, add records, and backup data.</p>
            </div>
            <a href="<?php echo $base; ?>about.php" class="about-cta-btn"><i class="fas fa-book-open"></i> How To Use</a>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> PROJECT E. All rights reserved.</p>
        <div class="footer-links">
            <a href="https://linkedin.com/in/itznayan" target="_blank" style="color: var(--accent-secondary);"><i class="fab fa-linkedin"></i> Nayan</a>
            <span style="color: rgba(255,255,255,0.1);">|</span>
            <a href="https://github.com/anankush" target="_blank" style="color: var(--text-muted);"><i class="fab fa-github"></i> GitHub</a>
        </div>
    </footer>

    <script src="<?php echo $base; ?>assets/js/index.js"></script>
</body>
</html>
