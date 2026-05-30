<?php
// ============================================================
// PROJECT M: Central Dashboard
// Shows combined stats from PROJECT E + PROJECT S
// Separate views for admin (all users) and regular user
// ============================================================

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/db.php';

session_start_secure();
set_security_headers();
validate_domain_access();
check_session_timeout();

// Guard — must be logged in
if (empty($_SESSION['user_id'])) {
    header('Location: ' . ROOT_BASE_URL . 'login.php');
    exit;
}

$user_id   = (int)$_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'user';
$user_email = $_SESSION['user_email'] ?? '';
$csrf_token = generate_csrf_token();

// ── Fetch personal stats (PROJECT E — expenses) ───────────────
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM expenses WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_expense = (float)$stmt->fetchColumn();

// ── Fetch personal stats (PROJECT S — savings) ────────────────
$stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN transaction_type='deposit'  THEN amount ELSE 0 END),0) AS deposited,
        COALESCE(SUM(CASE WHEN transaction_type='withdraw' THEN amount ELSE 0 END),0) AS withdrawn
    FROM savings_logs WHERE user_id = ?
");
$stmt->execute([$user_id]);
$savings_row   = $stmt->fetch();
$total_savings = (float)$savings_row['deposited'] - (float)$savings_row['withdrawn'];

// ── Monthly chart data (last 6 months) ───────────────────────
$months_expense = [];
$months_savings = [];
for ($i = 5; $i >= 0; $i--) {
    $label = date('M Y', strtotime("-$i months"));
    $m     = date('Y-m', strtotime("-$i months"));

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id = ? AND DATE_FORMAT(entry_date,'%Y-%m') = ?");
    $stmt->execute([$user_id, $m]);
    $months_expense[] = ['label' => $label, 'value' => (float)$stmt->fetchColumn()];

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN transaction_type='deposit' THEN amount ELSE -amount END),0) FROM savings_logs WHERE user_id = ? AND DATE_FORMAT(transaction_date,'%Y-%m') = ?");
    $stmt->execute([$user_id, $m]);
    $months_savings[] = ['label' => $label, 'value' => (float)$stmt->fetchColumn()];
}

