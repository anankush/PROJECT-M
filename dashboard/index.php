<?php
// ProjectM/dashboard/index.php
require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/csrf.php';
require_once '../includes/functions.php';
require_login();
set_security_headers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | PROJECT M</title>
    <?php echo get_csrf_meta_tag(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/glassmorphism.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="aurora-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>
    <div class="noise-overlay"></div>

    <div class="dashboard-container">
        <!-- Top Navigation Bar -->
        <header class="topbar glass">
            <div class="user-info">
                <div class="topbar-avatar"><i class="fas fa-user-astronaut"></i></div>
                <div>
                    <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>!</h2>
                    <span class="text-muted" style="font-size:0.85rem;"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></span>
                </div>
            </div>
            <div class="topbar-actions">
                <button class="btn btn-ghost" id="refreshDashBtn" onclick="loadDashboardData()">
                    <i class="fas fa-sync-alt" id="refreshIcon"></i> Refresh
                </button>
                <a href="../auth/logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </header>

        <!-- Live Financial Overview Stat Pills -->
        <div class="overview-stats fadeInUp stagger-1">
            <div class="stat-pill glass-card">
                <div class="stat-pill-icon exp-icon"><i class="fas fa-wallet"></i></div>
                <div class="stat-pill-info">
                    <span class="stat-pill-label">Monthly Budget</span>
                    <span class="stat-pill-value" id="expBudgetVal"><i class="fas fa-circle-notch fa-spin fa-xs"></i></span>
                </div>
            </div>
            <div class="stat-pill glass-card">
                <div class="stat-pill-icon" style="background:rgba(239,68,68,0.15);color:#ef4444;"><i class="fas fa-receipt"></i></div>
                <div class="stat-pill-info">
                    <span class="stat-pill-label">Monthly Spent</span>
                    <span class="stat-pill-value" id="expSpentVal"><i class="fas fa-circle-notch fa-spin fa-xs"></i></span>
                </div>
            </div>
            <div class="stat-pill glass-card">
                <div class="stat-pill-icon" style="background:rgba(16,185,129,0.15);color:#10b981;"><i class="fas fa-balance-scale"></i></div>
                <div class="stat-pill-info">
                    <span class="stat-pill-label">Budget Balance</span>
                    <span class="stat-pill-value" id="expBalanceVal"><i class="fas fa-circle-notch fa-spin fa-xs"></i></span>
                </div>
            </div>
            <div class="stat-pill glass-card">
                <div class="stat-pill-icon" style="background:rgba(6,182,212,0.15);color:#06b6d4;"><i class="fas fa-piggy-bank"></i></div>
                <div class="stat-pill-info">
                    <span class="stat-pill-label">Total Saved</span>
                    <span class="stat-pill-value" id="savTotalVal"><i class="fas fa-circle-notch fa-spin fa-xs"></i></span>
                </div>
            </div>
        </div>

        <!-- Module Navigation Cards -->
        <div class="module-cards fadeInUp stagger-2">
            <a href="../Exp/dashboard.php" class="glass-card module-card exp-module">
                <i class="fas fa-wallet"></i>
                <h3>Expense Management</h3>
                <p class="text-secondary">Track, categorize, and analyze your daily expenses.</p>
                <div class="btn btn-ghost" style="margin-top:auto;">Open Exp <i class="fas fa-arrow-right"></i></div>
            </a>
            <a href="../Sav/dashboard.php" class="glass-card module-card sav-module">
                <i class="fas fa-piggy-bank"></i>
                <h3>Savings Management</h3>
                <p class="text-secondary">Set goals, track deposits, and achieve financial freedom.</p>
                <div class="btn btn-ghost" style="margin-top:auto;">Open Sav <i class="fas fa-arrow-right"></i></div>
            </a>
        </div>

        <!-- Financial Charts -->
        <h2 style="margin-bottom:1.5rem;" class="fadeInUp stagger-3">Financial Overview
            <span id="chartMonthLabel" style="font-size:0.85rem; color:var(--text-muted); margin-left:0.75rem; font-weight:400;"></span>
        </h2>
        <div class="charts-container fadeInUp stagger-4">
            <div class="glass-card chart-card">
                <h3>Monthly Cash Flow — Expenses vs Savings</h3>
                <div style="height:300px;">
                    <canvas id="combinedChart"></canvas>
                </div>
            </div>
            <div class="glass-card chart-card">
                <h3>Expenses by Category</h3>
                <div style="height:300px; position:relative;">
                    <canvas id="expenseDonutChart"></canvas>
                    <div id="donutEmpty" style="display:none;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;color:var(--text-muted);">
                        <i class="fas fa-chart-pie fa-2x" style="margin-bottom:0.5rem;"></i><br>No expense data yet
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/csrf.js"></script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/charts.js"></script>
</body>
</html>
