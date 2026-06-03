<?php

require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/csrf.php';
require_once '../includes/functions.php';


if (empty($_SESSION['admin_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/admin_login.php');
    exit;
}

set_security_headers();
$base = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="path-depth" content="1">
    <title>Admin Dashboard | Money Management</title>
    <?php echo get_csrf_meta_tag(); ?>
    <?php echo get_logout_meta_tag('../'); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/glassmorphism.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        #appUI { display: flex; width: 100%; height: 100vh; height: 100dvh; overflow: hidden; }
        
        .sidebar-bottom {
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid var(--glass-border);
        }
        .sidebar-bottom .btn {
            width: 100%;
            text-align: left;
            margin-bottom: 0.5rem;
            justify-content: flex-start;
        }

        
        .admin-view {
            display: none;
            animation: fadeIn 0.4s ease both;
        }
        .admin-view.active {
            display: block;
        }

        
        .admin-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .admin-stat-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            transition: all 0.3s ease;
        }
        .admin-stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(139, 92, 246, 0.15);
            border-color: rgba(139, 92, 246, 0.4);
        }
        .stat-card-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        
        .admin-charts-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .chart-wrapper {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            padding: 1.5rem;
            height: 400px;
        }
        .chart-wrapper h3 {
            font-size: 1.05rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }
        .chart-container-inner {
            height: 320px;
            width: 100%;
            position: relative;
        }

        
        .control-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .search-input-wrapper {
            position: relative;
            max-width: 320px;
            width: 100%;
        }
        .search-input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }
        .search-input-wrapper input {
            padding-left: 40px;
        }

        
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-status.active {
            background: rgba(16, 185, 129, 0.15);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .badge-status.blocked {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        
        .btn-table-action {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            border-radius: 8px;
            font-weight: 600;
        }
        
        
        .log-details {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-family: var(--font-mono);
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            .main-content {
                padding-bottom: 5rem !important;
            }
            .admin-charts-grid {
                gap: 1rem;
                margin-bottom: 1.5rem;
            }
            .chart-wrapper {
                padding: 1rem;
                height: 320px;
                border-radius: var(--radius-lg);
            }
            .chart-wrapper h3 {
                font-size: 0.95rem;
                margin-bottom: 0.75rem;
            }
            .chart-container-inner {
                height: 240px;
            }
            .header-controls {
                display: flex;
                justify-content: flex-end;
                width: 100%;
            }
            .hide-mobile {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="aurora-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>
    <div class="noise-overlay"></div>

    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <div id="appUI">
        <aside class="sidebar" id="appSidebar">
            <div class="user-profile" style="border-bottom: 1px solid rgba(239, 68, 68, 0.2);">
                <h2 style="display:flex; align-items:center; gap:8px;">
                    <i class="fas fa-user-shield" style="color:#ef4444;"></i>
                    <span>Admin Portal</span>
                </h2>
                <span class="text-muted" id="adminEmailDisplay" style="font-size:0.8rem; display:block; margin-top:2px;">Loading session...</span>
            </div>

            <ul class="category-tabs" id="adminTabs">
                <li>
                    <button class="category-tab active" id="tab-overview" onclick="switchTab('overview')">
                        <i class="fas fa-chart-pie"></i> System Overview
                    </button>
                </li>
                <li>
                    <button class="category-tab" id="tab-users" onclick="switchTab('users')">
                        <i class="fas fa-users"></i> User Directory
                    </button>
                </li>
                <li>
                    <button class="category-tab" id="tab-logs" onclick="switchTab('logs')">
                        <i class="fas fa-history"></i> Security Logs
                    </button>
                </li>
                <li>
                    <button class="category-tab" id="tab-shield" onclick="switchTab('shield')">
                        <i class="fas fa-shield-alt"></i> Email Shield
                    </button>
                </li>
            </ul>

            <div class="sidebar-bottom">
                <a href="#" class="btn btn-ghost" onclick="Swal.fire({icon:'info',title:'Coming Soon',text:'Settings panel is under construction.',background:'var(--glass-bg)',color:'var(--text-primary)',confirmButtonColor:'var(--aurora-1)'}); return false;"><i class="fas fa-cog"></i> Settings</a>
                <a href="<?php echo get_logout_url('../'); ?>" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <div class="copyright" style="margin-top:1rem; font-size:0.75rem; color:var(--text-muted); text-align:center;">
                    &copy; <?php echo date("Y"); ?> Money Management
                </div>
            </div>
        </aside>

        <main class="main-content" id="main-content">
            <!-- 1. VIEW: SYSTEM OVERVIEW -->
            <section class="admin-view active" id="view-overview">
                <div class="dashboard-header">
                    <div class="header-left">
                        <button class="mobile-menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                        <h1>System Overview</h1>
                    </div>
                    <div class="header-controls">
                        <button class="btn btn-ghost refresh-btn" onclick="refreshOverview()">
                            <i class="fas fa-sync-alt refresh-icon"></i> <span>Refresh Info</span>
                        </button>
                    </div>
                </div>

                <!-- Stats Summary Row -->
                <div class="admin-stats-grid">
                    <div class="admin-stat-card">
                        <div class="stat-card-icon" style="background:rgba(139, 92, 246, 0.15); color:var(--aurora-1);">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <span class="metric-label">Total Users</span>
                            <div class="metric-value" id="stat-total-users"><i class="fas fa-circle-notch fa-spin fa-xs"></i></div>
                        </div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="stat-card-icon" style="background:rgba(16, 185, 129, 0.15); color:var(--success);">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div>
                            <span class="metric-label">Active (24h)</span>
                            <div class="metric-value" id="stat-active-users"><i class="fas fa-circle-notch fa-spin fa-xs"></i></div>
                        </div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="stat-card-icon" style="background:rgba(239, 68, 68, 0.15); color:var(--danger);">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div>
                            <span class="metric-label">Total Spent</span>
                            <div class="metric-value" id="stat-system-spent"><i class="fas fa-circle-notch fa-spin fa-xs"></i></div>
                        </div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="stat-card-icon" style="background:rgba(16, 185, 129, 0.15); color:var(--success);">
                            <i class="fas fa-piggy-bank"></i>
                        </div>
                        <div>
                            <span class="metric-label">Total Saved</span>
                            <div class="metric-value" id="stat-system-saved"><i class="fas fa-circle-notch fa-spin fa-xs"></i></div>
                        </div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="stat-card-icon" style="background:rgba(245, 158, 11, 0.15); color:#f59e0b;">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div>
                            <span class="metric-label">Failed Logins (24h)</span>
                            <div class="metric-value" id="stat-failed-logins"><i class="fas fa-circle-notch fa-spin fa-xs"></i></div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="admin-charts-grid">
                    <div class="chart-wrapper">
                        <h3>User Registrations & Logins Trend</h3>
                        <div class="chart-container-inner">
                            <canvas id="registrationsChart"></canvas>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 2. VIEW: USER DIRECTORY -->
            <section class="admin-view" id="view-users">
                <div class="dashboard-header">
                    <div class="header-left">
                        <button class="mobile-menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                        <h1>User Directory</h1>
                    </div>
                </div>

                <div class="control-row" style="display: flex; flex-direction: row; flex-wrap: nowrap; gap: 8px; width: 100%;">
                    <div class="search-input-wrapper" style="flex: 1; max-width: none;">
                        <i class="fas fa-search"></i>
                        <input type="text" id="userSearchInput" placeholder="Search users by email..." onkeyup="filterUsersTable()">
                    </div>
                    <button class="btn btn-ghost" onclick="fetchUsers()" style="flex-shrink: 0; padding: 0.6rem 1rem;">
                        <i class="fas fa-sync-alt"></i> <span class="hide-mobile">Refresh Users</span>
                    </button>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Email</th>
                                <th>Registered At</th>
                                <th>Last Active At</th>
                                <th>Sections</th>
                                <th>Goals</th>
                                <th>Expenses</th>
                                <th>Status</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <tr><td colspan="9" style="text-align:center; padding:3rem;"><i class="fas fa-circle-notch fa-spin fa-2x"></i></td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- 3. VIEW: SECURITY LOGS -->
            <section class="admin-view" id="view-logs">
                <div class="dashboard-header">
                    <div class="header-left">
                        <button class="mobile-menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                        <h1>Security Logs</h1>
                    </div>
                </div>

                <div class="control-row" style="display: flex; flex-direction: row; flex-wrap: nowrap; gap: 8px; width: 100%;">
                    <div class="search-input-wrapper" style="flex: 1; max-width: none;">
                        <i class="fas fa-search"></i>
                        <input type="text" id="logSearchInput" placeholder="Filter logs by email or action..." onkeyup="filterLogsTable()">
                    </div>
                    <button class="btn btn-ghost" onclick="fetchLogs()" style="flex-shrink: 0; padding: 0.6rem 1rem;">
                        <i class="fas fa-sync-alt"></i> <span class="hide-mobile">Refresh Logs</span>
                    </button>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Email</th>
                                <th>Action Event</th>
                                <th>IP Address</th>
                                <th>Browser User-Agent</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody">
                            <tr><td colspan="5" style="text-align:center; padding:3rem;"><i class="fas fa-circle-notch fa-spin fa-2x"></i></td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- 4. VIEW: EMAIL SHIELD -->
            <section class="admin-view" id="view-shield">
                <div class="dashboard-header">
                    <div class="header-left">
                        <button class="mobile-menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                        <h1>Email Security Shield</h1>
                    </div>
                </div>

                <div class="admin-stats-grid">
                    <div class="admin-stat-card">
                        <div class="stat-card-icon" style="background:rgba(139, 92, 246, 0.15); color:var(--aurora-1);">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>
                            <span class="metric-label">Total Blocked Domains</span>
                            <div class="metric-value" id="shield-total-domains"><i class="fas fa-circle-notch fa-spin fa-xs"></i></div>
                        </div>
                    </div>
                    <div class="admin-stat-card" style="grid-column: span 2;">
                        <div class="stat-card-icon" style="background:rgba(16, 185, 129, 0.15); color:var(--success);">
                            <i class="fas fa-history"></i>
                        </div>
                        <div style="width: calc(100% - 60px);">
                            <span class="metric-label">Last Blocklist Sync</span>
                            <div class="metric-value font-mono" id="shield-last-sync" style="font-size:1.15rem; margin-top:4px;"><i class="fas fa-circle-notch fa-spin fa-xs"></i></div>
                        </div>
                    </div>
                </div>

                <div class="control-row" style="margin-bottom: 2rem;">
                    <button class="btn btn-primary" onclick="syncLiveBlocklist()" style="background: linear-gradient(135deg, var(--aurora-1), var(--aurora-deep)); display: flex; align-items: center; gap: 8px; padding: 0.75rem 1.5rem; font-weight: 600;">
                        <i class="fas fa-sync-alt" id="syncShieldIcon"></i> Sync Live Blocklist
                    </button>
                </div>

                <div class="glass-card" style="background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: var(--radius-xl); padding: 2rem; margin-top: 1rem;">
                    <h3 style="font-family: var(--font-display); font-size: 1.2rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-primary);">
                        <i class="fas fa-search-location" style="color: var(--aurora-1); margin-right: 8px;"></i> Live Domain Lookup
                    </h3>
                    <p class="text-secondary" style="font-size: 0.85rem; margin-bottom: 1.5rem;">
                        Verify whether a particular email domain is currently blacklisted and blocked by the Security Shield.
                    </p>
                    <div style="display: flex; gap: 10px; width: 100%; flex-wrap: wrap;">
                        <input type="text" id="shieldDomainInput" placeholder="e.g. yopmail.com" class="theme-input-select" style="flex: 1; min-width: 250px; height: 44px; padding: 0.75rem 1rem; border-radius: 8px;">
                        <button class="btn btn-primary" onclick="checkDomainShield()" style="height: 44px; display: inline-flex; align-items: center; justify-content: center; padding: 0 1.5rem; font-weight: 600;">
                            <i class="fas fa-search" style="margin-right: 6px;"></i> Lookup Domain
                        </button>
                    </div>
                    <div id="shieldCheckResult" style="margin-top: 1.5rem; display: none; font-weight: 600; padding: 1rem; border-radius: 8px; font-size: 0.9rem;"></div>
                </div>
            </section>
        </main>
    </div>

    <script src="../assets/js/csrf.js?v=<?php echo time(); ?>"></script>
    <script>
        const API_URL = 'api/api.php';
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        let usersData = [];
        let logsData = [];
        let regChartInstance = null;
        let catChartInstance = null;

        document.addEventListener('DOMContentLoaded', () => {
            checkSession();
        });

        async function checkSession() {
            try {
                const res = await fetch(`${API_URL}?action=check_session`);
                const data = await res.json();
                if (data.is_admin) {
                    document.getElementById('adminEmailDisplay').innerText = data.email;
                    refreshOverview();
                } else {
                    window.location.href = '../auth/admin_login.php';
                }
            } catch (e) {
                console.error(e);
            }
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('appSidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            if (sidebar) sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('open');
        }

        function switchTab(tabId) {
            
            document.querySelectorAll('#adminTabs .category-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            document.getElementById(`tab-${tabId}`).classList.add('active');

            
            document.querySelectorAll('.admin-view').forEach(view => {
                view.classList.remove('active');
            });
            document.getElementById(`view-${tabId}`).classList.add('active');

            if (window.innerWidth <= 768) {
                toggleSidebar();
            }

            
            if (tabId === 'overview') {
                refreshOverview();
            } else if (tabId === 'users') {
                fetchUsers();
            } else if (tabId === 'logs') {
                fetchLogs();
            } else if (tabId === 'shield') {
                fetchShieldStatus();
            }
        }

        async function refreshOverview() {
            const spin = document.querySelector('.refresh-btn .refresh-icon');
            if (spin) spin.classList.add('spin');
            
            await fetchStats();
            await fetchAnalytics();

            setTimeout(() => {
                if (spin) spin.classList.remove('spin');
            }, 700);
        }

        async function fetchStats() {
            try {
                const res = await fetch(`${API_URL}?action=get_system_stats`);
                const data = await res.json();
                if (data.status === 'success') {
                    const stats = data.data;
                    document.getElementById('stat-total-users').innerText = stats.total_users;
                    document.getElementById('stat-active-users').innerText = stats.active_users;
                    document.getElementById('stat-failed-logins').innerText = stats.failed_logins;
                    
                    const spentFormatted = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(stats.system_spent);
                    const savedFormatted = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(stats.system_saved);
                    document.getElementById('stat-system-spent').innerText = spentFormatted;
                    document.getElementById('stat-system-saved').innerText = savedFormatted;
                }
            } catch (e) {
                console.error("Failed to load stats", e);
            }
        }

        async function fetchAnalytics() {
            try {
                const res = await fetch(`${API_URL}?action=get_analytics`);
                const data = await res.json();
                if (data.status === 'success') {
                    renderCharts(data.data);
                }
            } catch (e) {
                console.error("Failed to fetch analytics", e);
            }
        }

        function renderCharts(data) {
            
            const regData = data.registration_trend || [];
            const logData = data.login_trend || [];
            
            const allMonths = Array.from(new Set([
                ...regData.map(d => d.month),
                ...logData.map(d => d.month)
            ])).sort();

            const regCounts = allMonths.map(m => {
                const found = regData.find(d => d.month === m);
                return found ? found.count : 0;
            });

            const logCounts = allMonths.map(m => {
                const found = logData.find(d => d.month === m);
                return found ? found.count : 0;
            });

            const regCtx = document.getElementById('registrationsChart').getContext('2d');

            if (regChartInstance) regChartInstance.destroy();
            regChartInstance = new Chart(regCtx, {
                type: 'line',
                data: {
                    labels: allMonths.length ? allMonths : ['No Data'],
                    datasets: [
                        {
                            label: 'New Registrations',
                            data: regCounts,
                            borderColor: '#8b5cf6',
                            backgroundColor: 'rgba(139, 92, 246, 0.1)',
                            borderWidth: 2.5,
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'User Logins',
                            data: logCounts,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.05)',
                            borderWidth: 2.5,
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            display: true,
                            labels: {
                                color: 'rgba(255, 255, 255, 0.8)',
                                font: {
                                    size: 11
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            grid: { color: 'rgba(255, 255, 255, 0.05)' },
                            ticks: { color: 'rgba(255, 255, 255, 0.6)', stepSize: 1 }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: 'rgba(255, 255, 255, 0.6)' }
                        }
                    }
                }
            });
        }

        async function fetchUsers() {
            const body = document.getElementById('usersTableBody');
            body.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:3rem;"><i class="fas fa-circle-notch fa-spin fa-2x"></i></td></tr>';

            try {
                const res = await fetch(`${API_URL}?action=get_users`);
                const data = await res.json();
                if (data.status === 'success') {
                    usersData = data.data;
                    renderUsersTable(usersData);
                }
            } catch (e) {
                console.error("Failed to load users", e);
                body.innerHTML = '<tr><td colspan="9" style="text-align:center; color:var(--danger)">Failed to fetch user directory.</td></tr>';
            }
        }

        function renderUsersTable(users) {
            const body = document.getElementById('usersTableBody');
            body.innerHTML = '';

            if (users.length === 0) {
                body.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:2rem; color:var(--text-muted)">No registered users found.</td></tr>';
                return;
            }

            users.forEach(user => {
                const badgeClass = user.status === 'blocked' ? 'blocked' : 'active';
                const actionText = user.status === 'blocked' ? 'Unblock' : 'Block';
                const actionBtnClass = user.status === 'blocked' ? 'btn-ghost' : 'btn-danger';
                const actionValue = user.status !== 'blocked'; 

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="font-mono">#${user.id}</td>
                    <td style="font-weight: 500;">${escapeHtml(user.email)}</td>
                    <td>${user.created_at}</td>
                    <td>${user.last_active_at || 'Never'}</td>
                    <td>${user.total_categories}</td>
                    <td>${user.total_goals}</td>
                    <td>${user.total_expenses}</td>
                    <td><span class="badge-status ${badgeClass}">${user.status}</span></td>
                    <td style="text-align:right;">
                        <button class="btn ${actionBtnClass} btn-table-action" onclick="confirmToggleUser(${user.id}, '${escapeHtml(user.email)}', ${actionValue})">
                            ${actionText}
                        </button>
                    </td>
                `;
                body.appendChild(tr);
            });
        }

        function filterUsersTable() {
            const query = document.getElementById('userSearchInput').value.toLowerCase().trim();
            const filtered = usersData.filter(user => user.email.toLowerCase().includes(query));
            renderUsersTable(filtered);
        }

        function confirmToggleUser(userId, email, isBlock) {
            const action = isBlock ? 'Block' : 'Unblock';
            Swal.fire({
                title: `${action} User?`,
                text: `Are you sure you want to ${action.toLowerCase()} user ${email}?`,
                icon: isBlock ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonText: `Yes, ${action}`,
                cancelButtonText: 'Cancel',
                customClass: {
                    confirmButton: isBlock ? 'btn btn-danger' : 'btn btn-primary',
                    cancelButton: 'btn btn-ghost'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    executeToggleUser(userId, isBlock);
                }
            });
        }

        async function executeToggleUser(userId, isBlock) {
            try {
                const res = await fetch(`${API_URL}?action=toggle_user_status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': CSRF_TOKEN
                    },
                    body: JSON.stringify({ user_id: userId, block: isBlock })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    fetchUsers();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            } catch (e) {
                console.error("Toggle user failed", e);
            }
        }

        async function fetchLogs() {
            const body = document.getElementById('logsTableBody');
            body.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:3rem;"><i class="fas fa-circle-notch fa-spin fa-2x"></i></td></tr>';

            try {
                const res = await fetch(`${API_URL}?action=get_security_logs`);
                const data = await res.json();
                if (data.status === 'success') {
                    logsData = data.data;
                    renderLogsTable(logsData);
                }
            } catch (e) {
                console.error("Failed to load logs", e);
                body.innerHTML = '<tr><td colspan="5" style="text-align:center; color:var(--danger)">Failed to fetch security logs.</td></tr>';
            }
        }

        function renderLogsTable(logs) {
            const body = document.getElementById('logsTableBody');
            body.innerHTML = '';

            if (logs.length === 0) {
                body.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:2rem; color:var(--text-muted)">No security logs recorded.</td></tr>';
                return;
            }

            logs.forEach(log => {
                
                let actionBadgeColor = 'rgba(255, 255, 255, 0.05)';
                let actionColor = 'var(--text-secondary)';
                if (log.action.includes('success')) {
                    actionBadgeColor = 'rgba(16, 185, 129, 0.1)';
                    actionColor = 'var(--success)';
                } else if (log.action.includes('failed') || log.action.includes('blocked')) {
                    actionBadgeColor = 'rgba(239, 68, 68, 0.1)';
                    actionColor = 'var(--danger)';
                }

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="font-size:0.85rem; color:var(--text-muted);">${log.created_at}</td>
                    <td style="font-weight: 500;">${escapeHtml(log.email)}</td>
                    <td>
                        <span class="badge-status" style="background:${actionBadgeColor}; color:${actionColor}; text-transform:none;">
                            ${log.action}
                        </span>
                    </td>
                    <td class="font-mono" style="font-size:0.85rem;">${log.ip_address}</td>
                    <td class="log-details" title="${escapeHtml(log.user_agent)}">${escapeHtml(log.user_agent)}</td>
                `;
                body.appendChild(tr);
            });
        }

        function filterLogsTable() {
            const query = document.getElementById('logSearchInput').value.toLowerCase().trim();
            const filtered = logsData.filter(log => 
                log.email.toLowerCase().includes(query) || 
                log.action.toLowerCase().includes(query) ||
                log.ip_address.includes(query)
            );
            renderLogsTable(filtered);
        }

        function escapeHtml(unsafe) {
            if (!unsafe) return '';
            return String(unsafe)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        async function fetchShieldStatus() {
            try {
                const res = await fetch(`${API_URL}?action=get_blocklist_status`);
                const data = await res.json();
                if (data.status === 'success') {
                    document.getElementById('shield-total-domains').innerText = new Intl.NumberFormat().format(data.count);
                    document.getElementById('shield-last-sync').innerText = data.last_sync;
                }
            } catch (e) {
                console.error("Failed to load Shield status", e);
            }
        }

        async function syncLiveBlocklist() {
            const syncIcon = document.getElementById('syncShieldIcon');
            if (syncIcon) syncIcon.classList.add('fa-spin');
            
            Swal.fire({
                title: 'Syncing Live Blocklist...',
                text: 'Downloading latest disposable domains from raw.githubusercontent.com and updating cache...',
                background: 'var(--glass-bg)',
                color: 'var(--text-primary)',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                const res = await fetch(`${API_URL}?action=sync_email_blocklist`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': CSRF_TOKEN
                    }
                });
                const data = await res.json();
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sync Completed!',
                        text: `${data.message} Total domains in blocklist: ${new Intl.NumberFormat().format(data.count)}`,
                        background: 'var(--glass-bg)',
                        color: 'var(--text-primary)',
                        confirmButtonColor: 'var(--aurora-1)'
                    });
                    document.getElementById('shield-total-domains').innerText = new Intl.NumberFormat().format(data.count);
                    document.getElementById('shield-last-sync').innerText = data.last_sync;
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Sync Failed',
                        text: data.message,
                        background: 'var(--glass-bg)',
                        color: 'var(--text-primary)',
                        confirmButtonColor: 'var(--aurora-1)'
                    });
                }
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'An unexpected connection error occurred while syncing.',
                    background: 'var(--glass-bg)',
                    color: 'var(--text-primary)',
                    confirmButtonColor: 'var(--aurora-1)'
                });
            } finally {
                if (syncIcon) syncIcon.classList.remove('fa-spin');
            }
        }

        async function checkDomainShield() {
            const domainInput = document.getElementById('shieldDomainInput').value.trim().toLowerCase();
            const resultDiv = document.getElementById('shieldCheckResult');
            if (!domainInput) return;
            
            resultDiv.style.display = 'block';
            resultDiv.className = '';
            resultDiv.style.background = 'rgba(255, 255, 255, 0.05)';
            resultDiv.style.color = 'var(--text-secondary)';
            resultDiv.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Searching in live blocklist...';
            
            try {
                // Check via Kickbox API first for real-time
                const response = await fetch(`https://open.kickbox.com/v1/disposable/${domainInput}`);
                if (response.ok) {
                    const data = await response.json();
                    if (data && typeof data.disposable !== 'undefined') {
                        if (data.disposable) {
                            resultDiv.style.background = 'rgba(239, 68, 68, 0.15)';
                            resultDiv.style.color = 'var(--danger)';
                            resultDiv.style.border = '1px solid rgba(239, 68, 68, 0.3)';
                            resultDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> DANGER: <strong>${escapeHtml(domainInput)}</strong> is flagged as a DISPOSABLE/TEMP mail domain. Registers will be blocked!`;
                            return;
                        }
                    }
                }
                
                // Fallback: search in local/GitHub cached blocklist
                let blocklist = localStorage.getItem('disposable_email_blocklist');
                if (!blocklist) {
                    const listResponse = await fetch('https://raw.githubusercontent.com/disposable-email-domains/disposable-email-domains/master/disposable_email_blocklist.conf');
                    if (listResponse.ok) {
                        blocklist = await listResponse.text();
                        localStorage.setItem('disposable_email_blocklist', blocklist);
                        localStorage.setItem('disposable_email_blocklist_time', Date.now().toString());
                    }
                }
                
                if (blocklist) {
                    const domains = blocklist.split('\n').map(d => d.trim().toLowerCase()).filter(Boolean);
                    if (domains.includes(domainInput)) {
                        resultDiv.style.background = 'rgba(239, 68, 68, 0.15)';
                        resultDiv.style.color = 'var(--danger)';
                        resultDiv.style.border = '1px solid rgba(239, 68, 68, 0.3)';
                        resultDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> DANGER: <strong>${escapeHtml(domainInput)}</strong> is found in the blocklist. Registers will be blocked!`;
                        return;
                    }
                }
                
                resultDiv.style.background = 'rgba(16, 185, 129, 0.15)';
                resultDiv.style.color = 'var(--success)';
                resultDiv.style.border = '1px solid rgba(16, 185, 129, 0.3)';
                resultDiv.innerHTML = `<i class="fas fa-check-circle"></i> SAFE: <strong>${escapeHtml(domainInput)}</strong> is not in the blocklist. Registers allowed!`;
                
            } catch (e) {
                resultDiv.style.background = 'rgba(239, 68, 68, 0.15)';
                resultDiv.style.color = 'var(--danger)';
                resultDiv.innerHTML = '<i class="fas fa-times-circle"></i> Failed to query blocklist. Try again later.';
            }
        }
    </script>
</body>
</html>
