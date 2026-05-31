<!-- Exp/user/budgets.php -->
<div class="dashboard-header">
    <div class="header-left">
        <button class="mobile-menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>

        <h1>Manage Budgets</h1>
    </div>
</div>

<div class="table-container fadeInUp stagger-2" style="display:block;">
    
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


