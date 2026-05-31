// Sav/assets/js/sav.js
let userCurrency = '₹';
let email = '';
let currentView = 'goals.php';
let goals = [];

const categoryIcons = {
    travel: 'fas fa-plane',
    home: 'fas fa-home',
    education: 'fas fa-graduation-cap',
    gadget: 'fas fa-laptop',
    emergency: 'fas fa-shield-alt',
    wedding: 'fas fa-ring',
    others: 'fas fa-rocket'
};

const categoryNames = {
    travel: 'Travel',
    home: 'Home',
    education: 'Education',
    gadget: 'Gadget',
    emergency: 'Emergency Fund',
    wedding: 'Wedding',
    others: 'Others'
};

const priorityClasses = {
    low: 'priority-low',
    medium: 'priority-medium',
    high: 'priority-high'
};

async function loadSavView(viewName, tabId) {
    currentView = viewName;
    document.querySelectorAll('.category-tab, .sidebar-bottom .btn').forEach(t => t.classList.remove('active'));
    if(tabId) {
        const activeTab = document.getElementById(tabId);
        if (activeTab) activeTab.classList.add('active');
    }

    try {
        const res = await fetch(viewName);
        const html = await res.text();
        document.getElementById('main-content').innerHTML = html;
        
        if (viewName === 'goals.php' || viewName === 'manage_goals.php') {
            fetchGoals();
        } else if (viewName === 'history.php') {
            populateGoalFilter();
            fetchHistory();
        }

        if (window.innerWidth <= 768) {
            const sidebar = document.getElementById('appSidebar');
            if (sidebar && sidebar.classList.contains('open')) toggleSidebar();
        }
    } catch (e) {
        console.error("Failed to load view", e);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    checkAuth();
});

