<!-- Sav/user/goals.php -->
<div class="dashboard-header">
    <div class="header-left">
        <button class="mobile-menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <h1>My Savings Goals</h1>
    </div>
    <div class="header-controls">
        <button class="btn btn-ghost desktop-refresh-btn" onclick="fetchGoals()">
            <i class="fas fa-sync-alt refresh-icon"></i> <span class="refresh-text">Refresh</span>
        </button>
    </div>
</div>

<div class="summary-grid" id="savSummaryGrid">
    <div class="summary-card card-budget">
        <span class="metric-label">Total Target</span>
        <div class="metric-value" id="totalTargetDisplay">0.00</div>
    </div>
    <div class="summary-card card-expenditure" style="background:rgba(16, 185, 129, 0.1); border:1px solid rgba(16, 185, 129, 0.3); color:#10b981;">
        <span class="metric-label">Total Saved</span>
        <div class="metric-value" id="totalSavedDisplay">0.00</div>
    </div>
    <div class="summary-card card-balance">
        <span class="metric-label">Remaining Needed</span>
        <div class="metric-value" id="totalRemainingDisplay">0.00</div>
    </div>
</div>

<div class="goals-container" id="goalsContainer" style="display:flex; flex-direction:column; gap:1.5rem;">
    <div id="goalsLoader" class="loader" style="text-align:center; padding:50px; display:none;">
        <i class="fas fa-circle-notch fa-spin fa-2x"></i>
    </div>
    <div id="goalsGrid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:1.5rem;">
        <!-- Goals injected here -->
    </div>
    <div id="goalsEmptyState" class="empty-state" style="display:none; text-align:center; padding:3rem; background:rgba(255,255,255,0.02); border-radius:15px; border:1px dashed rgba(255,255,255,0.1);">
        <i class="fas fa-bullseye fa-3x" style="margin-bottom:1rem; opacity:0.5; color:var(--text-muted);"></i>
        <p style="color:var(--text-muted);">You haven't set any savings goals yet.</p>
        <button class="btn btn-primary" style="margin-top:1rem;" onclick="addNewGoal()"><i class="fas fa-plus"></i> Create Goal</button>
    </div>
</div>
