<!-- Sav/user/history.php -->
<div class="dashboard-header">
    <div class="header-left">
        <button class="mobile-menu-btn" id="sidebarToggleBtn" onclick="toggleSidebar()"><div class="hamburger-icon"><span></span><span></span><span></span></div></button>
        <h1>Deposit History</h1>
    </div>
    <div class="header-controls">
        <select id="goalFilter" class="theme-input-select" onchange="fetchHistory()">
            <option value="all" style="background:var(--bg-deep);">All Goals</option>
            <!-- Injected dynamically -->
        </select>
        <button class="btn btn-ghost refresh-btn" onclick="fetchHistory()">
            <i class="fas fa-sync-alt refresh-icon"></i> <span>Refresh</span>
        </button>
    </div>
</div>

<div class="table-container">
    <div id="historyLoader" class="loader" style="text-align:center; padding:50px; display:none;">
        <i class="fas fa-circle-notch fa-spin fa-2x"></i>
    </div>
    <table id="historyTable" style="display:none;">
        <thead>
            <tr>
                <th>Date</th>
                <th>Goal</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody id="historyTableBody"></tbody>
    </table>
    <div id="historyEmptyState" class="empty-state" style="display:none;">
        <i class="fas fa-receipt fa-3x" style="margin-bottom:1rem; opacity:0.5;"></i>
        <p>No deposit history found.</p>
    </div>
</div>
