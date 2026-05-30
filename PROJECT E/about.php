<?php
$pageTitle = 'How To Use | Expense Management';
require_once 'includes/header_root.php';
$base = BASE_URL;
?>

<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/global.css">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/index.css">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/about.css">
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
            <a href="<?php echo $base; ?>" class="nav-btn nav-btn-ghost"><i class="fas fa-home"></i> <span>Home</span></a>
            <a href="https://github.com/anankush" target="_blank" class="nav-btn nav-btn-ghost" title="GitHub"><i class="fab fa-github"></i></a>
        </div>
    </nav>

    <!-- HERO -->
    <section class="about-hero">
        <div class="hero-badge"><i class="fas fa-book-open"></i>&nbsp;User Guide</div>
        <h1>How To Use<br>Expense Management</h1>
        <p>A complete step-by-step walkthrough — from registration to tracking your expenses like a pro.</p>
    </section>

    <!-- STEPS -->
    <div class="steps-container">

        <!-- Role overview -->
        <div class="steps-title">
            <h2>Two Portals, One System</h2>
            <p>Expense Management has two separate access points for Members and Administrators</p>
        </div>
        <div class="roles-grid">
            <div class="role-card member">
                <div class="role-icon"><i class="fas fa-users"></i></div>
                <h3>Member Portal</h3>
                <ul>
                    <li><i class="fas fa-circle"></i> Register with email & password</li>
                    <li><i class="fas fa-circle"></i> Create custom expense sections</li>
                    <li><i class="fas fa-circle"></i> Add, edit & delete records</li>
                    <li><i class="fas fa-circle"></i> Filter by month & year</li>
                    <li><i class="fas fa-circle"></i> Set section-wise budgets</li>
                    <li><i class="fas fa-circle"></i> Export & import JSON backup</li>
                </ul>
            </div>
            <div class="role-card admin">
                <div class="role-icon"><i class="fas fa-user-shield"></i></div>
                <h3>Admin Portal</h3>
                <ul>
                    <li><i class="fas fa-circle"></i> View all registered members</li>
                    <li><i class="fas fa-circle"></i> Monitor total platform expenditure</li>
                    <li><i class="fas fa-circle"></i> Filter stats by month</li>
                    <li><i class="fas fa-circle"></i> View individual user expenses</li>
                    <li><i class="fas fa-circle"></i> Delete member accounts</li>
                    <li><i class="fas fa-circle"></i> Manage admin password</li>
                </ul>
            </div>
        </div>

        <div class="section-divider"><span><i class="fas fa-user"></i> &nbsp;Member Guide</span></div>

        <div class="step-card">
            <div class="step-number">1</div>
            <div class="step-body">
                <h3>Register Your Account</h3>
                <p>Go to the <strong>Member Portal</strong> from the homepage. Click <em>"New here? Register"</em> on the login page. Enter a valid email address and a strong password (min 8 characters, 1 uppercase, 1 number, 1 special character). Click <strong>Register</strong> to create your account instantly.</p>
                <span class="tip"><i class="fas fa-lightbulb"></i> Password must meet complexity requirements shown in the form</span>
            </div>
        </div>

        <div class="step-card">
            <div class="step-number">2</div>
            <div class="step-body">
                <h3>Login to Your Dashboard</h3>
                <p>Enter your registered email and password on the login page. Click <strong>Login</strong>. Your personal dashboard will load with your expense sections in the left sidebar. If you forget your password, click <em>"Forgot Password?"</em> to receive an OTP on your email for recovery.</p>
                <span class="tip"><i class="fas fa-shield-alt"></i> Session auto-expires after 15 minutes of inactivity for security</span>
            </div>
        </div>

        <div class="step-card">
            <div class="step-number">3</div>
            <div class="step-body">
                <h3>Create Expense Sections</h3>
                <p>In the left sidebar, click <strong>"+ Add New Section"</strong> and give it a name (e.g., <em>Food</em>, <em>Rent</em>, <em>Utilities</em>). You can create unlimited sections. To <strong>rename</strong> a section, select it and click the edit icon next to the section title. To <strong>delete</strong> it, click the trash icon.</p>
                <span class="tip"><i class="fas fa-folder-plus"></i> Create sections for different expense categories to organize better</span>
            </div>
        </div>

        <div class="step-card">
            <div class="step-number">4</div>
            <div class="step-body">
                <h3>Add Expense Records</h3>
                <p>Select a section from the sidebar, then click <strong>"+ Add Record"</strong> in the top-right. Fill in the <em>Date</em>, <em>Amount</em>, and <em>Description</em>. You can also add <strong>Custom Fields</strong> (like "Paid By", "Invoice No.") for richer records. Click <strong>Save</strong> to add the entry.</p>
                <span class="tip"><i class="fas fa-plus-circle"></i> Custom fields are remembered and reused automatically for that section</span>
            </div>
        </div>

        <div class="step-card">
            <div class="step-number">5</div>
            <div class="step-body">
                <h3>Filter by Month & Set Budgets</h3>
                <p>Use the <strong>Month Picker</strong> in the header to filter all expenses for a specific month and year. Each section can have its own monthly budget — click the edit icon on the <em>Section Budget</em> card. The dashboard shows real-time <strong>Budget vs Expenditure vs Remaining</strong> balance.</p>
                <span class="tip"><i class="fas fa-calendar-alt"></i> Budgets can differ each month — great for variable income tracking</span>
            </div>
        </div>

        <div class="step-card">
            <div class="step-number">6</div>
            <div class="step-body">
                <h3>Edit & Delete Records</h3>
                <p>Each record row has <strong>Edit</strong> and <strong>Delete</strong> buttons on the right side. Clicking Edit opens a popup pre-filled with the existing data — modify and save. Deleting will ask for confirmation first. <em>Deleted records cannot be recovered</em>, so export a backup beforehand if needed.</p>
                <span class="tip"><i class="fas fa-exclamation-triangle"></i> Always backup before deleting important records</span>
            </div>
        </div>

        <div class="step-card">
            <div class="step-number">7</div>
            <div class="step-body">
                <h3>Export & Import JSON Backup</h3>
                <p>Go to <strong>Settings &rarr; Export Data</strong> to download a complete JSON backup of all your sections, budgets, and records. To restore, go to <strong>Settings &rarr; Import Data</strong> and upload the JSON file. All your data will be restored — existing sections are preserved and new ones added automatically.</p>
                <span class="tip"><i class="fas fa-file-export"></i> Export regularly to keep a local backup of your financial data</span>
            </div>
        </div>

        <div class="step-card">
            <div class="step-number">8</div>
            <div class="step-body">
                <h3>Change Currency & Language</h3>
                <p>Go to <strong>Settings</strong> in the sidebar. Select your preferred <strong>Currency symbol</strong> (&#8377;, $, &euro;, &#2547; and 100+ more) — it applies to all amount displays. You can also switch the display <strong>Language</strong> using the Google Translate integration available in settings.</p>
                <span class="tip"><i class="fas fa-globe"></i> Currency preference is saved to your account and persists across logins</span>
            </div>
        </div>

        <div class="section-divider" style="margin-top:2rem;"><span><i class="fas fa-user-shield"></i> &nbsp;Admin Guide</span></div>

        <div class="step-card">
            <div class="step-number">A</div>
            <div class="step-body">
                <h3>Login to Admin Panel</h3>
                <p>Visit the <strong>Admin Portal</strong> from the homepage. Enter the admin credentials and the Admin Key set by the system administrator. The admin account is separate from member accounts and requires a dedicated login.</p>
                <span class="tip"><i class="fas fa-lock"></i> Admin Key is configured in the system — contact your administrator if locked out</span>
            </div>
        </div>

        <div class="step-card">
            <div class="step-number">B</div>
            <div class="step-body">
                <h3>Monitor Platform Activity</h3>
                <p>The admin dashboard shows <strong>Total Users</strong>, <strong>Daily Active Users</strong>, and <strong>Platform Total Expenditure</strong>. Use the <strong>Month Filter</strong> at the top to view stats for any specific month. The user breakdown table shows each member's total spending, sorted by highest or most recent activity.</p>
                <span class="tip"><i class="fas fa-chart-bar"></i> Use month filter to compare expenditure across different periods</span>
            </div>
        </div>

        <div class="step-card">
            <div class="step-number">C</div>
            <div class="step-body">
                <h3>Manage Member Accounts</h3>
                <p>In the user breakdown table, each row has a <strong>View</strong> button to see that member's detailed expense sections and records. You can also <strong>Delete</strong> a member account — this permanently removes all their data including sections, records, and budgets. A backup warning is shown before deletion.</p>
                <span class="tip"><i class="fas fa-trash-alt"></i> Member deletion is irreversible — data cannot be recovered once deleted</span>
            </div>
        </div>

        <!-- CTA -->
        <div class="about-page-cta">
            <h3>Ready to get started?</h3>
            <p>Create your account in seconds and start tracking expenses immediately.</p>
            <div class="cta-buttons">
                <a href="<?php echo $base; ?>user/login.php" class="cta-btn cta-btn-primary"><i class="fas fa-users"></i> Member Login</a>
                <a href="<?php echo $base; ?>admin/login.php" class="cta-btn cta-btn-ghost"><i class="fas fa-user-shield"></i> Admin Panel</a>
            </div>
        </div>

    </div>

    <!-- FOOTER -->
    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> PROJECT E. All rights reserved.</p>
        <div class="footer-links">
            <a href="https://linkedin.com/in/itznayan" target="_blank" style="color: var(--accent-secondary);"><i class="fab fa-linkedin"></i> Nayan</a>
            <span style="color: rgba(255,255,255,0.1);">|</span>
            <a href="https://github.com/anankush" target="_blank" style="color: var(--text-muted);"><i class="fab fa-github"></i> GitHub</a>
        </div>
    </footer>

    <script src="<?php echo $base; ?>assets/js/about.js"></script>
</body>
</html>
