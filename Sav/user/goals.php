<!-- Sav/user/goals.php -->
<style>
    @media (max-width: 768px) {
        #addGoalBtn {
            display: flex !important;
            position: fixed !important;
            bottom: 24px !important;
            right: 24px !important;
            top: auto !important;
            left: auto !important;
            width: 60px !important;
            height: 60px !important;
            border-radius: 50% !important;
            align-items: center !important;
            justify-content: center !important;
            box-shadow: 0 10px 25px rgba(139, 92, 246, 0.5) !important;
            z-index: 99999 !important;
            padding: 0 !important;
        }
        #addGoalBtn .hide-mobile {
            display: none !important;
        }
        #addGoalBtn i {
            font-size: 1.5rem !important;
            margin: 0 !important;
        }
    }
</style>
<div class="dashboard-header">
    <div class="header-left">
        <button class="mobile-menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <h1>My Savings Goals</h1>
    </div>
    <div class="header-controls">
        <button class="btn btn-ghost" onclick="openEmergencyCalculator()" style="background:rgba(6, 182, 212, 0.1); color:#06b6d4; border: 1px solid rgba(6, 182, 212, 0.3); display:flex; align-items:center; justify-content:center; gap:6px;">
            <i class="fas fa-shield-alt"></i> <span>Emergency Calculator</span>
        </button>
        <button class="btn btn-ghost refresh-btn" onclick="fetchGoals()">
            <i class="fas fa-sync-alt refresh-icon"></i> <span>Refresh</span>
        </button>
        <button class="btn btn-primary" id="addGoalBtn" onclick="addNewGoal()">
            <i class="fas fa-plus"></i> <span class="hide-mobile">Create New Goal</span>
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
