<!-- Exp/user/view_expenses.php -->
<div class="dashboard-header">
    <div class="header-left">
        <button class="mobile-menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <h1 id="currentTableTitle">Select a Section</h1>
        <div class="action-btns" id="sectionActions" style="display:none;">
            <button class="icon-btn edit" onclick="renameSection()" title="Rename Section">
                <i class="fas fa-pen"></i>
            </button>
            <button class="icon-btn delete" onclick="deleteSpecificCategory(currentCategoryId, currentCategoryName)" title="Delete Section">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <button class="btn btn-ghost mobile-refresh-btn" id="refreshBtnMobile" onclick="refreshCurrentView()" style="display:none; margin-left:auto;">
            <i class="fas fa-sync-alt refresh-icon"></i> <span class="refresh-text">Refresh</span>
        </button>
    </div>
    <div class="header-controls">
        <button class="btn btn-ghost" id="noteBtn" onclick="openNoteModal()" title="Section Notes" style="display:none;">
            <i class="far fa-sticky-note"></i> <span class="hide-mobile">Notes</span>
        </button>
        <input type="month" id="monthFilter" class="theme-input-select" value="<?php echo date('Y-m'); ?>" placeholder="Choose Month" style="max-width: 140px; font-size: 0.9rem; cursor: pointer;" onfocus="this.type='month'; if(this.showPicker) this.showPicker();" onblur="if(!this.value) this.type='text';" onchange="if(!this.value) this.type='text'; else this.type='month'; applyMonthFilter();" onkeydown="return false">
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
    <div class="summary-card card-budget" id="totalBudgetBox">
        <span class="metric-label" id="labelTotalBudget">Total Budget</span>
        <div class="metric-value" id="totalBudgetDisplay">0.00</div>
    </div>
    <div class="summary-card card-expenditure" id="totalExpenditureBox">
        <span class="metric-label" id="labelTotalExpenditure">Total Expenditure</span>
        <div class="metric-value" id="totalAmount">0.00</div>
    </div>
    <div class="summary-card card-balance" id="totalBalanceBox">
        <span class="metric-label" id="labelTotalBalance">Total Remaining</span>
        <div class="metric-value" id="totalBalanceDisplay">0.00</div>
    </div>

    <div class="summary-card" id="sectionBudgetBox" style="background:rgba(245, 158, 11, 0.1); border:1px solid rgba(245, 158, 11, 0.3); color:#f59e0b; display:none;">
        <span class="metric-label">Section Budget</span>
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
