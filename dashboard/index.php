<?php

require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/csrf.php';
require_once '../includes/functions.php';
require_login();


unset($_SESSION['exp_authorized']);
unset($_SESSION['sav_authorized']);


$exp_ott = generate_ott('exp');
$sav_ott = generate_ott('sav');

set_security_headers();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Money Management</title>
    <?php echo get_csrf_meta_tag(); ?>
    <?php echo get_logout_meta_tag('../'); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/glassmorphism.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.2.0/crypto-js.min.js"></script>
</head>

<body>
    <div class="aurora-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>
    <div class="noise-overlay"></div>

    <input type="file" id="importFile" accept=".encrypted" style="display:none;" onchange="handleImport(event)">

    <div class="dashboard-container">
        <!-- Top Navigation Bar -->
        <header class="topbar glass">
            <div class="user-info">
                <div class="topbar-avatar"><i class="fas fa-user-astronaut"></i></div>
                <div>
                    <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>!</h2>
                    <span class="text-muted"
                        style="font-size:0.85rem;"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></span>
                </div>
            </div>
            <div class="topbar-actions">
                <button class="btn btn-primary" id="quickLogBtn" onclick="openQuickLog()">
                    <i class="fas fa-plus-circle"></i> <span class="hide-mobile">Quick Log</span>
                </button>
                <button class="btn btn-ghost" id="refreshDashBtn" onclick="loadDashboardData(currentSelectedMonth)">
                    <i class="fas fa-sync-alt" id="refreshIcon"></i> Refresh
                </button>
                <button class="btn btn-ghost" id="openSettingsBtn" onclick="openGlobalSettings()">
                    <i class="fas fa-cog"></i> <span class="hide-mobile">Settings</span>
                </button>
                <a href="<?php echo get_logout_url('../'); ?>" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </header>

        <!-- Live Financial Overview Stat Pills -->
        <div class="overview-stats fadeInUp stagger-1">
            <div class="stat-pill glass-card">
                <div class="stat-pill-icon exp-icon"><i class="fas fa-wallet"></i></div>
                <div class="stat-pill-info">
                    <span class="stat-pill-label">Monthly Budget</span>
                    <span class="stat-pill-value" id="expBudgetVal"><i
                            class="fas fa-circle-notch fa-spin fa-xs"></i></span>
                </div>
            </div>
            <div class="stat-pill glass-card">
                <div class="stat-pill-icon" style="background:rgba(239,68,68,0.15);color:#ef4444;"><i
                        class="fas fa-receipt"></i></div>
                <div class="stat-pill-info">
                    <span class="stat-pill-label">Monthly Spent</span>
                    <span class="stat-pill-value" id="expSpentVal"><i
                            class="fas fa-circle-notch fa-spin fa-xs"></i></span>
                </div>
            </div>
            <div class="stat-pill glass-card">
                <div class="stat-pill-icon" style="background:rgba(16,185,129,0.15);color:#10b981;"><i
                        class="fas fa-balance-scale"></i></div>
                <div class="stat-pill-info">
                    <span class="stat-pill-label">Budget Balance</span>
                    <span class="stat-pill-value" id="expBalanceVal"><i
                            class="fas fa-circle-notch fa-spin fa-xs"></i></span>
                </div>
            </div>
            <div class="stat-pill glass-card">
                <div class="stat-pill-icon" style="background:rgba(6,182,212,0.15);color:#06b6d4;"><i
                        class="fas fa-piggy-bank"></i></div>
                <div class="stat-pill-info">
                    <span class="stat-pill-label">Total Saved</span>
                    <span class="stat-pill-value" id="savTotalVal"><i
                            class="fas fa-circle-notch fa-spin fa-xs"></i></span>
                </div>
            </div>
            <div class="stat-pill glass-card">
                <div class="stat-pill-icon" style="background:rgba(139,92,246,0.15);color:#8b5cf6;"><i
                        class="fas fa-chart-line"></i></div>
                <div class="stat-pill-info">
                    <span class="stat-pill-label">Net Worth</span>
                    <span class="stat-pill-value" id="netWorthVal"><i
                            class="fas fa-circle-notch fa-spin fa-xs"></i></span>
                </div>
            </div>
            <div class="stat-pill glass-card">
                <div class="stat-pill-icon" style="background:rgba(16,185,129,0.15);color:#10b981;"><i
                        class="fas fa-heartbeat"></i></div>
                <div class="stat-pill-info">
                    <span class="stat-pill-label">Health Score</span>
                    <span class="stat-pill-value" id="healthScoreVal"><i
                            class="fas fa-circle-notch fa-spin fa-xs"></i></span>
                </div>
            </div>
        </div>

        <!-- Module Navigation Cards -->
        <div class="module-cards fadeInUp stagger-2">
            <a href="#" onclick="navigateSecurely('exp'); return false;" class="glass-card module-card exp-module" id="expModuleLink">
                <i class="fas fa-wallet"></i>
                <h3>Expense Management</h3>
                <p class="text-secondary">Track, categorize, and analyze your daily expenses.</p>
                <div class="btn btn-ghost" style="margin-top:auto;"> Expense Panel <i class="fas fa-arrow-right"></i>
                </div>
            </a>
            <a href="#" onclick="navigateSecurely('sav'); return false;" class="glass-card module-card sav-module" id="savModuleLink">
                <i class="fas fa-piggy-bank"></i>
                <h3>Savings Management</h3>
                <p class="text-secondary">Set goals, track deposits, and achieve financial freedom.</p>
                <div class="btn btn-ghost" style="margin-top:auto;"> Savings Panel <i class="fas fa-arrow-right"></i>
                </div>
            </a>
        </div>

        <!-- New Goal Progress & Alerts Section -->
        <div class="dashboard-widgets fadeInUp stagger-3" style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-bottom: 3rem;">
            <!-- Active Savings Goals -->
            <div class="glass-card" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
                <h3 style="font-size: 1.1rem; font-weight: 600; color: var(--text-primary); display: flex; align-items: center; gap: 8px; margin-bottom: 0.5rem;">
                    <i class="fas fa-bullseye" style="color: var(--aurora-1);"></i> Active Savings Goals
                </h3>
                <div id="goalsProgressList" style="display: flex; flex-direction: column; gap: 1rem; overflow-y: auto; max-height: 220px; padding-right: 4px;">
                    <div style="text-align:center; padding:2rem; color:var(--text-muted);"><i class="fas fa-circle-notch fa-spin"></i> Loading goals...</div>
                </div>
            </div>
            
            <!-- Upcoming Deadlines -->
            <div class="glass-card" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
                <h3 style="font-size: 1.1rem; font-weight: 600; color: var(--text-primary); display: flex; align-items: center; gap: 8px; margin-bottom: 0.5rem;">
                    <i class="fas fa-hourglass-half" style="color: var(--danger);"></i> Upcoming Deadlines
                </h3>
                <div id="upcomingDeadlinesList" style="display: flex; flex-direction: column; gap: 0.75rem; overflow-y: auto; max-height: 220px; padding-right: 4px;">
                    <div style="text-align:center; padding:2rem; color:var(--text-muted);"><i class="fas fa-circle-notch fa-spin"></i> Loading deadlines...</div>
                </div>
            </div>
        </div>

        <!-- Financial Charts -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:10px;" class="fadeInUp stagger-3">
            <h2 style="margin:0;">Financial Overview
                <span id="chartMonthLabel"
                    style="font-size:0.85rem; color:var(--text-muted); margin-left:0.75rem; font-weight:400;">(All-Time)</span>
            </h2>
            <div style="display:flex; align-items:center; gap:10px;">
                <select id="dashboardMonthFilter" class="theme-input-select" style="padding: 0.4rem 1rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: var(--text-primary); font-size: 0.85rem; outline: none; height: auto;" onchange="filterDashboardByMonth(this.value)">
                    <option value="all">All-Time</option>
                </select>
                <button id="resetFilterBtn" class="btn btn-ghost" style="font-size: 0.8rem; padding: 0.4rem 0.8rem; display:none;" onclick="resetDashboardFilter()">
                    <i class="fas fa-times-circle"></i> Clear Filter
                </button>
            </div>
        </div>
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
                    <div id="donutEmpty"
                        style="display:none;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;color:var(--text-muted);">
                        <i class="fas fa-chart-pie fa-2x" style="margin-bottom:0.5rem;"></i><br>No expense data yet
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Section: Transactions & Summary Table -->
        <div class="details-container fadeInUp stagger-5">
            <!-- Month-wise Summary Table -->
            <div class="glass-card details-card summary-table-card">
                <h3>Month-wise Summary</h3>
                <div class="table-responsive">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Budget</th>
                                <th>Spent</th>
                                <th>Saved</th>
                            </tr>
                        </thead>
                        <tbody id="summaryTableBody">
                            <tr><td colspan="4" style="text-align:center; padding:2rem;"><i class="fas fa-circle-notch fa-spin"></i> Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Transactions List -->
            <div class="glass-card details-card recent-tx-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="margin: 0;">Recent Transactions</h3>
                    <a href="#" onclick="navigateSecurely('exp'); return false;" class="btn btn-ghost" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="transaction-list" id="recentTransactionsList">
                    <div style="text-align:center; padding:2rem; color:var(--text-muted);"><i class="fas fa-circle-notch fa-spin"></i> Loading...</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const API_URL = '../Exp/api/api.php';
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let userCurrency = '<?php echo htmlspecialchars($_SESSION['currency'] ?? "₹"); ?>';
        let email = '<?php echo htmlspecialchars($_SESSION['user_email'] ?? ""); ?>';
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/csrf.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/charts.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/settings.js?v=<?php echo time(); ?>"></script>
</body>

</html>