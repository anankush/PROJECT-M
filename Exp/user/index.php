<?php
// Exp/user/index.php
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/csrf.php';
require_once '../../includes/functions.php';
require_login();
set_security_headers();
$base = '../../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Management | Money Management</title>
    <?php echo get_csrf_meta_tag(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/glassmorphism.css">
    <link rel="stylesheet" href="../assets/css/exp.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.2.0/crypto-js.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
    <style>
        /* Hide appUI until data is loaded to prevent flash */
        #appUI { display: flex; width: 100%; }
        
        /* Sidebar layout styling overrides */
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
            <div class="user-profile">
                <h2 id="userNameDisplay">Welcome, User</h2>
            </div>

            <ul class="category-tabs" id="categoryTabs"></ul>

            <button class="add-category-btn" onclick="addCategory()">
                <i class="fas fa-plus"></i> Add New Section
            </button>

            <div class="sidebar-bottom">
                <button class="btn btn-ghost" onclick="openSettings()"><i class="fas fa-cog"></i> Settings</button>
                <button class="btn btn-ghost" onclick="changePasswordModal()"><i class="fas fa-key"></i> Change Password</button>
                <button class="btn btn-ghost" onclick="loadView('budgets.php')"><i class="fas fa-wallet"></i> Manage Budgets</button>
                <a href="../../dashboard/index.php" class="btn btn-ghost"><i class="fas fa-home"></i> Main Dashboard</a>
                <a href="../../auth/logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <div class="copyright" style="margin-top:1rem; font-size:0.8rem; color:var(--text-muted); text-align:center;">
                    &copy; <?php echo date("Y"); ?> Money Management
                </div>
            </div>
        </aside>

        <input type="file" id="importFile" accept=".encrypted" style="display:none;" onchange="handleImport(event)">

        <main class="main-content" id="main-content">
            <!-- Dynamic Content (view_expenses.php / budgets.php) Injected Here via AJAX -->
            <div class="loader" style="margin:auto; display:block; text-align:center; padding-top:100px;">
                <i class="fas fa-circle-notch fa-spin fa-3x" style="color:var(--aurora-1);"></i>
                <h2 style="color: white; margin-top: 20px;">Loading Expense Data...</h2>
            </div>
        </main>
    </div>

    <script>
        const API_URL = '../api/api.php';
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    </script>
    <script src="../../assets/js/csrf.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="../assets/js/exp.js"></script>
</body>
</html>
