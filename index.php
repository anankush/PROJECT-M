<?php 
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<div class="glass-panel" style="text-align: center; padding: 60px 20px; margin-top: 50px;">
    <h1 style="font-size: 3rem; background: linear-gradient(to right, #00CEC9, #6C5CE7); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Welcome to PROJECT M</h1>
    <p style="font-size: 1.2rem; max-width: 600px; margin: 20px auto;">Your ultimate unified financial hub. Manage your expenses, track your savings goals, and visualize your financial journey in one secure platform.</p>
    
    <div style="margin-top: 40px; display: flex; gap: 20px; justify-content: center;">
        <?php if (isLoggedIn()): ?>
            <a href="dashboard.php" class="btn btn-primary" style="padding: 15px 40px; font-size: 1.1rem;">Go to Dashboard</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-primary" style="padding: 15px 40px; font-size: 1.1rem;">Login Now</a>
            <a href="register.php" class="btn btn-glass" style="padding: 15px 40px; font-size: 1.1rem;">Create Account</a>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-3" style="margin-top: 40px;">
    <div class="glass-panel">
        <h3 style="color: var(--secondary-color);">Unified Login</h3>
        <p>One account to access both your Savings Management (PROJECT S) and Expense Tracking (PROJECT E).</p>
    </div>
    <div class="glass-panel">
        <h3 style="color: var(--primary-color);">Savings Goals</h3>
        <p>Set targets for emergencies, vacations, or purchases and track your progress visually.</p>
    </div>
    <div class="glass-panel">
        <h3 style="color: var(--accent-color);">Expense Tracking</h3>
        <p>Monitor your daily, monthly, and category-wise spending using the integrated expense engine.</p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
