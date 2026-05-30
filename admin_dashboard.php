<?php
$pageTitle = 'Admin Dashboard | PROJECT M';
require_once __DIR__ . '/includes/config.php';
requireAdmin();
require_once __DIR__ . '/includes/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin_dashboard.css">

<div class="glass-panel dashboard-header-container">
    <h2>Admin Dashboard</h2>
    <p>Manage users and monitor overall platform statistics.</p>
</div>

<div class="admin-stats-grid">
    <div class="admin-stat-card">
        <div class="admin-stat-icon users"><i class="fas fa-users"></i></div>
        <div class="admin-stat-info">
            <h3 id="totalUsers">0</h3>
            <p>Total Registered Users</p>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon money"><i class="fas fa-coins"></i></div>
        <div class="admin-stat-info">
            <h3 id="platformTotal">₹0.00</h3>
            <p>Platform Total Expenses</p>
        </div>
    </div>
</div>

<div class="admin-table-container">
    <h3 style="margin-bottom: 20px; color: var(--primary-color);">Registered Users</h3>
    <table class="admin-table" id="userTable">
        <thead>
            <tr>
                <th>User ID</th>
                <th>Email</th>
                <th>Total Spent (Expenses)</th>
                <th style="text-align:right;">Actions</th>
            </tr>
        </thead>
        <tbody id="userTableBody">
            <tr><td colspan="4" style="text-align:center;">Loading users...</td></tr>
        </tbody>
    </table>
</div>

<script>
const CSRF_TOKEN = '<?= generate_csrf_token() ?>';

document.addEventListener('DOMContentLoaded', () => {
    loadAdminStats();
});

async function loadAdminStats() {
    try {
        const res = await fetch('includes/api_admin.php?action=get_admin_stats');
        const data = await res.json();
        
        if (data.status === 'success') {
            document.getElementById('totalUsers').innerText = data.total_users;
            document.getElementById('platformTotal').innerText = `₹${parseFloat(data.platform_total).toFixed(2)}`;
            
            const tbody = document.getElementById('userTableBody');
            tbody.innerHTML = '';
            
            if (data.user_breakdown.length > 0) {
                data.user_breakdown.forEach(user => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${user.id}</td>
                        <td>${user.email}</td>
                        <td style="color: var(--success-color); font-weight: bold;">₹${parseFloat(user.total_spent).toFixed(2)}</td>
                        <td style="text-align:right;">
                            <button class="action-btn btn-delete" onclick="deleteUser(${user.id}, '${user.email}')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">No users found.</td></tr>';
            }
        }
    } catch (e) {
        console.error(e);
        Swal.fire('Error', 'Failed to load admin stats', 'error');
    }
}

async function deleteUser(userId, userEmail) {
    const confirm = await Swal.fire({
        title: 'Delete User?',
        html: `This will permanently delete user <b>${userEmail}</b> and ALL their data (Expenses & Savings).`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d63031',
        confirmButtonText: 'Yes, delete everything!'
    });
    
    if (confirm.isConfirmed) {
        try {
            const res = await fetch('includes/api_admin.php?action=delete_user', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ target_user_id: userId, csrf_token: CSRF_TOKEN })
            });
            const data = await res.json();
            
            if (data.status === 'success') {
                Swal.fire('Deleted!', 'User and all associated data have been removed.', 'success');
                loadAdminStats();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Failed to delete user', 'error');
        }
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
