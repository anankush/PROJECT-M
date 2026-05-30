// Sav/assets/js/sav.js
let userCurrency = '₹';
let email = '';
let currentView = 'goals.php';
let goals = [];

async function loadSavView(viewName, tabId) {
    currentView = viewName;
    document.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
    if(tabId) document.getElementById(tabId).classList.add('active');

    try {
        const res = await fetch(viewName);
        const html = await res.text();
        document.getElementById('main-content').innerHTML = html;
        
        if (viewName === 'goals.php') {
            fetchGoals();
        } else if (viewName === 'history.php') {
            populateGoalFilter();
            fetchHistory();
        }

        if (window.innerWidth <= 768) {
            const sidebar = document.getElementById('appSidebar');
            if (sidebar.classList.contains('open')) toggleSidebar();
        }
    } catch (e) {
        console.error("Failed to load view", e);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    checkAuth();
});

function toggleSidebar() {
    document.getElementById('appSidebar').classList.toggle('open');
    document.querySelector('.sidebar-overlay').classList.toggle('open');
}

async function checkAuth() {
    try {
        const res = await fetch(`${API_URL}?action=check_session`);
        const data = await res.json();
        if (data.is_user) {
            email = data.email;
            userCurrency = data.currency;
            document.getElementById('userNameDisplay').innerText = `Welcome, ${email}`;
            document.getElementById('appUI').style.display = 'flex';
            loadSavView('goals.php', 'tab-goals');
        } else {
            window.location.href = '../../auth/login.php';
        }
    } catch (e) {
        console.error(e);
    }
}

async function fetchGoals() {
    document.getElementById('goalsLoader').style.display = 'block';
    document.getElementById('goalsGrid').style.display = 'none';
    document.getElementById('goalsEmptyState').style.display = 'none';

    try {
        const res = await fetch(`${API_URL}?action=get_goals`);
        const result = await res.json();
        document.getElementById('goalsLoader').style.display = 'none';
        
        if (result.status === 'success') {
            goals = result.data;
            renderGoals();
        }
    } catch (e) {
        console.error(e);
        document.getElementById('goalsLoader').style.display = 'none';
    }
}

function renderGoals() {
    const grid = document.getElementById('goalsGrid');
    const emptyState = document.getElementById('goalsEmptyState');
    grid.innerHTML = '';

    if (!goals || goals.length === 0) {
        emptyState.style.display = 'block';
        updateSummary(0, 0);
        return;
    }

    emptyState.style.display = 'none';
    grid.style.display = 'grid';

    let totalTarget = 0;
    let totalSaved = 0;

    goals.forEach(g => {
        let target = parseFloat(g.target_amount) || 0;
        let current = parseFloat(g.current_amount) || 0;
        totalTarget += target;
        totalSaved += current;

        let pct = target > 0 ? (current / target) * 100 : 0;
        if (pct > 100) pct = 100;
        if (pct < 0) pct = 0;
        
        let deadlineStr = g.deadline ? new Date(g.deadline).toLocaleDateString() : 'No Deadline';
        let badgeHtml = current >= target && target > 0 ? `<div class="goal-badge">ACHIEVED <i class="fas fa-check-circle"></i></div>` : '';

        const card = document.createElement('div');
        card.className = 'goal-card';
        card.innerHTML = `
            ${badgeHtml}
            <div class="goal-header">
                <div style="flex:1;">
                    <div class="goal-title" title="${escapeHtml(g.goal_name)}">${escapeHtml(g.goal_name)}</div>
                    <div class="goal-deadline"><i class="far fa-calendar-alt"></i> ${deadlineStr}</div>
                </div>
                <div style="text-align:right;">
                    <button class="icon-btn delete" onclick="deleteGoal(${g.id})" title="Delete Goal"><i class="fas fa-trash"></i></button>
                </div>
            </div>
            
            <div class="goal-amounts">
                <div>Saved: <span class="amount-current">${userCurrency}${current.toFixed(2)}</span></div>
                <div>Target: <span>${userCurrency}${target.toFixed(2)}</span></div>
            </div>
            
            <div class="progress-container" title="${pct.toFixed(1)}%">
                <div class="progress-bar" style="width: ${pct}%"></div>
            </div>
            <div style="text-align:right; font-size:0.8rem; color:var(--text-muted); margin-top:-5px;">${pct.toFixed(1)}% Completed</div>
            
            <div class="goal-actions">
                <button class="btn btn-deposit" onclick="openDepositModal(${g.id}, 'deposit')"><i class="fas fa-arrow-down"></i> Deposit</button>
                <button class="btn btn-withdraw" onclick="openDepositModal(${g.id}, 'withdraw')"><i class="fas fa-arrow-up"></i> Withdraw</button>
            </div>
        `;
        grid.appendChild(card);
    });

    updateSummary(totalTarget, totalSaved);
}

function updateSummary(target, saved) {
    if(document.getElementById('totalTargetDisplay')) {
        document.getElementById('totalTargetDisplay').innerHTML = `${userCurrency}${target.toFixed(2)}`;
        document.getElementById('totalSavedDisplay').innerHTML = `${userCurrency}${saved.toFixed(2)}`;
        let rem = target - saved;
        if (rem < 0) rem = 0;
        document.getElementById('totalRemainingDisplay').innerHTML = `${userCurrency}${rem.toFixed(2)}`;
    }
}

