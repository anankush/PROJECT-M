<!-- Exp/user/budgets.php -->
<div class="dashboard-header">
    <div class="header-left">
        <button class="mobile-menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <h1>Manage Budgets</h1>
    </div>
    <div class="header-controls">
        <input type="text" id="monthFilter" class="theme-input-select" placeholder="Select Month" readonly title="Select a month to filter">
    </div>
</div>

<div class="summary-grid fadeInUp stagger-2" id="budgetSummaryGrid" style="display: grid; margin-bottom: 2rem;">
    <div class="summary-card card-budget" id="overallBudgetCard" style="background:rgba(139, 92, 246, 0.1); border:1px solid rgba(139, 92, 246, 0.3); color:#a78bfa;">
        <span class="metric-label" style="display:flex; align-items:center; justify-content:center; gap:5px;">
            Overall Monthly Budget 
            <i class="fas fa-edit" style="cursor:pointer; font-size:0.8rem;" onclick="triggerEditOverallBudget()" title="Set Overall Budget"></i>
        </span>
        <div class="metric-value" id="overallBudgetDisplay">0.00</div>
    </div>
    <div class="summary-card" id="allocatedBudgetCard" style="background:rgba(6, 182, 212, 0.1); border:1px solid rgba(6, 182, 212, 0.3); color:#06b6d4;">
        <span class="metric-label">Allocated to Sections</span>
        <div class="metric-value" id="allocatedBudgetDisplay">0.00</div>
    </div>
    <div class="summary-card" id="unallocatedBudgetCard" style="background:rgba(16, 185, 129, 0.1); border:1px solid rgba(16, 185, 129, 0.3); color:#10b981;">
        <span class="metric-label" id="unallocatedLabel">Remaining to Allocate</span>
        <div class="metric-value" id="unallocatedBudgetDisplay">0.00</div>
    </div>
</div>

<div class="table-container fadeInUp stagger-3" style="display:block;">
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
