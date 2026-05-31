<?php
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
    <title>Savings Management | Money Management</title>
    <?php echo get_csrf_meta_tag(); ?>
    <?php echo get_logout_meta_tag('../../'); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/glassmorphism.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/sav.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        #appUI {
            display: flex;
            height: 100vh;
            height: 100dvh;
            width: 100%;
            overflow: hidden;
        }
        
        
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

            <ul class="category-tabs" id="navTabs">
                <li style="display:flex; align-items:center;">
                    <button class="category-tab active" id="tab-goals" style="flex:1;" onclick="loadSavView('goals.php', 'tab-goals')">
                        <i class="fas fa-bullseye"></i> <span style="flex:1; text-align:left;">My Goals</span>
                    </button>
                </li>
                <li style="display:flex; align-items:center;">
                    <button class="category-tab" id="tab-history" style="flex:1;" onclick="loadSavView('history.php', 'tab-history')">
                        <i class="fas fa-history"></i> <span style="flex:1; text-align:left;">Deposit History</span>
                    </button>
                </li>
            </ul>

            <button class="add-category-btn hide-mobile" onclick="addNewGoal()">
                <i class="fas fa-plus"></i> Create New Goal
            </button>

            <div class="sidebar-bottom">
                <button class="btn btn-ghost" id="tab-manage-goals" onclick="loadSavView('manage_goals.php', 'tab-manage-goals')"><i class="fas fa-tasks"></i> Manage Goals</button>
                <a href="../../dashboard/index.php" class="btn btn-ghost"><i class="fas fa-home"></i> Main Dashboard</a>
                <a href="<?php echo get_logout_url('../../'); ?>" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <div class="copyright" style="margin-top:1rem; font-size:0.8rem; color:var(--text-muted); text-align:center;">
                    &copy; <?php echo date("Y"); ?> Money Management
                </div>
            </div>
        </aside>

        <main class="main-content" id="main-content">
            <!-- Dynamic Content (goals.php / history.php) Injected Here via AJAX -->
            <div class="loading-container" style="margin:auto; display:block; text-align:center; padding-top:100px;">
                <i class="fas fa-circle-notch fa-spin fa-3x" style="color:var(--aurora-1);"></i>
                <h2 style="color: white; margin-top: 20px;">Loading Savings Data...</h2>
            </div>
        </main>
    </div>

    <script>
        const API_URL = '../api/api.php';
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    </script>
    <script src="../../assets/js/csrf.js?v=<?php echo time(); ?>"></script>
    <script src="../../assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/sav.js?v=<?php echo time(); ?>"></script>
</body>
</html>
