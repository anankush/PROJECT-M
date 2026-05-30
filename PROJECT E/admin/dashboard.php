<?php
$pageTitle = 'Admin Panel | Expense Management';
require_once '../includes/header.php';
$base = BASE_URL;

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ' . $base . '404.php');
    exit;
}
check_session_timeout();
validate_url_access();
?>

<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/global.css">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/admin_dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo $base; ?>assets/js/forgot_password.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
</head>

<body>
    <div class="aurora-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>
    <div class="noise-overlay"></div>

    <div class="mobile-overlay" id="mobileOverlay" onclick="toggleAdminPanel()"></div>

    <!-- TOPBAR -->
    <header class="admin-topbar">
        <div class="topbar-brand">
            <div class="brand-icon"><i class="fas fa-user-shield"></i></div>
            ADMIN <span class="brand-sub">PANEL</span>
        </div>

        <div class="topbar-right">
            <div class="topbar-desktop-btns">
                <button class="btn btn-ghost" onclick="openAdminSettings()"><i class="fas fa-cog"></i> Settings</button>
                <a href="<?php echo $base; ?>index.php" class="btn btn-ghost"><i class="fas fa-home"></i></a>
                <button class="btn btn-danger" onclick="adminLogout()"><i class="fas fa-sign-out-alt"></i> Logout</button>
            </div>
            <button class="mobile-menu-btn" onclick="toggleAdminPanel()"><i class="fas fa-bars"></i></button>
        </div>
    </header>

    <!-- Admin Controls Row -->
    <div class="admin-controls">
        <input type="text" id="monthFilterDesktop" class="theme-input-select" placeholder="Select Month">
        <select id="sortRecordsSelectMobile" class="theme-input-select" onchange="sortRecords()" style="display:none;">
            <option value="newest" style="background:var(--bg-deep);">Newest</option>
            <option value="oldest" style="background:var(--bg-deep);">Oldest</option>
            <option value="highest" style="background:var(--bg-deep);">Highest</option>
            <option value="lowest" style="background:var(--bg-deep);">Lowest</option>
        </select>
        <button class="btn btn-ghost" onclick="refreshAdminView()"><i class="fas fa-sync-alt refresh-icon"></i> <span class="refresh-text">Refresh</span></button>
    </div>

    <!-- Mobile sidebar panel -->
    <div class="admin-sidebar-panel" id="adminSidebarActions">
        <div class="sidebar-bottom">
            <button class="btn btn-ghost" onclick="openAdminSettings(); toggleAdminPanel();"><i class="fas fa-cog"></i> Settings</button>
            <a href="<?php echo $base; ?>index.php" class="btn btn-ghost"><i class="fas fa-home"></i> Home</a>
            <button class="btn btn-danger" onclick="adminLogout()"><i class="fas fa-sign-out-alt"></i> Logout</button>
            <div class="support-info">Support: support.nayan@gmail.com</div>
            <div class="copyright">&copy; <?php echo date("Y"); ?> PROJECT E<br>All rights reserved.</div>
        </div>
    </div>

    <!-- MAIN -->
    <main class="admin-main">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon users"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3 id="totalUsers">0</h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon active"><i class="fas fa-user-check"></i></div>
                <div class="stat-info">
                    <h3 id="dailyActive">0</h3>
                    <p>Daily Active Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon total"><i class="fas fa-coins"></i></div>
                <div class="stat-info">
                    <h3 id="platformTotal">0</h3>
                    <p>Platform Total Expenditure</p>
                </div>
            </div>
        </div>

        <div class="user-table-container">
            <div id="tableLoader" class="loader" style="display:none;"></div>
            <table id="userTable" style="display:none;">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Currency</th>
                        <th>Total Spent</th>
                        <th>Last Active</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="userTableBody"></tbody>
            </table>
        </div>
    </main>

    <script>
        const API_URL = '<?php echo $base; ?>includes/api.php';
        const CSRF_TOKEN = '<?php echo generate_csrf_token(); ?>';

        let adminCurrency = '₹';
        let adminEmail = '';
        let isManualLogout = false;

        const allCurrencies = [
            "₹", "৳", "$", "€", "£", "¥", "د.إ", "ر.س", "A$", "C$", "Fr", "kr", "R", "₽", "₺", "₩", "Rp", "฿", "₫", "₱", "₦",
            "AED", "AFN", "ALL", "AMD", "ANG", "AOA", "ARS", "AUD", "AWG", "AZN", "BAM", "BBD", "BDT", "BGN", "BHD", "BIF", "BMD", "BND", "BOB", "BRL", "BSD", "BTN", "BWP", "BYN", "BZD",
            "CAD", "CDF", "CHF", "CLP", "CNY", "COP", "CRC", "CUP", "CVE", "CZK", "DJF", "DKK", "DOP", "DZD", "EGP", "ERN", "ETB", "EUR",
            "FJD", "FKP", "GBP", "GEL", "GHS", "GIP", "GMD", "GNF", "GTQ", "GYD", "HKD", "HNL", "HRK", "HTG", "HUF", "IDR", "ILS", "INR", "IQD", "IRR", "ISK",
            "JMD", "JOD", "JPY", "KES", "KGS", "KHR", "KMF", "KRW", "KWD", "KYD", "KZT", "LAK", "LBP", "LKR", "LRD", "LSL", "LYD", "MAD", "MDL", "MGA", "MKD", "MMK", "MNT", "MOP", "MUR", "MVR", "MWK", "MXN", "MYR", "MZN",
            "NAD", "NGN", "NIO", "NOK", "NPR", "NZD", "OMR", "PAB", "PEN", "PGK", "PHP", "PKR", "PLN", "PYG", "QAR",
            "RON", "RSD", "RUB", "RWF", "SAR", "SBD", "SCR", "SDG", "SEK", "SGD", "SHP", "SLE", "SLL", "SOS", "SRD", "SSP", "STN", "SYP", "SZL",
            "THB", "TJS", "TMT", "TND", "TOP", "TRY", "TTD", "TWD", "TZS", "UAH", "UGX", "USD", "UYU", "UZS", "VES", "VND", "VUV", "WST",
            "XAF", "XCD", "XOF", "XPF", "YER", "ZAR", "ZMW", "ZWL"
        ];

        function toggleAdminPanel() {
            const panel = document.getElementById('adminSidebarActions');
            const overlay = document.getElementById('mobileOverlay');
            panel.classList.toggle('open');
            overlay.classList.toggle('active');
        }

        document.addEventListener('DOMContentLoaded', () => {
            flatpickr("#monthFilterDesktop", {
                disableMobile: true,
                plugins: [new monthSelectPlugin({ shorthand: true, dateFormat: "Y-m", altFormat: "Y-m", theme: "dark" })],
                onChange: function() { loadAdminStats(); }
            });
            checkAdminSession();
        });

        async function checkAdminSession() {
            try {
                const res = await fetch(`${API_URL}?action=check_session`);
                const data = await res.json();
                if (data.is_admin) {
                    adminEmail = data.email || '';
                    adminCurrency = data.currency || '₹';
                    loadAdminStats();
                } else {
                    window.location.href = 'login.php';
                }
            } catch (e) { console.error(e); }
        }

        async function loadAdminStats() {
            const monthVal = document.getElementById('monthFilterDesktop').value;
            const loader = document.getElementById('tableLoader');
            const table = document.getElementById('userTable');

            loader.style.display = 'block';
            table.style.display = 'none';
            document.getElementById('sortRecordsSelectMobile').style.display = 'none';

            try {
                const res = await fetch(`${API_URL}?action=get_admin_stats&month=${monthVal}`);
                const data = await res.json();
                loader.style.display = 'none';

                if (data.status === 'success') {
                    document.getElementById('totalUsers').textContent = data.total_users;
                    document.getElementById('dailyActive').textContent = data.daily_active;
                    document.getElementById('platformTotal').textContent = `${adminCurrency}${parseFloat(data.platform_total).toFixed(2)}`;

                    const tbody = document.getElementById('userTableBody');
                    tbody.innerHTML = '';

                    if (data.user_breakdown && data.user_breakdown.length > 0) {
                        data.user_breakdown.forEach(user => {
                            const tr = document.createElement('tr');
                            const lastActive = user.last_active_at ? new Date(user.last_active_at).toLocaleDateString() : 'Never';
                            const userCurrency = user.currency || '₹';
                            const safeEmail = escapeHtml(user.email);
                            tr.innerHTML = `
                                <td>${safeEmail}</td>
                                <td>${userCurrency}</td>
                                <td style="color:var(--success); font-weight:600;">${userCurrency}${parseFloat(user.total_spent).toFixed(2)}</td>
                                <td style="font-size:0.85rem; color:var(--text-muted);">${lastActive}</td>
                                <td style="text-align:right;">
                                    <div class="action-btns">
                                        <button class="icon-btn edit" onclick="viewUserRecords('${user.encoded_id}', '${escapeHtml(user.email)}')" title="View Records"><i class="fas fa-eye"></i></button>
                                        <button class="icon-btn delete" onclick="deleteUser('${user.encoded_id}', '${escapeHtml(user.email)}')" title="Delete User"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            `;
                            tbody.appendChild(tr);
                        });
                        table.style.display = 'table';
                        document.getElementById('sortRecordsSelectMobile').style.display = 'inline-block';
                    }
                }
            } catch (e) { loader.style.display = 'none'; }
        }

        async function viewUserRecords(userId, userEmail) {
            try {
                const monthVal = document.getElementById('monthFilterDesktop').value;
                const res = await fetch(`${API_URL}?action=get_user_categories_admin&target_user_id=${userId}&month=${monthVal}`);
                if (res.status === 404) { window.location.href = '<?php echo $base; ?>404.php'; return; }
                const data = await res.json();

                if (data.status === 'success') {
                    let categoriesHtml = '';
                    if (data.data && data.data.length > 0) {
                        data.data.forEach(cat => {
                            categoriesHtml += `
                                <div class="cat-card" style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:10px; align-items:center;">
                                    <span class="cat-name" style="flex:1;">${escapeHtml(cat.category_name)}</span>
                                    <div style="text-align:right; font-size:0.85rem; display:flex; flex-direction:column; gap:2px;">
                                        <div style="color:var(--text-muted);">Budget: ${adminCurrency}${parseFloat(cat.budget || 0).toFixed(2)}</div>
                                        <div style="color:var(--danger); font-weight:600;">Spent: ${adminCurrency}${parseFloat(cat.spent || 0).toFixed(2)}</div>
                                    </div>
                                </div>`;
                        });
                    } else {
                        categoriesHtml = '<div style="padding:1.5rem; text-align:center; color:var(--text-muted);">No categories found</div>';
                    }

                    let titleText = `User: ${escapeHtml(userEmail)}`;
                    if (monthVal) {
                        const [y, m] = monthVal.split('-');
                        const mName = new Date(y, m - 1).toLocaleString('default', { month: 'long' });
                        titleText += ` (${mName} ${y})`;
                    } else {
                        titleText += ` (Overall)`;
                    }

                    Swal.fire({
                        title: titleText,
                        html: `<div style="text-align:left; max-height:400px; overflow-y:auto; border-radius:10px; border:1px solid rgba(255,255,255,0.06); background:rgba(255,255,255,0.02);">${categoriesHtml}</div>`,
                        width: 500,
                        confirmButtonColor: '#8b5cf6'
                    });
                }
            } catch (e) { Swal.fire('Error', 'Failed to load user data', 'error'); }
        }

        async function deleteUser(userId, userEmail) {
            const confirm = await Swal.fire({
                title: 'Delete User?',
                html: `This will permanently delete user "<b>${escapeHtml(userEmail)}</b>" and ALL their data.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Yes, delete!'
            });

            if (confirm.isConfirmed) {
                try {
                    const res = await fetch(`${API_URL}?action=delete_user_account`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
                        body: JSON.stringify({ target_user_id: userId })
                    });
                    if (res.status === 404) { window.location.href = '<?php echo $base; ?>404.php'; return; }
                    const data = await res.json();
                    if (data.status === 'success') {
                        Swal.fire('Deleted!', 'User has been removed.', 'success');
                        loadAdminStats();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                } catch (e) { Swal.fire('Error', 'Failed to delete user', 'error'); }
            }
        }

        async function refreshAdminView() {
            document.querySelectorAll('.refresh-icon').forEach(icon => {
                icon.classList.remove('spin');
                void icon.offsetWidth;
                icon.classList.add('spin');
            });
            try {
                const res = await fetch(`${API_URL}?action=check_session`);
                const data = await res.json();
                if (data.is_admin) {
                    adminCurrency = data.currency || '₹';
                    await loadAdminStats();
                } else {
                    window.location.href = 'login.php';
                }
            } catch (e) {
                console.error('Refresh failed:', e);
                Swal.fire('Error', 'Failed to refresh data', 'error');
            }
        }

        function sortRecords() {
            const val = document.getElementById('sortRecordsSelectMobile').value;
            const table = document.getElementById('userTable');
            const tbody = table.querySelector('tbody');
            if (!tbody) return;
            const rows = Array.from(tbody.querySelectorAll('tr'));
            rows.sort((a, b) => {
                const aAmt = parseFloat((a.cells[2]?.textContent || '').replace(/[^0-9.-]/g, '')) || 0;
                const bAmt = parseFloat((b.cells[2]?.textContent || '').replace(/[^0-9.-]/g, '')) || 0;
                const aDate = a.cells[3]?.textContent || '';
                const bDate = b.cells[3]?.textContent || '';
                if (val === 'highest') return bAmt - aAmt;
                if (val === 'lowest') return aAmt - bAmt;
                if (val === 'oldest') return aDate.localeCompare(bDate);
                return bDate.localeCompare(aDate);
            });
            rows.forEach(row => tbody.appendChild(row));
        }

        async function changeAdminPassword() {
            const { value: formValues } = await Swal.fire({
                title: 'Change Admin Password',
                html: `<div class="swal-form-container">
                    <div class="swal-field"><label class="swal-label">Current Password</label><input id="cp-old" type="password" class="theme-input-select swal-input" placeholder="Enter current password"></div>
                    <div class="swal-field"><label class="swal-label">New Password</label><input id="cp-new" type="password" class="theme-input-select swal-input" placeholder="Enter new password"></div>
                    <div style="text-align:right; margin-top:5px;"><a href="javascript:void(0)" onclick="event.preventDefault(); Swal.close(); forgotPassword(API_URL, CSRF_TOKEN, 'admin', adminEmail);" style="color: var(--danger); font-size: 0.85rem; cursor:pointer;"><i class="fas fa-key"></i> Forgot Current Password?</a></div>
                </div>`,
                width: 380,
                focusConfirm: false, showCancelButton: true, confirmButtonText: 'Update Password', confirmButtonColor: '#10b981',
                preConfirm: () => {
                    const oldp = document.getElementById('cp-old').value;
                    const newp = document.getElementById('cp-new').value;
                    if (!oldp || !newp) { Swal.showValidationMessage('Both fields are required!'); return false; }
                    if (newp.length < 8) { Swal.showValidationMessage('New password must be at least 8 characters!'); return false; }
                    return { oldp, newp };
                }
            });
            if (formValues) {
                const res = await fetch(`${API_URL}?action=admin_change_password`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN }, body: JSON.stringify({ old_password: formValues.oldp, new_password: formValues.newp }) });
                const data = await res.json();
                if (data.status === 'success') Swal.fire('Success', 'Password changed successfully!', 'success');
                else Swal.fire('Error', data.message, 'error');
            }
        }

        async function changeAdminKey() {
            const { value: formValues } = await Swal.fire({
                title: 'Change Admin Key',
                html: `<div style="text-align:left; margin-top:10px; padding: 0 10px;">
                    <label style="font-weight:600; color:var(--text-primary);">Current Admin Key</label>
                    <input id="ck-old" type="password" class="swal2-input" placeholder="Enter current admin key" style="width:100%; box-sizing:border-box; margin: 10px 0 0 0;">
                </div>
                <div style="text-align:left; margin-top:10px; padding: 0 10px;">
                    <label style="font-weight:600; color:var(--text-primary);">New Admin Key</label>
                    <input id="ck-new" type="password" class="swal2-input" placeholder="Enter new admin key" style="width:100%; box-sizing:border-box; margin: 10px 0 0 0;">
                </div>`,
                focusConfirm: false, showCancelButton: true, confirmButtonText: 'Update Admin Key', confirmButtonColor: '#8b5cf6',
                preConfirm: () => {
                    const oldk = document.getElementById('ck-old').value;
                    const newk = document.getElementById('ck-new').value;
                    if (!oldk || !newk) Swal.showValidationMessage('Both fields are required!');
                    if (newk.length < 6) Swal.showValidationMessage('New key must be at least 6 characters!');
                    return { oldk, newk };
                }
            });
            if (formValues) {
                const res = await fetch(`${API_URL}?action=admin_change_key`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
                    body: JSON.stringify({ old_key: formValues.oldk, new_key: formValues.newk })
                });
                const data = await res.json();
                if (data.status === 'success') Swal.fire('Success', 'Admin key changed successfully!', 'success');
                else Swal.fire('Error', data.message, 'error');
            }
        }

        async function openAdminSettings() {
            window.tempAdminCurrency = adminCurrency;
            let gridHtml = '';
            allCurrencies.forEach(c => { gridHtml += `<div class="set-card curr-card ${adminCurrency===c?'active':''}" onclick="selectCurr(this, '${c}')">${c}</div>`; });

            const { value: formValues } = await Swal.fire({
                title: 'Admin Settings', width: 600,
                html: `
                    <div style="text-align:left; margin-bottom:15px;">
                        <label style="font-weight:600; color:var(--text-primary);">Select Currency (160+ Currencies)</label>
                        <div class="set-grid" style="max-height:300px; overflow-y:auto; padding-right:10px; margin-top:10px;">${gridHtml}</div>
                    </div>
                    <hr style="border-color:rgba(255,255,255,0.1); margin:15px 0;">
                    <div style="text-align:left; margin-bottom:15px;">
                        <label style="font-weight:600; color:var(--text-primary);">Security</label>
                        <div style="display:flex; gap:10px; margin-top:10px;">
                            <button type="button" class="btn btn-ghost" style="flex:1;" onclick="Swal.close(); changeAdminPassword();"><i class="fas fa-key"></i> Change Password</button>
                            <button type="button" class="btn btn-ghost" style="flex:1;" onclick="Swal.close(); changeAdminKey();"><i class="fas fa-shield-alt"></i> Change Admin Key</button>
                        </div>
                    </div>
                    <hr style="border-color:rgba(239,68,68,0.3); margin:15px 0;">
                    <div style="text-align:left; margin-bottom:15px;">
                        <label style="font-weight:600; color:var(--danger);">Danger Zone</label>
                        <div style="margin-top:10px;">
                            <button type="button" class="btn btn-danger" style="width:100%;" onclick="Swal.close(); deleteAdminAccount();"><i class="fas fa-trash-alt"></i> Permanently Delete Admin Account</button>
                        </div>
                    </div>
                `,
                focusConfirm: false, showCancelButton: true, confirmButtonText: 'Save Changes', confirmButtonColor: '#8b5cf6',
                preConfirm: () => { return { currency: window.tempAdminCurrency || adminCurrency, language: 'en' } }
            });
            if (formValues) {
                const res = await fetch(`${API_URL}?action=update_admin_settings`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN }, body: JSON.stringify(formValues) });
                const result = await res.json();
                if (result.status === 'success') { adminCurrency = formValues.currency; loadAdminStats(); Swal.fire('Saved', 'Settings applied successfully', 'success'); }
                else Swal.fire('Error', result.message, 'error');
            }
        }

        window.tempAdminCurrency = adminCurrency;
        function selectCurr(el, val) { document.querySelectorAll('.curr-card').forEach(c => c.classList.remove('active')); el.classList.add('active'); window.tempAdminCurrency = val; }

        history.pushState(null, null, location.href);
        window.addEventListener('popstate', function(e) {
            fetch(`${API_URL}?action=admin_logout`, { method: 'POST', headers: { 'X-CSRF-Token': CSRF_TOKEN }, keepalive: true });
            window.location.href = 'login.php';
        });

        window.addEventListener('beforeunload', function(e) {
            if (!isManualLogout) {
                fetch(`${API_URL}?action=admin_logout`, { method: 'POST', headers: { 'X-CSRF-Token': CSRF_TOKEN }, keepalive: true });
            }
        });

        async function deleteAdminAccount() {
            const confirm = await Swal.fire({
                title: 'Delete Admin Account?',
                html: "<span style='color:#ef4444; font-weight:bold;'>WARNING:</span> This will permanently delete your admin account.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Yes, Delete Everything!'
            });
            if (confirm.isConfirmed) {
                const res = await fetch(`${API_URL}?action=delete_admin_account`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN } });
                const data = await res.json();
                if (data.status === 'success') {
                    await Swal.fire('Deleted', 'Admin account removed.', 'success');
                    window.location.href = '<?php echo $base; ?>admin/login.php';
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            }
        }

        async function adminLogout() {
            isManualLogout = true;
            const confirm = await Swal.fire({
                title: 'Logout?',
                text: 'Are you sure you want to logout?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Yes, Logout',
                cancelButtonText: 'Cancel'
            });
            if (confirm.isConfirmed) {
                await fetch(`${API_URL}?action=admin_logout`, { method: 'POST', headers: { 'X-CSRF-Token': CSRF_TOKEN } });
                window.location.href = 'login.php';
            } else {
                isManualLogout = false;
            }
        }
    </script>

    <style>
        .set-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(60px, 1fr)); gap: 8px; margin-bottom: 1rem; }
        .set-card { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; padding: 10px 5px; text-align: center; cursor: pointer; transition: 0.2s; font-size: 0.85rem; color: var(--text-secondary); }
        .set-card:hover { background: rgba(255, 255, 255, 0.1); }
        .set-card.active { background: rgba(139, 92, 246, 0.2); border-color: var(--aurora-1); color: white; font-weight: 600; }
    </style>
</body>
</html>
