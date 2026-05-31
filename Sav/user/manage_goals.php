<!-- Sav/user/manage_goals.php -->
<div class="dashboard-header">
    <div class="header-left">
        <button class="mobile-menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <h1>Manage Savings Goals</h1>
    </div>
</div>

<div class="table-container fadeInUp" style="display:block;">
    <table id="manageGoalsTable">
        <thead>
            <tr>
                <th>Goal Name</th>
                <th>Category</th>
                <th>Priority</th>
                <th>Target Amount</th>
                <th style="text-align:right;">Actions</th>
            </tr>
        </thead>
        <tbody id="manageGoalsTableBody">
            <!-- Populated dynamically by sav.js -->
        </tbody>
    </table>
</div>
