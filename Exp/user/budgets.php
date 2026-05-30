<!-- Exp/user/budgets.php -->
<div class="dashboard-header">
    <div class="header-left">
        <button class="mobile-menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>

        <h1>Manage Budgets</h1>
    </div>
</div>

<div class="table-container" style="display:block;">
    <p style="margin-bottom:1rem; color:var(--text-muted);">Set or update your monthly budgets for each section.</p>
    
    <table id="budgetsTable">
        <thead>
            <tr>
                <th>Section Name</th>
                <th>Current Budget</th>
                <th style="text-align:right;">Action</th>
            </tr>
        </thead>
        <tbody id="budgetsTableBody">
            <!-- Populated by JS -->
        </tbody>
    </table>
</div>