function toggleSidebar() {
    const sidebar = document.getElementById('appSidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    if (sidebar) sidebar.classList.toggle('open');
    if (overlay) overlay.classList.toggle('open');
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
    if (document.getElementById('goalsLoader')) document.getElementById('goalsLoader').style.display = 'block';
    if (document.getElementById('goalsGrid')) document.getElementById('goalsGrid').style.display = 'none';
    if (document.getElementById('goalsEmptyState')) document.getElementById('goalsEmptyState').style.display = 'none';

    try {
        const res = await fetch(`${API_URL}?action=get_goals`);
        const result = await res.json();
        if (document.getElementById('goalsLoader')) document.getElementById('goalsLoader').style.display = 'none';
        
        if (result.status === 'success') {
            goals = result.data;
            if (currentView === 'goals.php') {
                renderGoals();
            } else if (currentView === 'manage_goals.php') {
                renderManageGoalsTable();
            }
        }
    } catch (e) {
        console.error(e);
        if (document.getElementById('goalsLoader')) document.getElementById('goalsLoader').style.display = 'none';
    }
}

function renderGoals() {
    const grid = document.getElementById('goalsGrid');
    const emptyState = document.getElementById('goalsEmptyState');
    if (!grid) return;
    grid.innerHTML = '';

    if (!goals || goals.length === 0) {
        if (emptyState) emptyState.style.display = 'block';
        updateSummary(0, 0);
        return;
    }

    if (emptyState) emptyState.style.display = 'none';
    grid.style.display = 'grid';

    let totalTarget = 0;
    let totalSaved = 0;

    // Sort goals so High priority shows first, then Medium, then Low
    const priorityOrder = { high: 1, medium: 2, low: 3 };
    const sortedGoals = [...goals].sort((a, b) => {
        const pA = priorityOrder[a.priority || 'medium'] || 2;
        const pB = priorityOrder[b.priority || 'medium'] || 2;
        return pA - pB;
    });

    sortedGoals.forEach(g => {
        let target = parseFloat(g.target_amount) || 0;
        let current = parseFloat(g.current_amount) || 0;
        totalTarget += target;
        totalSaved += current;

        let pct = target > 0 ? (current / target) * 100 : 0;
        if (pct > 100) pct = 100;
        if (pct < 0) pct = 0;
        
        let deadlineStr = g.deadline ? new Date(g.deadline).toLocaleDateString() : 'No Deadline';
        
        const pClass = priorityClasses[g.priority || 'medium'] || 'priority-medium';
        const pLabel = (g.priority || 'medium').toUpperCase();
        
        const iconClass = categoryIcons[g.category || 'others'] || 'fas fa-rocket';
        let badgeHtml = current >= target && target > 0 ? `<div class="goal-badge">ACHIEVED <i class="fas fa-check-circle"></i></div>` : '';

        // Planner Info
        let plannerHtml = '';
        if (current < target && target > 0 && g.deadline) {
            const diffTime = new Date(g.deadline) - new Date();
            const diffMonths = Math.ceil(diffTime / (1000 * 60 * 60 * 24 * 30.4));
            if (diffMonths > 0) {
                const neededPerMonth = (target - current) / diffMonths;
                plannerHtml = `
                    <div class="planner-info">
                        <div>📅 Target in <strong>${diffMonths} month(s)</strong></div>
                        <div>💡 Suggested savings: <strong>${userCurrency}${neededPerMonth.toFixed(2)}/month</strong></div>
                    </div>
                `;
            } else {
                plannerHtml = `
                    <div class="planner-info" style="border-color: rgba(239, 68, 68, 0.2); background: rgba(239, 68, 68, 0.05); color: #ef4444;">
                        <div>⚠️ Deadline passed! <strong>Behind Schedule</strong></div>
                    </div>
                `;
            }
        }

        const card = document.createElement('div');
        card.className = `goal-card theme-card-${g.theme_color || 'purple'}`;
        card.innerHTML = `
            ${badgeHtml}
            <div class="goal-card-top">
                <div class="goal-cat-icon"><i class="${iconClass}"></i></div>
                <div style="flex:1; min-width:0;">
                    <div class="goal-title" title="${escapeHtml(g.goal_name)}">${escapeHtml(g.goal_name)}</div>
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:2px;">
                        <span class="priority-badge ${pClass}">${pLabel}</span>
                        <div class="goal-deadline"><i class="far fa-calendar-alt"></i> ${deadlineStr}</div>
                    </div>
                </div>
            </div>
            
            <div class="goal-amounts">
                <div>Saved: <span class="amount-current">${userCurrency}${current.toFixed(2)}</span></div>
                <div>Target: <span>${userCurrency}${target.toFixed(2)}</span></div>
            </div>
            
            <div class="progress-container" title="${pct.toFixed(1)}%">
                <div class="progress-bar" style="width: ${pct}%"></div>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.75rem; color:var(--text-muted); margin-top:-5px;">
                <span>${pct.toFixed(1)}% Completed</span>
                <span class="projection-btn" onclick="showSIPProjection(${g.id})"><i class="fas fa-chart-line"></i> Growth Projection</span>
            </div>
            
            ${plannerHtml}
            
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
                    <input id="sg-target" type="number" step="0.01" class="theme-input-select swal-input" placeholder="0.00" value="10000">
                </div>
                <div class="swal-field">
                    <label class="swal-label">Deadline (Optional)</label>
                    <input id="sg-date" type="date" class="theme-input-select swal-input">
                </div>
                <div class="swal-field">
                    <label class="swal-label">Category</label>
                    <select id="sg-category" class="theme-input-select swal-input" style="background:var(--bg-deep);">
                        <option value="travel">Travel</option>
                        <option value="home">Home</option>
                        <option value="education">Education</option>
                        <option value="gadget">Gadget</option>
                        <option value="emergency">Emergency Fund</option>
                        <option value="wedding">Wedding</option>
                        <option value="others" selected>Others</option>
                    </select>
                </div>
                <div class="swal-field">
                    <label class="swal-label">Theme Color</label>
                    <select id="sg-theme" class="theme-input-select swal-input" style="background:var(--bg-deep);">
                        <option value="purple" selected>Purple Glass</option>
                        <option value="emerald">Emerald Glass</option>
                        <option value="sunset">Sunset Orange</option>
                        <option value="ocean">Ocean Blue</option>
                    </select>
                </div>
                <div class="swal-field">
                    <label class="swal-label">Priority Level</label>
                    <select id="sg-priority" class="theme-input-select swal-input" style="background:var(--bg-deep);">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <hr style="border-color:rgba(255,255,255,0.08); margin:15px 0;">
                <div class="swal-field">
                    <label class="swal-label" id="slider-label">Monthly Savings: ${userCurrency}1,000</label>
                    <input id="sg-slider" type="range" min="100" max="50000" step="100" value="1000" style="width:100%; accent-color:var(--aurora-1);">
                    <div style="font-size:0.75rem; color:var(--text-muted); margin-top:5px;" id="slider-calc-result">Estimated target calculation.</div>
                </div>
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Create Goal',
        confirmButtonColor: '#8b5cf6',
        didOpen: () => {
            const targetInput = document.getElementById('sg-target');
            const sliderInput = document.getElementById('sg-slider');
            
            const updateSliderCalc = () => {
                const targetVal = parseFloat(targetInput.value) || 0;
                const sliderVal = parseFloat(sliderInput.value) || 0;
                const label = document.getElementById('slider-label');
                const result = document.getElementById('slider-calc-result');
                if (label) label.innerHTML = `Monthly Savings: ${userCurrency}${sliderVal.toLocaleString()}`;
                if (result) {
                    if (targetVal > 0 && sliderVal > 0) {
                        const months = Math.ceil(targetVal / sliderVal);
                        const years = (months / 12).toFixed(1);
                        result.innerHTML = `It will take <strong>${months} month(s)</strong> (${years} year(s)) to reach your target.`;
                        
                        const targetDate = new Date();
                        targetDate.setMonth(targetDate.getMonth() + months);
                        document.getElementById('sg-date').value = targetDate.toISOString().split('T')[0];
                    } else {
                        result.innerHTML = `Enter a target amount to estimate time.`;
                    }
                }
            };

            targetInput.addEventListener('input', updateSliderCalc);
            sliderInput.addEventListener('input', updateSliderCalc);
            updateSliderCalc();
        },
        preConfirm: () => {
            const name = document.getElementById('sg-name').value;
            const target = document.getElementById('sg-target').value;
            const deadline = document.getElementById('sg-date').value;
            const category = document.getElementById('sg-category').value;
            const theme_color = document.getElementById('sg-theme').value;
            const priority = document.getElementById('sg-priority').value;
            if (!name || !target) {
                Swal.showValidationMessage('Name and Target Amount are required!');
                return false;
            }
            return { goal_name: name, target_amount: target, deadline: deadline, category, theme_color, priority };
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
            if (currentView === 'goals.php' || currentView === 'manage_goals.php') fetchGoals();
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    }
}

async function editGoal(id) {
    const goal = goals.find(g => g.id === id);
    if (!goal) return;
    
    const { value: formValues } = await Swal.fire({
        title: 'Edit Savings Goal',
        html: `
            <div class="swal-form-container">
                <div class="swal-field">
                    <label class="swal-label">Goal Name</label>
                    <input id="sg-name" type="text" class="theme-input-select swal-input" value="${escapeHtml(goal.goal_name)}" placeholder="e.g. Dream Car">
                </div>
                <div class="swal-field">
                    <label class="swal-label">Target Amount (${userCurrency})</label>
                    <input id="sg-target" type="number" step="0.01" class="theme-input-select swal-input" value="${parseFloat(goal.target_amount)}" placeholder="0.00">
                </div>
                <div class="swal-field">
                    <label class="swal-label">Deadline (Optional)</label>
                    <input id="sg-date" type="date" class="theme-input-select swal-input" value="${goal.deadline || ''}">
                </div>
                <div class="swal-field">
                    <label class="swal-label">Category</label>
                    <select id="sg-category" class="theme-input-select swal-input" style="background:var(--bg-deep);">
                        <option value="travel" ${goal.category === 'travel' ? 'selected' : ''}>Travel</option>
                        <option value="home" ${goal.category === 'home' ? 'selected' : ''}>Home</option>
                        <option value="education" ${goal.category === 'education' ? 'selected' : ''}>Education</option>
                        <option value="gadget" ${goal.category === 'gadget' ? 'selected' : ''}>Gadget</option>
                        <option value="emergency" ${goal.category === 'emergency' ? 'selected' : ''}>Emergency Fund</option>
                        <option value="wedding" ${goal.category === 'wedding' ? 'selected' : ''}>Wedding</option>
                        <option value="others" ${goal.category === 'others' ? 'selected' : ''}>Others</option>
                    </select>
                </div>
                <div class="swal-field">
                    <label class="swal-label">Theme Color</label>
                    <select id="sg-theme" class="theme-input-select swal-input" style="background:var(--bg-deep);">
                        <option value="purple" ${goal.theme_color === 'purple' ? 'selected' : ''}>Purple Glass</option>
                        <option value="emerald" ${goal.theme_color === 'emerald' ? 'selected' : ''}>Emerald Glass</option>
                        <option value="sunset" ${goal.theme_color === 'sunset' ? 'selected' : ''}>Sunset Orange</option>
                        <option value="ocean" ${goal.theme_color === 'ocean' ? 'selected' : ''}>Ocean Blue</option>
                    </select>
                </div>
                <div class="swal-field">
                    <label class="swal-label">Priority Level</label>
                    <select id="sg-priority" class="theme-input-select swal-input" style="background:var(--bg-deep);">
                        <option value="low" ${goal.priority === 'low' ? 'selected' : ''}>Low</option>
                        <option value="medium" ${goal.priority === 'medium' ? 'selected' : ''}>Medium</option>
                        <option value="high" ${goal.priority === 'high' ? 'selected' : ''}>High</option>
                    </select>
                </div>
                <hr style="border-color:rgba(255,255,255,0.08); margin:15px 0;">
                <div class="swal-field">
                    <label class="swal-label" id="slider-label">Monthly Savings: ${userCurrency}1,000</label>
                    <input id="sg-slider" type="range" min="100" max="50000" step="100" value="1000" style="width:100%; accent-color:var(--aurora-1);">
                    <div style="font-size:0.75rem; color:var(--text-muted); margin-top:5px;" id="slider-calc-result">Estimated target calculation.</div>
                </div>
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Save Changes',
        confirmButtonColor: '#8b5cf6',
        didOpen: () => {
            const targetInput = document.getElementById('sg-target');
            const sliderInput = document.getElementById('sg-slider');
            
            const updateSliderCalc = () => {
                const targetVal = parseFloat(targetInput.value) || 0;
                const sliderVal = parseFloat(sliderInput.value) || 0;
                const label = document.getElementById('slider-label');
                const result = document.getElementById('slider-calc-result');
                if (label) label.innerHTML = `Monthly Savings: ${userCurrency}${sliderVal.toLocaleString()}`;
                if (result) {
                    if (targetVal > 0 && sliderVal > 0) {
                        const months = Math.ceil(targetVal / sliderVal);
                        const years = (months / 12).toFixed(1);
                        result.innerHTML = `It will take <strong>${months} month(s)</strong> (${years} year(s)) to reach your target.`;
                        
                        const targetDate = new Date();
                        targetDate.setMonth(targetDate.getMonth() + months);
                        document.getElementById('sg-date').value = targetDate.toISOString().split('T')[0];
                    } else {
                        result.innerHTML = `Enter a target amount to estimate time.`;
                    }
                }
            };

            targetInput.addEventListener('input', updateSliderCalc);
            sliderInput.addEventListener('input', updateSliderCalc);
            updateSliderCalc();
        },
        preConfirm: () => {
            const name = document.getElementById('sg-name').value;
            const target = document.getElementById('sg-target').value;
            const deadline = document.getElementById('sg-date').value;
            const category = document.getElementById('sg-category').value;
            const theme_color = document.getElementById('sg-theme').value;
            const priority = document.getElementById('sg-priority').value;
            if (!name || !target) {
                Swal.showValidationMessage('Name and Target Amount are required!');
                return false;
            }
            return { goal_id: id, goal_name: name, target_amount: target, deadline: deadline, category, theme_color, priority };
        }
    });

    if (formValues) {
        const res = await fetch(`${API_URL}?action=update_goal`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
            body: JSON.stringify(formValues)
        });
        const result = await res.json();
        if (result.status === 'success') {
            Swal.fire('Saved!', result.message, 'success');
            if (currentView === 'goals.php' || currentView === 'manage_goals.php') fetchGoals();
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
            if (currentView === 'goals.php' || currentView === 'manage_goals.php') fetchGoals();
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
                <div class="swal-field">
                    <label class="swal-label">Notes / Remarks (Optional)</label>
                    <input id="st-notes" type="text" class="theme-input-select swal-input" placeholder="e.g. Birthday savings, bonus deposit">
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
            const notes = document.getElementById('st-notes').value;
            if (!amt || amt <= 0) {
                Swal.showValidationMessage('Please enter a valid positive amount.');
                return false;
            }
            return { goal_id: goalId, amount: amt, type: type, date: date, notes: notes };
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
            if (currentView === 'goals.php') {
                fetchGoals().then(() => {
                    const goal = goals.find(g => g.id === goalId);
                    if (goal && parseFloat(goal.current_amount) >= parseFloat(goal.target_amount) && type === 'deposit') {
                        fireConfetti();
                    }
                });
            }
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    }
}

async function populateGoalFilter() {
    if (goals.length === 0) {
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

    if (loader) loader.style.display = 'block';
    if (table) table.style.display = 'none';
    if (emptyState) emptyState.style.display = 'none';

    try {
        const res = await fetch(`${API_URL}?action=get_history&goal_id=${goalId}`);
        const result = await res.json();
        if (loader) loader.style.display = 'none';
        
        if (result.status === 'success') {
            if (tbody) tbody.innerHTML = '';
            if (result.data.length > 0) {
                result.data.forEach(r => {
                    let typeHtml = r.type === 'deposit' ? 
                        `<span style="color:#10b981; font-weight:bold;"><i class="fas fa-arrow-down"></i> Deposit</span>` : 
                        `<span style="color:#ef4444; font-weight:bold;"><i class="fas fa-arrow-up"></i> Withdraw</span>`;
                    
                    let notesHtml = r.notes ? escapeHtml(r.notes) : `<span style="color:var(--text-muted); font-style:italic;">-</span>`;

                    let tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${new Date(r.transaction_date).toLocaleDateString()}</td>
                        <td>${escapeHtml(r.goal_name)}</td>
                        <td>${typeHtml}</td>
                        <td style="font-weight:bold; color: ${r.type==='deposit' ? '#10b981' : '#ef4444'}">${userCurrency}${parseFloat(r.amount).toFixed(2)}</td>
                        <td>${notesHtml}</td>
                    `;
                    if (tbody) tbody.appendChild(tr);
                });
                if (table) table.style.display = 'table';
            } else {
                if (emptyState) emptyState.style.display = 'block';
            }
        }
    } catch (e) {
        console.error(e);
        if (loader) loader.style.display = 'none';
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

function showSIPProjection(goalId) {
    const goal = goals.find(g => g.id === goalId);
    if (!goal) return;
    
    const current = parseFloat(goal.current_amount) || 0;
    
    const calculateFutureValue = (principal, rate, years) => {
        return principal * Math.pow(1 + rate / 100, years);
    };
    
    const rates = [6, 8, 12];
    const periods = [1, 3, 5, 10];
    
    let tableHtml = `
        <table class="dashboard-table" style="width:100%; font-size:0.85rem; border-collapse: collapse; margin-top:15px; background: rgba(255,255,255,0.01); border-radius: 8px; overflow: hidden;">
            <thead>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.03);">
                    <th style="padding:10px; text-align:left;">Rate</th>
                    <th style="padding:10px; text-align:right;">1 Yr</th>
                    <th style="padding:10px; text-align:right;">3 Yrs</th>
                    <th style="padding:10px; text-align:right;">5 Yrs</th>
                    <th style="padding:10px; text-align:right;">10 Yrs</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    rates.forEach(r => {
        tableHtml += `<tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
            <td style="padding:10px; font-weight:bold; color:var(--aurora-1); text-align:left;">${r}%</td>`;
        periods.forEach(p => {
            const fv = calculateFutureValue(current, r, p);
            tableHtml += `<td style="padding:10px; text-align:right;">${userCurrency}${fv.toFixed(0)}</td>`;
        });
        tableHtml += `</tr>`;
    });
    
    tableHtml += `</tbody></table>`;
    
    Swal.fire({
        title: 'Compound Growth Projection',
        html: `
            <div style="text-align:left; font-size:0.9rem; color:var(--text-secondary);">
                Your current savings of <strong>${userCurrency}${current.toFixed(2)}</strong> for <strong>${escapeHtml(goal.goal_name)}</strong> could grow to:
                ${tableHtml}
                <div style="font-size:0.75rem; color:var(--text-muted); margin-top:15px; font-style:italic; text-align:center;">
                    Calculated using annual compounding formula: A = P(1 + r)^t
                </div>
            </div>
        `,
        confirmButtonText: 'Got it!',
        confirmButtonColor: '#8b5cf6'
    });
}

function fireConfetti() {
    if (typeof confetti === 'function') {
        confetti({
            particleCount: 150,
            spread: 80,
            origin: { y: 0.6 }
        });
    } else {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js';
        script.onload = () => {
            confetti({
                particleCount: 150,
                spread: 80,
                origin: { y: 0.6 }
            });
        };
        document.head.appendChild(script);
    }
}

async function openEmergencyCalculator() {
    Swal.fire({
        title: 'Calculating Average Expenses...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const res = await fetch(`${API_URL}?action=get_average_expense`);
        const data = await res.json();
        Swal.close();

        if (data.status !== 'success') {
            Swal.fire('Error', 'Could not retrieve expense history.', 'error');
            return;
        }

        const avgExpense = parseFloat(data.avg_expense) || 0;

        if (avgExpense <= 0) {
            Swal.fire({
                title: 'No Expense History',
                text: 'We could not find any expense records to calculate your average monthly spending. Please enter your estimated monthly expenses below to calculate your ideal Emergency Fund.',
                input: 'number',
                inputPlaceholder: 'e.g. 15000',
                showCancelButton: true,
                confirmButtonText: 'Continue',
                confirmButtonColor: '#8b5cf6',
                inputValidator: (value) => {
                    if (!value || parseFloat(value) <= 0) {
                        return 'Please enter a valid monthly expense amount.';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    showEmergencyFundSetupModal(parseFloat(result.value));
                }
            });
        } else {
            showEmergencyFundSetupModal(avgExpense);
        }
    } catch (err) {
        Swal.fire('Error', 'Failed to communicate with API.', 'error');
    }
}

async function showEmergencyFundSetupModal(avgMonthlyExpense) {
    const { value: formValues } = await Swal.fire({
        title: 'Emergency Fund Planner',
        html: `
            <div class="swal-form-container">
                <div style="text-align:left; font-size:0.85rem; color:var(--text-secondary); margin-bottom:15px; background:rgba(6,182,212,0.08); border:1px solid rgba(6,182,212,0.2); padding:10px; border-radius:8px;">
                    Average Monthly Expense: <strong>${userCurrency}${avgMonthlyExpense.toFixed(2)}</strong>
                </div>
                <div class="swal-field">
                    <label class="swal-label" id="em-months-label">Coverage Period: 6 Month(s)</label>
                    <input id="em-months" type="range" min="1" max="24" step="1" value="6" style="width:100%; accent-color:var(--aurora-1);">
                    <div style="font-size:0.75rem; color:var(--text-muted); margin-top:5px;">Typically, 3 to 6 months of expenses are recommended for financial security.</div>
                </div>
                <div class="swal-field" style="margin-top:15px;">
                    <label class="swal-label">Calculated Target Fund</label>
                    <input id="em-target" type="text" class="theme-input-select swal-input" style="text-align:center; font-weight:bold; color:#0ea5e9;" readonly>
                </div>
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Create Goal',
        confirmButtonColor: '#06b6d4',
        didOpen: () => {
            const monthsSlider = document.getElementById('em-months');
            const targetInput = document.getElementById('em-target');
            const label = document.getElementById('em-months-label');

            const calculateTarget = () => {
                const months = parseInt(monthsSlider.value) || 6;
                const total = avgMonthlyExpense * months;
                if (label) label.innerHTML = `Coverage Period: <strong>${months} Month(s)</strong> (${(months/12).toFixed(1)} Year(s))`;
                if (targetInput) targetInput.value = `${userCurrency}${total.toFixed(2)}`;
            };

            monthsSlider.addEventListener('input', calculateTarget);
            calculateTarget();
        },
        preConfirm: () => {
            const months = parseInt(document.getElementById('em-months').value) || 6;
            const total = avgMonthlyExpense * months;
            return {
                goal_name: `Emergency Fund (${months}M)`,
                target_amount: total,
                deadline: null,
                category: 'emergency',
                theme_color: 'emerald',
                priority: 'high'
            };
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
            Swal.fire('Created!', 'Emergency Fund goal has been created successfully.', 'success');
            if (currentView === 'goals.php' || currentView === 'manage_goals.php') fetchGoals();
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    }
}

const allCurrencies = [
    "₹", "৳", "$", "€", "£", "¥", "د.إ", "ر.س", "A$", "C$", "Fr", "kr", "R", "₽", "₺", "₩", "Rp", "฿", "₫", "₱", "₦",
    "AED", "AFN", "ALL", "AMD", "ANG", "AOA", "ARS", "AUD", "AWG", "AZN", "BAM", "BBD", "BDT", "BGN", "BHD", "BIF", "BMD", "BND", "BOB", "BRL", "BSD", "BTN", "BWP", "BYN", "BZD",
    "CAD", "CDF", "CHF", "CLP", "CNY", "COP", "CRC", "CUP", "CVE", "CZK", "DJF", "DKK", "DOP", "DZD", "EGP", "ERN", "ETB", "EUR",
    "FJD", "FKP", "GBP", "GEL", "GHS", "GIP", "GMD", "GNF", "GTQ", "GYD", "HKD", "HNL", "HRK", "HTG", "HUF", "IDR", "ILS", "INR", "IQD", "IRR", "ISK",
    "JMD", "JOD", "JPY", "KES", "KGS", "KHR", "KMF", "KRW", "KWD", "KYD", "KZT", "LAK", "LBP", "LKR", "LRD", "LSL", "LYD", "MAD", "MDL", "MGA", "MKD", "MMK", "MNT", "MOP", "MUR", "MVR", "MWK", "MXN", "MYR", "MZN",
    "NAD", "NGN", "NIO", "NOK", "NPR", "NZD", "OMR", "PAB", "PEN", "PGK", "PHP", "PKR", "PLN", "PYG", "QAR",
    "RON", "RSD", "RUB", "RWF", "SAR", "SBD", "SCR", "SDG", "SEK", "SGD", "SHP", "SLE", "SLL", "SOS", "SRD", "SSP", "STN", "SYP", "SZL",
    "THB", "TJS", "TMT", "TND", "TOP", "TRY", "TTD", "TWD", "TZS", "UAH", "UGX", "USD", "UYU", "UZS", "VES", "VND", "VUV", "WST",
    "XAF", "XCD", "XOF", "XPF", "YER", "ZAR", "ZMW", "ZWL"
];

function renderManageGoalsTable() {
    const tbody = document.getElementById('manageGoalsTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (!goals || goals.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No goals found. Create one first.</td></tr>';
        return;
    }

    goals.forEach(g => {
        const target = parseFloat(g.target_amount) || 0;
        const pClass = priorityClasses[g.priority || 'medium'] || 'priority-medium';
        const pLabel = (g.priority || 'medium').toUpperCase();
        const iconClass = categoryIcons[g.category || 'others'] || 'fas fa-rocket';
        const catName = categoryNames[g.category || 'others'] || 'Others';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <div style="display:flex; align-items:center; gap:10px;">
                    <div class="goal-cat-icon" style="width:30px; height:30px; font-size:0.9rem;"><i class="${iconClass}"></i></div>
                    <span style="font-weight:500;">${escapeHtml(g.goal_name)}</span>
                </div>
            </td>
            <td>${catName}</td>
            <td><span class="priority-badge ${pClass}">${pLabel}</span></td>
            <td style="color:var(--aurora-2); font-weight:600;">${userCurrency}${target.toFixed(2)}</td>
            <td style="text-align:right;">
                <div class="action-btns" style="justify-content:flex-end; gap:8px;">
                    <button class="icon-btn edit" title="Edit Goal" onclick="editGoal(${g.id})" style="background:rgba(139, 92, 246, 0.1); color:#a78bfa; border-color:rgba(139, 92, 246, 0.2);">
                        <i class="fas fa-pen"></i>
                    </button>
                    <button class="icon-btn delete" title="Delete Goal" onclick="deleteGoal(${g.id})">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

window.tempCurrency = userCurrency;
window.selCurr = function(el, val) {
    document.querySelectorAll('.curr-card').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    window.tempCurrency = val;
    const lbl = document.getElementById('selCurrLabel');
    if (lbl) lbl.textContent = val;
};

window.filterCurrencies = function(q) {
    const grid = document.getElementById('currencyGrid');
    if (!grid) return;
    const matches = q.trim()
        ? allCurrencies.filter(c => c.toLowerCase().includes(q.toLowerCase()))
        : allCurrencies.slice(0, 20);
    const list = matches.includes(window.tempCurrency) ? matches : [window.tempCurrency, ...matches];
    grid.innerHTML = list.map(c =>
        `<div class="set-card curr-card${window.tempCurrency === c ? ' active' : ''}" onclick="selCurr(this,'${c}')">${c}</div>`
    ).join('');
};

async function openSavSettings() {
    if (window.innerWidth <= 768) {
        const sidebar = document.getElementById('appSidebar');
        if (sidebar && sidebar.classList.contains('open')) toggleSidebar();
    }
    window.tempCurrency = userCurrency;
    const EXP_API_URL = '../../Exp/api/api.php';

    const defaultList = allCurrencies.slice(0, 20).includes(userCurrency)
        ? allCurrencies.slice(0, 20)
        : [userCurrency, ...allCurrencies.slice(0, 20)];

    const initialGrid = defaultList.map(c =>
        `<div class="set-card curr-card${userCurrency === c ? ' active' : ''}" onclick="selCurr(this,'${c}')">${c}</div>`
    ).join('');

    const currencySection = `
        <div style="text-align:left; margin-bottom:15px;">
            <label style="font-weight:600; color:var(--text-primary);">Regional Settings</label>
            <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:10px;">Search from 160+ currencies below</div>
            <input type="text" id="currencySearch" placeholder="🔍 Type to search (e.g. USD, ₹, EUR)..."
                class="theme-input-select"
                style="width:100%; box-sizing:border-box; margin-bottom:10px; font-size:0.9rem;"
                oninput="filterCurrencies(this.value)">
            <div id="currencyGrid" class="set-grid">${initialGrid}</div>
            <div style="font-size:0.75rem; color:var(--text-muted); margin-top:8px; text-align:right;">
                Selected: <strong id="selCurrLabel" style="color:var(--aurora-1);">${userCurrency}</strong>
            </div>
        </div>`;

    const { value: formValues } = await Swal.fire({
        title: 'Global Settings', width: 600,
        html: currencySection +
            `<hr style="border-color:rgba(255,255,255,0.1); margin:20px 0;"><div style="text-align:left; margin-bottom:15px;"><label style="font-weight:600; color:var(--text-primary);">Security</label><div style="margin-top:10px;"><button type="button" class="btn btn-ghost" style="width:100%; justify-content:flex-start; background:rgba(139, 92, 246, 0.1); border:1px solid rgba(139, 92, 246, 0.2);" onclick="Swal.close(); setTimeout(changePasswordModal, 300);"><i class="fas fa-key" style="color:var(--aurora-1);"></i> Change Account Password</button></div></div><hr style="border-color:rgba(239,68,68,0.3); margin:20px 0;"><div style="text-align:left; margin-bottom:15px;"><label style="font-weight:600; color:var(--danger);">Danger Zone</label><div style="margin-top:10px;"><button type="button" class="btn btn-danger" style="width:100%; justify-content:flex-start;" onclick="deleteMyAccount()"><i class="fas fa-trash-alt"></i> Permanently Delete My Account</button></div></div>`,
        focusConfirm: false, showCancelButton: true, confirmButtonText: 'Save Changes', confirmButtonColor: '#8b5cf6',
        didOpen: () => {
            const s = document.getElementById('currencySearch');
            if (s) setTimeout(() => s.focus(), 50);
        },
        preConfirm: () => { return { currency: window.tempCurrency, language: 'en' }; }
    });

    if (formValues) {
        const res = await fetch(`${EXP_API_URL}?action=update_settings`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN }, body: JSON.stringify(formValues) });
        const result = await res.json();
        if (result.status === 'success') {
            userCurrency = formValues.currency;
            Swal.fire('Saved', 'Settings saved successfully', 'success').then(() => {
                if (currentView === 'goals.php' || currentView === 'manage_goals.php') fetchGoals();
            });
        } else Swal.fire('Error', result.message, 'error');
    }
}

async function changePasswordModal() {
    const EXP_API_URL = '../../Exp/api/api.php';
    const { value: formValues } = await Swal.fire({
        title: 'Change Password',
        html: `<div class="swal-form-container">
            <div class="swal-field"><label class="swal-label">Current Password</label><input id="cp-old" type="password" class="theme-input-select swal-input" placeholder="Enter current password"></div>
            <div class="swal-field"><label class="swal-label">New Password</label><input id="cp-new" type="password" class="theme-input-select swal-input" placeholder="Enter new password"></div>
            <div style="text-align:right; margin-top:5px;"><a href="javascript:void(0)" onclick="event.preventDefault(); Swal.close(); forgotPassword('${EXP_API_URL}', CSRF_TOKEN, 'user', email);" style="color: var(--danger); font-size: 0.85rem; cursor:pointer;"><i class="fas fa-key"></i> Forgot Current Password?</a></div>
        </div>`,
        width: 380,
        focusConfirm: false, showCancelButton: true, confirmButtonText: 'Update Password', confirmButtonColor: '#10b981',
        preConfirm: () => {
            const oldp = document.getElementById('cp-old').value;
            const newp = document.getElementById('cp-new').value;
            if (!oldp || !newp) { Swal.showValidationMessage('Both fields are required!'); return false; }
            if (newp.length < 8) { Swal.showValidationMessage('New password must be at least 8 characters!'); return false; }
            return { oldp, newp };
        }
    });
    if (formValues) {
        const res = await fetch(`${EXP_API_URL}?action=change_password`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN }, body: JSON.stringify({ old_password: formValues.oldp, new_password: formValues.newp }) });
        const data = await res.json();
        if (data.status === 'success') Swal.fire('Success', 'Password changed successfully!', 'success');
        else Swal.fire('Error', data.message, 'error').then(() => changePasswordModal());
    }
}

async function forgotPassword(apiUrl, csrfToken, role, userEmail) {
    const confirmSend = await Swal.fire({
        title: 'Reset Password',
        text: `We will send a 6-digit verification OTP to your registered email: ${userEmail}.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Send OTP',
        confirmButtonColor: '#8b5cf6',
        cancelButtonText: 'Cancel'
    });

    if (!confirmSend.isConfirmed) return;

    Swal.fire({ title: 'Sending OTP...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
        const res = await fetch(`${apiUrl}?action=send_reset_otp`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken }
        });
        const result = await res.json();
        
        if (result.status !== 'success') {
            Swal.fire('Error', result.message || 'Failed to send OTP.', 'error');
            return;
        }

        const otpPrompt = await Swal.fire({
            title: 'Enter Verification OTP',
            html: `
                <div class="swal-form-container">
                    <p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:15px;">A 6-digit OTP has been sent to your email. Please enter it below to verify.</p>
                    <div class="swal-field">
                        <label class="swal-label">Verification OTP</label>
                        <input id="reset-otp" type="text" class="theme-input-select swal-input" placeholder="e.g. 123456" maxlength="6" pattern="\\d{6}">
                    </div>
                </div>
            `,
            width: 380,
            showCancelButton: true,
            confirmButtonText: 'Verify OTP',
            confirmButtonColor: '#8b5cf6',
            cancelButtonText: 'Cancel',
            focusConfirm: false,
            preConfirm: () => {
                const otp = document.getElementById('reset-otp').value.trim();
                if (!otp || !/^\d{6}$/.test(otp)) {
                    Swal.showValidationMessage('Please enter a valid 6-digit OTP');
                    return false;
                }
                return otp;
            }
        });

        if (!otpPrompt.isConfirmed) return;
        const otpValue = otpPrompt.value;

        Swal.fire({ title: 'Verifying...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        const verifyRes = await fetch(`${apiUrl}?action=verify_reset_otp`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            body: JSON.stringify({ otp: otpValue })
        });
        
        const verifyResult = await verifyRes.json();
        if (verifyResult.status !== 'success') {
            Swal.fire('Error', verifyResult.message || 'Invalid or expired OTP.', 'error');
            return;
        }

        const resetPrompt = await Swal.fire({
            title: 'Set New Password',
            html: `
                <div class="swal-form-container">
                    <p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:15px;">Please enter your new account password.</p>
                    <div class="swal-field">
                        <label class="swal-label">New Password</label>
                        <input id="new-pwd" type="password" class="theme-input-select swal-input" placeholder="Enter new password">
                    </div>
                    <div class="swal-field">
                        <label class="swal-label">Confirm New Password</label>
                        <input id="conf-new-pwd" type="password" class="theme-input-select swal-input" placeholder="Confirm new password">
                    </div>
                </div>
            `,
            width: 380,
            showCancelButton: true,
            confirmButtonText: 'Reset Password',
            confirmButtonColor: '#10b981',
            cancelButtonText: 'Cancel',
            focusConfirm: false,
            preConfirm: () => {
                const pwd = document.getElementById('new-pwd').value;
                const confPwd = document.getElementById('conf-new-pwd').value;
                if (!pwd || !confPwd) {
                    Swal.showValidationMessage('Both fields are required!');
                    return false;
                }
                if (pwd !== confPwd) {
                    Swal.showValidationMessage('Passwords do not match!');
                    return false;
                }
                if (pwd.length < 8) {
                    Swal.showValidationMessage('Password must be at least 8 characters long!');
                    return false;
                }
                if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^A-Za-z\\d]).{8,}$/.test(pwd)) {
                    Swal.showValidationMessage('Must have 1 uppercase, 1 lowercase, 1 number & 1 special character.');
                    return false;
                }
                return pwd;
            }
        });

        if (!resetPrompt.isConfirmed) return;
        const newPassword = resetPrompt.value;

        Swal.fire({ title: 'Updating password...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        const resetRes = await fetch(`${apiUrl}?action=reset_password_with_otp`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            body: JSON.stringify({ new_password: newPassword })
        });
        const resetResult = await resetRes.json();
        if (resetResult.status === 'success') {
            await Swal.fire('Success', 'Password has been updated successfully!', 'success');
        } else {
            Swal.fire('Error', resetResult.message || 'Failed to update password.', 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'An error occurred during password reset.', 'error');
    }
}

async function deleteMyAccount() {
    const EXP_API_URL = '../../Exp/api/api.php';
    const { value: password } = await Swal.fire({
        title: 'Delete Account?',
        html: `<div class="swal-form-container"><p style="color:#ef4444; font-weight:bold; margin-bottom:10px;">WARNING: This will permanently delete your account and ALL data.</p><div class="swal-field"><label class="swal-label">Enter your password to confirm</label><input id="del-pwd" type="password" class="theme-input-select swal-input" placeholder="Enter password"></div></div>`,
        width: 380,
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Delete Everything',
        focusConfirm: false,
        preConfirm: () => {
            const v = document.getElementById('del-pwd').value;
            if (!v) { Swal.showValidationMessage('Password is required!'); return false; }
            return v;
        }
    });
    if (!password) return;
    const res = await fetch(`${EXP_API_URL}?action=delete_user_account`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN }, body: JSON.stringify({ password }) });
    const data = await res.json();
    if (data.status === 'success') {
        await Swal.fire('Deleted!', 'Your account has been deleted.', 'success');
        window.location.href = '../../index.php';
    } else Swal.fire('Error', data.message, 'error');
}