// ── Admin: all-user summary ────────────────────────────────────
$all_users_summary = [];
if ($user_role === 'admin') {
    $stmt = $pdo->query("
        SELECT u.id, u.email, u.role, u.created_at,
               COALESCE(SUM(e.amount),0) AS total_expense
        FROM users u
        LEFT JOIN expenses e ON e.user_id = u.id
        GROUP BY u.id ORDER BY u.created_at DESC
    ");
    $all_users_summary = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — PROJECT M</title>
    <meta name="description" content="Your unified financial overview — expenses and savings at a glance.">
    <?= get_csrf_meta_tag() ?>
    <meta name="base-url" content="<?= htmlspecialchars(ROOT_BASE_URL) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= ROOT_BASE_URL ?>assets/css/dashboard.css">
</head>
<body>
    <div class="aurora-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>
    <div class="noise-overlay"></div>

    <!-- Sidebar -->
    <div class="sidebar-overlay" onclick="toggleSidebar()" id="sidebarOverlay"></div>
    <aside class="sidebar" id="appSidebar">
        <div class="sidebar-header">
            <span class="sidebar-logo"><i class="fas fa-layer-group"></i> <strong>PROJECT M</strong></span>
            <button class="sidebar-close" onclick="toggleSidebar()" aria-label="Close menu"><i class="fas fa-times"></i></button>
        </div>
        <div class="sidebar-user">
            <div class="user-avatar"><i class="fas fa-user-circle"></i></div>
            <div>
                <p class="user-email"><?= htmlspecialchars($user_email) ?></p>
                <span class="role-badge role-<?= $user_role ?>"><?= ucfirst($user_role) ?></span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="<?= ROOT_BASE_URL ?>dashboard.php" class="nav-link active"><i class="fas fa-chart-pie"></i> Overview</a>
            <a href="<?= htmlspecialchars(ROOT_BASE_URL . 'PROJECT E/user/dashboard.php') ?>" class="nav-link"><i class="fas fa-wallet"></i> Expense Tracker</a>
            <a href="<?= htmlspecialchars(ROOT_BASE_URL . 'PROJECT S/user/dashboard.php') ?>" class="nav-link"><i class="fas fa-piggy-bank"></i> Savings Manager</a>
            <?php if ($user_role === 'admin'): ?>
            <a href="<?= htmlspecialchars(ROOT_BASE_URL . 'PROJECT E/admin/dashboard.php') ?>" class="nav-link"><i class="fas fa-shield-halved"></i> Admin Panel</a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <button class="btn-logout" id="logoutBtn" onclick="doLogout()"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="top-bar">
            <button class="hamburger" onclick="toggleSidebar()" aria-label="Open menu"><i class="fas fa-bars"></i></button>
            <div class="top-bar-title">
                <h1>Financial Overview</h1>
                <span class="top-bar-sub"><?= date('F j, Y') ?></span>
            </div>
        </header>

        <!-- Stats cards -->
        <section class="stats-grid" aria-label="Financial statistics">
            <div class="stat-card glass card-expense">
                <div class="stat-icon"><i class="fas fa-arrow-trend-up"></i></div>
                <div class="stat-body">
                    <span class="stat-label">Total Expenses</span>
                    <span class="stat-value" id="totalExpense">₹<?= number_format($total_expense, 2) ?></span>
                </div>
            </div>
            <div class="stat-card glass card-savings">
                <div class="stat-icon"><i class="fas fa-piggy-bank"></i></div>
                <div class="stat-body">
                    <span class="stat-label">Net Savings</span>
                    <span class="stat-value" id="totalSavings">₹<?= number_format($total_savings, 2) ?></span>
                </div>
            </div>
            <div class="stat-card glass card-balance">
                <div class="stat-icon"><i class="fas fa-scale-balanced"></i></div>
                <div class="stat-body">
                    <span class="stat-label">Balance Ratio</span>
                    <span class="stat-value"><?= $total_expense > 0 ? number_format(($total_savings / ($total_savings + $total_expense)) * 100, 1) : '100' ?>%</span>
                </div>
            </div>
        </section>

        <!-- Module shortcuts -->
        <section class="module-cards" aria-label="Module navigation">
            <a href="<?= htmlspecialchars(ROOT_BASE_URL . 'PROJECT E/user/dashboard.php') ?>" class="module-card glass" id="expenseModuleBtn">
                <div class="module-icon expense-icon"><i class="fas fa-wallet"></i></div>
                <div class="module-info">
                    <h2>Expense Tracker</h2>
                    <p>Log and manage your spending across categories</p>
                </div>
                <i class="fas fa-chevron-right module-arrow"></i>
            </a>
            <a href="<?= htmlspecialchars(ROOT_BASE_URL . 'PROJECT S/user/dashboard.php') ?>" class="module-card glass" id="savingsModuleBtn">
                <div class="module-icon savings-icon"><i class="fas fa-piggy-bank"></i></div>
                <div class="module-info">
                    <h2>Savings Manager</h2>
                    <p>Set goals, track deposits and withdrawals</p>
                </div>
                <i class="fas fa-chevron-right module-arrow"></i>
            </a>
        </section>

        <!-- Chart -->
        <section class="chart-section glass" aria-label="6-month financial chart">
            <div class="chart-header">
                <h2><i class="fas fa-chart-line"></i> Last 6 Months</h2>
            </div>
            <div class="chart-wrap">
                <canvas id="financeChart" aria-label="Financial chart" role="img"></canvas>
            </div>
        </section>

        <?php if ($user_role === 'admin' && !empty($all_users_summary)): ?>
        <!-- Admin: all users table -->
        <section class="admin-section glass" aria-label="All users summary">
            <div class="chart-header">
                <h2><i class="fas fa-users"></i> All Users Summary</h2>
            </div>
            <div class="table-wrap">
                <table class="admin-table" id="adminUsersTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Total Expenses</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_users_summary as $i => $u): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><span class="role-badge role-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                            <td>₹<?= number_format((float)$u['total_expense'], 2) ?></td>
                            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Chart data from PHP — no inline logic, just data injection
        const chartLabels   = <?= json_encode(array_column($months_expense, 'label')) ?>;
        const expenseData   = <?= json_encode(array_column($months_expense, 'value')) ?>;
        const savingsData   = <?= json_encode(array_column($months_savings, 'value')) ?>;
        const CSRF_TOKEN    = <?= json_encode($csrf_token) ?>;
        const ROOT_BASE_URL = <?= json_encode(ROOT_BASE_URL) ?>;
    </script>
    <script src="<?= ROOT_BASE_URL ?>assets/js/dashboard.js"></script>
</body>
</html>
