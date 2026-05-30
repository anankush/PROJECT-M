<?php
// ============================================================
// PROJECT S: User Dashboard
// ============================================================

require_once __DIR__ . '/../includes/db.php';

$central_security = dirname(__DIR__, 3) . '/includes/security.php';
if (file_exists($central_security)) {
    require_once $central_security;
} else {
    require_once __DIR__ . '/../includes/config.php';
}

session_start_secure();
set_security_headers();
check_session_timeout();

if (empty($_SESSION['user_id'])) {
    $root = defined('ROOT_BASE_URL') ? ROOT_BASE_URL : '/';
    header('Location: ' . $root . 'login.php');
    exit;
}

$base_s     = defined('BASE_URL_S_SELF') ? BASE_URL_S_SELF : '/PROJECT S/';
$root       = defined('ROOT_BASE_URL')   ? ROOT_BASE_URL   : '/';
$csrf_token = generate_csrf_token();
$currency   = $_SESSION['user_currency'] ?? '₹';
$email      = $_SESSION['user_email']    ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Savings Manager — PROJECT S</title>
    <meta name="description" content="Track your savings goals, deposits, and withdrawals in one place.">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token) ?>">
    <meta name="base-url-s" content="<?= htmlspecialchars($base_s) ?>">
    <meta name="root-url"   content="<?= htmlspecialchars($root)   ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($base_s) ?>assets/css/dashboard.css">
</head>
<body>
    <div class="aurora-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>
    <div class="noise-overlay"></div>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <aside class="sidebar" id="appSidebar">
        <div class="sidebar-header">
            <span class="sidebar-logo"><i class="fas fa-piggy-bank"></i> <strong>PROJECT S</strong></span>
            <button class="sidebar-close" onclick="toggleSidebar()" aria-label="Close menu"><i class="fas fa-times"></i></button>
        </div>
        <div class="sidebar-user">
            <div class="user-avatar"><i class="fas fa-user-circle"></i></div>
            <div>
                <p class="user-email" id="userEmailDisplay"><?= htmlspecialchars($email) ?></p>
                <span class="role-badge">Savings</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="<?= htmlspecialchars($root) ?>dashboard.php"                          class="nav-link"><i class="fas fa-chart-pie"></i> Overview</a>
            <a href="<?= htmlspecialchars($root) ?>PROJECT E/user/dashboard.php"            class="nav-link"><i class="fas fa-wallet"></i> Expense Tracker</a>
            <a href="<?= htmlspecialchars($base_s) ?>user/dashboard.php"                   class="nav-link active"><i class="fas fa-piggy-bank"></i> Savings Manager</a>
        </nav>
        <div class="sidebar-footer">
            <button class="btn-logout" onclick="doLogout()"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-bar">
            <button class="hamburger" onclick="toggleSidebar()" aria-label="Open menu"><i class="fas fa-bars"></i></button>
            <div class="top-bar-title">
                <h1>Savings Manager</h1>
                <span class="top-bar-sub">Grow your goals, one deposit at a time</span>
            </div>
            <button class="btn-add" id="addBucketBtn" onclick="openAddBucket()">
                <i class="fas fa-plus"></i> <span class="hide-mobile">New Goal</span>
            </button>
        </header>

        <!-- Summary Stats -->
        <section class="stats-grid" id="statsGrid" aria-label="Savings statistics">
            <div class="stat-card glass card-deposit">
                <div class="stat-icon"><i class="fas fa-arrow-down-to-line"></i></div>
                <div class="stat-body">
                    <span class="stat-label">Total Deposited</span>
                    <span class="stat-value" id="statDeposited"><?= $currency ?>0.00</span>
                </div>
            </div>
            <div class="stat-card glass card-withdraw">
                <div class="stat-icon"><i class="fas fa-arrow-up-from-line"></i></div>
                <div class="stat-body">
                    <span class="stat-label">Total Withdrawn</span>
                    <span class="stat-value" id="statWithdrawn"><?= $currency ?>0.00</span>
                </div>
            </div>
            <div class="stat-card glass card-net">
                <div class="stat-icon"><i class="fas fa-piggy-bank"></i></div>
                <div class="stat-body">
                    <span class="stat-label">Net Savings</span>
                    <span class="stat-value" id="statNet"><?= $currency ?>0.00</span>
                </div>
            </div>
            <div class="stat-card glass card-bucket">
                <div class="stat-icon"><i class="fas fa-bullseye"></i></div>
                <div class="stat-body">
                    <span class="stat-label">Active Goals</span>
                    <span class="stat-value" id="statBuckets">0</span>
                </div>
            </div>
        </section>

        <!-- Buckets Grid -->
        <section class="buckets-section">
            <div class="section-header">
                <h2><i class="fas fa-folder-open"></i> Your Savings Goals</h2>
            </div>
            <div id="bucketsGrid" class="buckets-grid">
                <div class="empty-state glass" id="emptyState">
                    <i class="fas fa-piggy-bank fa-3x"></i>
                    <h3>No savings goals yet</h3>
                    <p>Create your first goal to start tracking your savings.</p>
                    <button class="btn-add" onclick="openAddBucket()"><i class="fas fa-plus"></i> Create First Goal</button>
                </div>
            </div>
        </section>
    </main>

    <!-- Transaction Modal -->
    <div class="modal-overlay" id="txModal" style="display:none;">
        <div class="modal-box glass" id="txModalBox">
            <div class="modal-header">
                <h2 id="txModalTitle">Transactions</h2>
                <button class="modal-close" onclick="closeTxModal()" aria-label="Close"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-tabs">
                <button class="mtab active" id="tabDeposit"  onclick="setTxTab('deposit')">Deposit</button>
                <button class="mtab"        id="tabWithdraw" onclick="setTxTab('withdraw')">Withdraw</button>
                <button class="mtab"        id="tabHistory"  onclick="setTxTab('history')">History</button>
            </div>
            <div id="txFormWrap">
                <div class="tx-form-group">
                    <label for="txAmount">Amount (<?= $currency ?>)</label>
                    <input type="number" id="txAmount" class="tx-input" step="0.01" min="0.01" placeholder="0.00">
                </div>
                <div class="tx-form-group">
                    <label for="txDate">Date</label>
                    <input type="date" id="txDate" class="tx-input" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="tx-form-group">
                    <label for="txTime">Time</label>
                    <input type="time" id="txTime" class="tx-input" value="<?= date('H:i') ?>">
                </div>
                <div class="tx-form-group">
                    <label for="txDesc">Description (optional)</label>
                    <input type="text" id="txDesc" class="tx-input" placeholder="e.g. Monthly deposit" maxlength="255">
                </div>
                <button class="btn-tx-submit" id="txSubmitBtn" onclick="submitTransaction()">
                    <span id="txBtnText"><i class="fas fa-check"></i> Save Deposit</span>
                    <span id="txBtnSpinner" style="display:none;"><i class="fas fa-spinner fa-spin"></i></span>
                </button>
            </div>
            <div id="txHistoryWrap" style="display:none;">
                <div id="txHistoryList" class="tx-history"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const API_URL    = <?= json_encode($base_s . 'includes/api.php') ?>;
        const CSRF_TOKEN = <?= json_encode($csrf_token) ?>;
        const CURRENCY   = <?= json_encode($currency) ?>;
        const ROOT_URL   = <?= json_encode($root) ?>;
    </script>
    <script src="<?= htmlspecialchars($base_s) ?>assets/js/dashboard.js"></script>
</body>
</html>
