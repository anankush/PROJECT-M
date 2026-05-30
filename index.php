<?php 
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<div class="glass-panel hero-container">
    <h1 class="hero-title">Welcome to PROJECT M</h1>
    <p class="hero-subtitle">Your ultimate unified financial hub. Manage your expenses, track your savings goals, and visualize your financial journey in one secure platform.</p>
    
    <div class="hero-actions">
        <?php if (isLoggedIn()): ?>
            <a href="dashboard.php" class="btn btn-primary hero-btn">Go to Dashboard</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-primary hero-btn">Login Now</a>
            <a href="register.php" class="btn btn-glass hero-btn">Create Account</a>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-3 features-grid">
    <div class="glass-panel">
        <h3 class="feature-title-1">Unified Login</h3>
        <p>One account to access both your Savings Management (PROJECT S) and Expense Tracking (PROJECT E).</p>
    </div>
    <div class="glass-panel">
        <h3 class="feature-title-2">Savings Goals</h3>
        <p>Set targets for emergencies, vacations, or purchases and track your progress visually.</p>
    </div>
    <div class="glass-panel">
        <h3 class="feature-title-3">Expense Tracking</h3>
        <p>Monitor your daily, monthly, and category-wise spending using the integrated expense engine.</p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
