<!-- Exp/user/view_expenses.php -->
<div class="dashboard-header">
    <div class="header-left">
        <button class="mobile-menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>

        <h1 id="currentTableTitle">Select a Section</h1>
        <button class="btn btn-ghost mobile-refresh-btn" id="refreshBtnMobile" onclick="refreshCurrentView()" style="display:none; margin-left:auto;">
            <i class="fas fa-sync-alt refresh-icon"></i> <span class="refresh-text">Refresh</span>
        </button>
    </div>
    <div class="header-controls">
        <!-- Notes button moved to Manage Budgets page -->
        <input type="month" id="monthFilter" class="theme-input-select" onchange="handleMonthChange()" title="Select a month to filter">
        <select id="sortRecordsSelect" class="theme-input-select" onchange="sortRecords()" style="display:none;">
            <option value="newest" style="background:var(--bg-deep);">Date: Newest</option>
            <option value="oldest" style="background:var(--bg-deep);">Date: Oldest</option>
            <option value="highest" style="background:var(--bg-deep);">Amount: Highest</option>
            <option value="lowest" style="background:var(--bg-deep);">Amount: Lowest</option>
        </select>
        <button class="btn btn-ghost desktop-refresh-btn" id="refreshBtn" onclick="refreshCurrentView()" style="display:none;">
            <i class="fas fa-sync-alt refresh-icon"></i> <span class="refresh-text">Refresh</span>
        </button>
        <button class="btn btn-primary" id="addRecordBtn" onclick="addRecordForm()" style="display:none;">
            <i class="fas fa-plus"></i> <span class="hide-mobile">Add Record</span>
        </button>
    </div>
</div>

<div class="summary-grid" id="summaryGrid">

    <div class="summary-card" id="sectionBudgetBox" style="background:rgba(245, 158, 11, 0.1); border:1px solid rgba(245, 158, 11, 0.3); color:#f59e0b; display:none;">
        <span class="metric-label">Section Budget <i class="fas fa-edit" style="cursor:pointer; font-size:0.8rem; margin-left:5px;" onclick="editSectionBudget()" title="Edit Section Budget"></i></span>
        <div class="metric-value" id="sectionBudgetDisplay">0.00</div>
    </div>
    <div class="summary-card" id="sectionExpenditureBox" style="background:rgba(236, 72, 153, 0.1); border:1px solid rgba(236, 72, 153, 0.3); color:#ec4899; display:none;">
        <span class="metric-label">Section Expenditure</span>
        <div class="metric-value" id="sectionAmount">0.00</div>
    </div>
    <div class="summary-card" id="sectionBalanceBox" style="background:rgba(14, 165, 233, 0.1); border:1px solid rgba(14, 165, 233, 0.3); color:#0ea5e9; display:none;">
        <span class="metric-label">Section Remaining</span>
        <div class="metric-value" id="sectionBalanceDisplay">0.00</div>
    </div>
</div>

<div class="table-container">
    <div id="tableLoader" class="loader" style="display:none; text-align:center; padding:50px;">
        <i class="fas fa-circle-notch fa-spin fa-2x"></i>
    </div>
    <table id="dataTable" style="display:none;">
        <thead id="tableHead"></thead>
        <tbody id="tableBody"></tbody>
    </table>
    <div id="emptyState" class="empty-state">
        <i class="fas fa-folder-open fa-3x" style="margin-bottom:1rem; opacity:0.5;"></i>
        <p>Please create a section or select one from the sidebar.</p>
    </div>
</div>