async function addNewGoal() {
    const { value: formValues } = await Swal.fire({
        title: 'Create Savings Goal',
        html: `
            <div class="swal-form-container">
                <div class="swal-field">
                    <label class="swal-label">Goal Name</label>
                    <input id="sg-name" type="text" class="theme-input-select swal-input" placeholder="e.g. Dream Car">
                </div>
                <div class="swal-field">
                    <label class="swal-label">Target Amount (${userCurrency})</label>
                    <input id="sg-target" type="number" step="0.01" class="theme-input-select swal-input" placeholder="0.00">
                </div>
                <div class="swal-field">
                    <label class="swal-label">Deadline (Optional)</label>
                    <input id="sg-date" type="date" class="theme-input-select swal-input">
                </div>
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Create Goal',
        confirmButtonColor: '#8b5cf6',
        preConfirm: () => {
            const name = document.getElementById('sg-name').value;
            const target = document.getElementById('sg-target').value;
            const deadline = document.getElementById('sg-date').value;
            if (!name || !target) {
                Swal.showValidationMessage('Name and Target Amount are required!');
                return false;
            }
            return { goal_name: name, target_amount: target, deadline: deadline };
        }
    });

    if (formValues) {
        const res = await fetch(`${API_URL}?action=add_goal`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
            body: JSON.stringify(formValues)
        });
        const result = await res.json();
        if (result.status === 'success') {
            Swal.fire('Created!', result.message, 'success');
            if (currentView === 'goals.php') fetchGoals();
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    }
}

async function deleteGoal(id) {
    const confirm = await Swal.fire({
        title: 'Delete Goal?',
        html: `Are you sure? This will permanently remove the goal and all its transaction history.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, delete it!'
    });
    
    if (confirm.isConfirmed) {
        const res = await fetch(`${API_URL}?action=delete_goal`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
            body: JSON.stringify({ goal_id: id })
        });
        const result = await res.json();
        if (result.status === 'success') {
            Swal.fire('Deleted!', 'Goal removed.', 'success');
            if (currentView === 'goals.php') fetchGoals();
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    }
}

async function openDepositModal(goalId, type) {
    let typeText = type === 'deposit' ? 'Deposit to Goal' : 'Withdraw from Goal';
    let btnColor = type === 'deposit' ? '#10b981' : '#ef4444';
    
    const { value: formValues } = await Swal.fire({
        title: typeText,
        html: `
            <div class="swal-form-container">
                <div class="swal-field">
                    <label class="swal-label">Amount (${userCurrency})</label>
                    <input id="st-amt" type="number" step="0.01" class="theme-input-select swal-input" placeholder="0.00">
                </div>
                <div class="swal-field">
                    <label class="swal-label">Date</label>
                    <input id="st-date" type="date" class="theme-input-select swal-input" value="${new Date().toISOString().split('T')[0]}">
                </div>
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: type === 'deposit' ? 'Add Funds' : 'Withdraw Funds',
        confirmButtonColor: btnColor,
        preConfirm: () => {
            const amt = document.getElementById('st-amt').value;
            const date = document.getElementById('st-date').value;
            if (!amt || amt <= 0) {
                Swal.showValidationMessage('Please enter a valid positive amount.');
                return false;
            }
            return { goal_id: goalId, amount: amt, type: type, date: date };
        }
    });

    if (formValues) {
        const res = await fetch(`${API_URL}?action=add_deposit`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
            body: JSON.stringify(formValues)
        });
        const result = await res.json();
        if (result.status === 'success') {
            Swal.fire('Success!', result.message, 'success');
            if (currentView === 'goals.php') fetchGoals();
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    }
}

async function populateGoalFilter() {
    if (goals.length === 0) {
        // Fetch if empty
        const res = await fetch(`${API_URL}?action=get_goals`);
        const result = await res.json();
        if (result.status === 'success') goals = result.data;
    }
    const filter = document.getElementById('goalFilter');
    if (filter) {
        filter.innerHTML = '<option value="all" style="background:var(--bg-deep);">All Goals</option>';
        goals.forEach(g => {
            filter.innerHTML += `<option value="${g.id}" style="background:var(--bg-deep);">${escapeHtml(g.goal_name)}</option>`;
        });
    }
}

async function fetchHistory() {
    const loader = document.getElementById('historyLoader');
    const table = document.getElementById('historyTable');
    const tbody = document.getElementById('historyTableBody');
    const emptyState = document.getElementById('historyEmptyState');
    const filter = document.getElementById('goalFilter');
    
    let goalId = filter ? filter.value : 'all';

    loader.style.display = 'block';
    table.style.display = 'none';
    emptyState.style.display = 'none';

    try {
        const res = await fetch(`${API_URL}?action=get_history&goal_id=${goalId}`);
        const result = await res.json();
        loader.style.display = 'none';
        
        if (result.status === 'success') {
            tbody.innerHTML = '';
            if (result.data.length > 0) {
                result.data.forEach(r => {
                    let typeHtml = r.type === 'deposit' ? 
                        `<span style="color:#10b981; font-weight:bold;"><i class="fas fa-arrow-down"></i> Deposit</span>` : 
                        `<span style="color:#ef4444; font-weight:bold;"><i class="fas fa-arrow-up"></i> Withdraw</span>`;
                    
                    let tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${new Date(r.transaction_date).toLocaleDateString()}</td>
                        <td>${escapeHtml(r.goal_name)}</td>
                        <td>${typeHtml}</td>
                        <td style="font-weight:bold; color: ${r.type==='deposit' ? '#10b981' : '#ef4444'}">${userCurrency}${parseFloat(r.amount).toFixed(2)}</td>
                    `;
                    tbody.appendChild(tr);
                });
                table.style.display = 'table';
            } else {
                emptyState.style.display = 'block';
            }
        }
    } catch (e) {
        console.error(e);
        loader.style.display = 'none';
    }
}

function escapeHtml(unsafe) {
    if (!unsafe) return '';
    return unsafe
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/"/g, "&quot;")
         .replace(/'/g, "&#039;");
}
