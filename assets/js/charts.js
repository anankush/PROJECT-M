


Chart.defaults.color = 'rgba(255, 255, 255, 0.6)';
Chart.defaults.font.family = 'Outfit, Inter, sans-serif';
Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.08)';

let combinedChart = null;
let donutChart = null;
const CURRENCY = '₹';

let dashboardCategories = [];
let dashboardGoals = [];
let currentSelectedMonth = 'all';

document.addEventListener('DOMContentLoaded', () => {
    initCharts();
    loadDashboardData(currentSelectedMonth);
});

function initCharts() {
    const combinedCtx = document.getElementById('combinedChart');
    const donutCtx = document.getElementById('expenseDonutChart');

    if (combinedCtx) {
        combinedChart = new Chart(combinedCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Expenses',
                        data: [],
                        backgroundColor: 'rgba(239, 68, 68, 0.45)',
                        borderColor: 'rgba(239, 68, 68, 0.9)',
                        borderWidth: 2,
                        borderRadius: 6,
                        borderSkipped: false,
                        order: 2
                    },
                    {
                        label: 'Net Saved',
                        data: [],
                        backgroundColor: 'rgba(16, 185, 129, 0.45)',
                        borderColor: 'rgba(16, 185, 129, 0.9)',
                        borderWidth: 2,
                        borderRadius: 6,
                        borderSkipped: false,
                        order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        labels: { padding: 20, usePointStyle: true }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.dataset.label}: ${CURRENCY}${Number(ctx.raw).toFixed(2)}`
                        }
                    }
                },
                scales: {
                    x: { grid: { color: 'rgba(255,255,255,0.05)' } },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        ticks: {
                            callback: val => `${CURRENCY}${val}`
                        }
                    }
                }
            }
        });
    }

    if (donutCtx) {
        donutChart = new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: ['Loading...'],
                datasets: [{
                    data: [100],
                    backgroundColor: ['rgba(255,255,255,0.08)'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 12, usePointStyle: true, boxWidth: 10 }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.label}: ${CURRENCY}${Number(ctx.raw).toFixed(2)}`
                        }
                    }
                }
            }
        });
    }
}

async function loadDashboardData(selectedMonth = 'all') {
    const refreshIcon = document.getElementById('refreshIcon');
    if (refreshIcon) {
        refreshIcon.classList.add('fa-spin');
    }

    try {
        const res = await fetch(`../api/dashboard_api.php?month=${encodeURIComponent(selectedMonth)}`);
        const result = await res.json().catch(() => null);
        const data = result?.status === 'success' ? result.data : null;


        dashboardCategories = data?.categories || [];
        dashboardGoals = data?.goals || [];


        const currency = result?.currency || CURRENCY;
        const overallBudget = data ? data.overall_budget : null;
        const spent = data ? data.total_spent : null;
        const monthlyRemaining = data ? data.monthly_remaining : null;
        const netWorth = data ? data.net_worth : null;
        const lifetimeSpent = data ? data.lifetime_spent : null;
        const lifetimeSaved = data ? data.lifetime_saved : null;
        const monthlySaved = data ? data.monthly_saved : null;

        setStatPill('expBudgetVal', overallBudget, currency);
        setStatPill('expSpentVal', spent, currency, false);
        setStatPill('monthlyRemainingVal', monthlyRemaining, currency, true);
        setStatPill('netWorthVal', netWorth, currency, true);
        setStatPill('lifetimeSpentVal', lifetimeSpent, currency, false);
        setStatPill('lifetimeSavedVal', lifetimeSaved, currency, false, '#06b6d4');
        setStatPill('monthlySavedVal', monthlySaved, currency, true);

        const scoreEl = document.getElementById('healthScoreVal');
        if (scoreEl) {
            const score = data ? data.health_score : 100;
            const scoreColor = score >= 80 ? '#10b981' : score >= 50 ? '#f59e0b' : '#ef4444';
            scoreEl.innerHTML = `<span style="color:${scoreColor}">${score} <span style="font-size:0.8rem; font-weight:500; color:var(--text-muted);">/ 100</span></span>`;
        }


        const goalsList = document.getElementById('goalsProgressList');
        if (goalsList) {
            const goals = data?.goals || [];
            if (goals.length > 0) {
                goalsList.innerHTML = '';
                goals.forEach(g => {
                    const current = parseFloat(g.current_amount);
                    const target = parseFloat(g.target_amount);
                    const pct = Math.min(100, Math.round((current / target) * 100));

                    const div = document.createElement('div');
                    div.className = 'dash-goal-item';
                    div.innerHTML = `
                        <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.85rem; font-weight:600; margin-bottom:2px;">
                            <span style="color:var(--text-primary);"><i class="fas fa-dot-circle" style="color: ${getThemeColorHex(g.theme_color)}; margin-right:6px;"></i>${escapeHtml(g.goal_name)}</span>
                            <span style="color:${getThemeColorHex(g.theme_color)};">${pct}%</span>
                        </div>
                        <div class="dash-progress-container">
                            <div class="dash-progress-bar" style="width: ${pct}%; background: ${getThemeGradient(g.theme_color)};"></div>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:0.75rem; color:var(--text-secondary);">
                            <span>Saved: ${currency}${current.toFixed(2)}</span>
                            <span>Target: ${currency}${target.toFixed(2)}</span>
                        </div>
                    `;
                    goalsList.appendChild(div);
                });
            } else {
                goalsList.innerHTML = `<div style="text-align:center; padding:2rem; color:var(--text-muted);">
                    <i class="fas fa-bullseye fa-2x" style="margin-bottom:0.5rem; opacity:0.5;"></i><br>
                    No active savings goals found.
                </div>`;
            }
        }


        const deadlinesList = document.getElementById('upcomingDeadlinesList');
        if (deadlinesList) {
            const goals = data?.goals || [];
            const now = new Date();
            now.setHours(0, 0, 0, 0);

            const upcoming = goals.filter(g => {
                const current = parseFloat(g.current_amount);
                const target = parseFloat(g.target_amount);
                return (current < target) && g.deadline;
            }).map(g => {
                const dl = new Date(g.deadline);
                dl.setHours(0, 0, 0, 0);
                const diffTime = dl - now;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                return { ...g, diffDays };
            }).filter(g => g.diffDays <= 30)
                .sort((a, b) => a.diffDays - b.diffDays);

            if (upcoming.length > 0) {
                deadlinesList.innerHTML = '';
                upcoming.forEach(g => {
                    let badgeColor = 'var(--text-muted)';
                    let badgeText = '';
                    if (g.diffDays < 0) {
                        badgeColor = '#ef4444';
                        badgeText = `Overdue ${Math.abs(g.diffDays)}d`;
                    } else if (g.diffDays === 0) {
                        badgeColor = '#f59e0b';
                        badgeText = 'Today';
                    } else {
                        badgeColor = g.diffDays <= 7 ? '#f59e0b' : '#3b82f6';
                        badgeText = `${g.diffDays}d left`;
                    }

                    const formattedDate = new Date(g.deadline).toLocaleDateString('default', { month: 'short', day: 'numeric' });

                    const div = document.createElement('div');
                    div.className = 'dash-deadline-item';
                    div.innerHTML = `
                        <div style="font-size:1rem; color:${badgeColor};"><i class="fas fa-exclamation-circle"></i></div>
                        <div style="display:flex; flex-direction:column; min-width:0; flex:1;">
                            <span style="font-size:0.85rem; font-weight:600; color:var(--text-primary); text-overflow:ellipsis; overflow:hidden; white-space:nowrap;">${escapeHtml(g.goal_name)}</span>
                            <span style="font-size:0.75rem; color:var(--text-muted);">Deadline: ${formattedDate}</span>
                        </div>
                        <span style="background:${badgeColor}15; color:${badgeColor}; border:1px solid ${badgeColor}30; font-size:0.65rem; padding: 2px 6px; border-radius: 12px; font-weight: 700; white-space:nowrap;">${badgeText}</span>
                    `;
                    deadlinesList.appendChild(div);
                });
            } else {
                deadlinesList.innerHTML = `<div style="text-align:center; padding:2rem; color:var(--text-muted);">
                    <i class="fas fa-calendar-check fa-2x" style="margin-bottom:0.5rem; opacity:0.5;"></i><br>
                    No deadlines within 30 days.
                </div>`;
            }
        }


        if (data && data.breakdown && data.breakdown.length > 0) {
            const donutEmpty = document.getElementById('donutEmpty');
            if (donutEmpty) donutEmpty.style.display = 'none';

            const palette = [
                'rgba(139, 92, 246, 0.85)', 'rgba(239, 68, 68, 0.85)',
                'rgba(245, 158, 11, 0.85)', 'rgba(16, 185, 129, 0.85)',
                'rgba(6, 182, 212, 0.85)', 'rgba(236, 72, 153, 0.85)',
                'rgba(59, 130, 246, 0.85)', 'rgba(251, 191, 36, 0.85)'
            ];
            donutChart.data.labels = data.breakdown.map(b => b.category_name);
            donutChart.data.datasets[0].data = data.breakdown.map(b => parseFloat(b.spent));
            donutChart.data.datasets[0].categoryIds = data.breakdown.map(b => parseInt(b.category_id));
            donutChart.data.datasets[0].backgroundColor = data.breakdown.map((_, i) => palette[i % palette.length]);
            donutChart.data.datasets[0].borderWidth = 2;
            donutChart.data.datasets[0].borderColor = 'rgba(10,10,20,0.6)';

            if (donutChart.options.onClick === undefined) {
                donutChart.options.onClick = (e, activeEls) => {
                    if (activeEls.length > 0) {
                        const idx = activeEls[0].index;
                        const catId = donutChart.data.datasets[0].categoryIds[idx];
                        if (catId) {
                            navigateSecurely('exp', catId);
                        }
                    }
                };
                donutChart.options.onHover = (e, activeEls) => {
                    e.native.target.style.cursor = activeEls.length > 0 ? 'pointer' : 'default';
                };
            }
            donutChart.update('active');
        } else if (donutChart) {
            const donutEmpty = document.getElementById('donutEmpty');
            if (donutEmpty) donutEmpty.style.display = 'block';
            donutChart.data.labels = ['No Data'];
            donutChart.data.datasets[0].data = [100];
            donutChart.data.datasets[0].backgroundColor = ['rgba(255,255,255,0.05)'];
            donutChart.update();
        }


        const now = new Date();
        const monthLabels = [];
        for (let i = 5; i >= 0; i--) {
            const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
            monthLabels.push(`${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`);
        }

        const expMonthly = data?.exp_monthly || [];
        const savMonthly = data?.sav_monthly || [];

        const expMap = Object.fromEntries(expMonthly.map(r => [r.month, parseFloat(r.total_spent)]));
        const savMap = Object.fromEntries(savMonthly.map(r => [r.month, parseFloat(r.net_saved)]));

        const expValues = monthLabels.map(m => expMap[m] || 0);
        const savValues = monthLabels.map(m => savMap[m] || 0);

        const prettyLabels = monthLabels.map(m => {
            const [y, mo] = m.split('-');
            return new Date(y, mo - 1).toLocaleString('default', { month: 'short' }) + ` '${y.slice(2)}`;
        });


        const selectFilter = document.getElementById('dashboardMonthFilter');
        if (selectFilter && selectFilter.options.length <= 1) {
            monthLabels.forEach((m, idx) => {
                const opt = document.createElement('option');
                opt.value = m;
                opt.textContent = prettyLabels[idx];
                selectFilter.appendChild(opt);
            });
        }

        combinedChart.data.labels = prettyLabels;
        combinedChart.data.datasets[0].data = expValues;
        combinedChart.data.datasets[1].data = savValues;
        combinedChart.update('active');

        if (combinedChart.options.onClick === undefined) {
            combinedChart.options.onClick = (e, activeEls) => {
                if (activeEls.length > 0) {
                    const idx = activeEls[0].index;
                    const monthRaw = monthLabels[idx];
                    filterDashboardByMonth(monthRaw);
                }
            };
            combinedChart.options.onHover = (e, activeEls) => {
                e.native.target.style.cursor = activeEls.length > 0 ? 'pointer' : 'default';
            };
            combinedChart.update();
        }

        const curMonthLabel = document.getElementById('chartMonthLabel');
        if (curMonthLabel) {
            curMonthLabel.textContent = (selectedMonth === 'all') ? '(All-Time)' : `(${formatMonthYearLabel(selectedMonth)})`;
        }


        const tbody = document.getElementById('summaryTableBody');
        if (tbody) {
            tbody.innerHTML = '';
            const reversedLabels = [...monthLabels].reverse();
            const reversedPretty = [...prettyLabels].reverse();

            reversedLabels.forEach((m, idx) => {
                const tr = document.createElement('tr');
                tr.style.cursor = 'pointer';
                tr.title = `Click to filter details for ${reversedPretty[idx]}`;
                tr.onclick = () => { filterDashboardByMonth(m); };

                const isCurrentMonth = (idx === 0);
                const budgetStr = (isCurrentMonth && overallBudget !== null) ? `${currency}${Number(overallBudget).toFixed(2)}` : `<span style="color:var(--text-muted)">-</span>`;

                const expVal = expMap[m] || 0;
                const savVal = savMap[m] || 0;

                tr.innerHTML = `
                    <td style="font-weight:600; color:var(--aurora-2)">${reversedPretty[idx]}</td>
                    <td>${budgetStr}</td>
                    <td style="color:#ef4444">${currency}${expVal.toFixed(2)}</td>
                    <td style="color:#10b981">${currency}${savVal.toFixed(2)}</td>
                `;
                tbody.appendChild(tr);
            });
        }


        const txList = document.getElementById('recentTransactionsList');
        if (txList) {
            const txs = data?.recent_transactions || [];
            if (txs.length > 0) {
                txList.innerHTML = '';
                txs.forEach(tx => {
                    const d = new Date(tx.activity_date);
                    const formattedDate = d.toLocaleDateString('default', { month: 'short', day: 'numeric', year: 'numeric' });

                    let amtColor = '#ef4444';
                    let amtSign = '-';
                    let iconClass = 'fa-receipt';
                    let bgIcon = 'rgba(239, 68, 68, 0.15)';

                    if (tx.type === 'savings') {
                        if (tx.subtype === 'deposit') {
                            amtColor = '#10b981';
                            amtSign = '+';
                            iconClass = 'fa-piggy-bank';
                            bgIcon = 'rgba(16, 185, 129, 0.15)';
                        } else {
                            amtColor = '#f59e0b';
                            amtSign = '-';
                            iconClass = 'fa-hand-holding-usd';
                            bgIcon = 'rgba(245, 158, 11, 0.15)';
                        }
                    }

                    const div = document.createElement('div');
                    div.className = 'tx-item';
                    div.style.padding = '0.75rem 1rem';
                    div.innerHTML = `
                        <div style="display:flex; align-items:center; gap:12px; width:100%;">
                            <div style="width:38px; height:38px; border-radius:10px; background:${bgIcon}; color:${amtColor}; display:flex; align-items:center; justify-content:center; font-size:1.05rem; flex-shrink:0;">
                                <i class="fas ${iconClass}"></i>
                            </div>
                            <div class="tx-info" style="flex:1; min-width:0;">
                                <span class="tx-desc" style="text-overflow:ellipsis; overflow:hidden; white-space:nowrap; display:block;">${tx.description ? escapeHtml(tx.description) : '<i>No Description</i>'}</span>
                                <div class="tx-meta" style="margin-top:2px;">
                                    <span><i class="far fa-calendar-alt"></i> ${formattedDate}</span>
                                    <span class="tx-cat" style="background:${bgIcon}; color:${amtColor}; font-size:0.7rem; padding:1px 5px; border-radius:4px; font-weight:600;">${escapeHtml(tx.context)}</span>
                                </div>
                            </div>
                            <div class="tx-amount" style="color:${amtColor}; font-weight:700; white-space:nowrap; margin-left:10px; font-size:0.95rem;">
                                ${amtSign}${currency}${Number(tx.amount).toFixed(2)}
                            </div>
                        </div>
                    `;
                    txList.appendChild(div);
                });
            } else {
                txList.innerHTML = `<div style="text-align:center; padding:2rem; color:var(--text-muted);">
                    <i class="fas fa-receipt fa-2x" style="margin-bottom:0.5rem; opacity:0.5;"></i><br>
                    No recent transactions found.
                </div>`;
            }
        }

    } catch (e) {
    } finally {
        if (refreshIcon) refreshIcon.classList.remove('fa-spin');
    }
}

function setStatPill(elId, value, currency = '₹', isBalance = false, color = null) {
    const el = document.getElementById(elId);
    if (!el) return;
    if (value === null || value === undefined) {
        el.innerHTML = '<span style="color:var(--text-muted)">N/A</span>';
        return;
    }
    const formatted = `${currency}${Number(value).toFixed(2)}`;
    if (isBalance) {
        const c = value < 0 ? '#ef4444' : value > 0 ? '#10b981' : 'var(--text-muted)';
        el.innerHTML = `<span style="color:${c}">${formatted}</span>`;
    } else if (color) {
        el.innerHTML = `<span style="color:${color}">${formatted}</span>`;
    } else {
        el.textContent = formatted;
    }
}

function getThemeColorHex(theme) {
    switch (theme) {
        case 'purple': return '#8b5cf6';
        case 'emerald': return '#10b981';
        case 'sunset': return '#f43f5e';
        case 'ocean': return '#0ea5e9';
        default: return '#8b5cf6';
    }
}

function getThemeGradient(theme) {
    switch (theme) {
        case 'purple': return 'linear-gradient(90deg, #8b5cf6, #d946ef)';
        case 'emerald': return 'linear-gradient(90deg, #10b981, #34d399)';
        case 'sunset': return 'linear-gradient(90deg, #f43f5e, #f97316)';
        case 'ocean': return 'linear-gradient(90deg, #0ea5e9, #22d3ee)';
        default: return 'linear-gradient(90deg, #8b5cf6, #d946ef)';
    }
}


async function openQuickLog() {
    const { value: type } = await Swal.fire({
        title: 'Quick Log Entry',
        html: `
            <div style="margin-bottom: 10px; text-align: left;">
                <label style="font-weight:600; color:var(--text-primary); font-size:0.9rem;">Select Entry Type</label>
                <select id="quickLogType" class="theme-input-select swal-input" style="margin-top:6px; font-size:0.9rem; padding:8px 10px; height:40px;">
                    <option value="expense">Expense</option>
                    <option value="savings">Savings Deposit</option>
                </select>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Next <i class="fas fa-arrow-right"></i>',
        confirmButtonColor: '#8b5cf6',
        preConfirm: () => {
            return document.getElementById('quickLogType').value;
        }
    });

    if (!type) return;

    if (type === 'expense') {
        if (dashboardCategories.length === 0) {
            Swal.fire('No Categories', 'Please create an expense category first in the Expense panel.', 'warning');
            return;
        }

        const catOptions = dashboardCategories.map(c =>
            `<option value="${c.id}">${escapeHtml(c.category_name)}</option>`
        ).join('');

        const { value: expenseForm } = await Swal.fire({
            title: 'Log New Expense',
            html: `
                <div class="swal-form-container">
                    <div class="swal-field">
                        <label class="swal-label">Category</label>
                        <select id="ql-cat" class="theme-input-select swal-input">${catOptions}</select>
                    </div>
                    <div class="swal-field">
                        <label class="swal-label">Amount</label>
                        <input id="ql-amount" type="number" step="0.01" min="0.01" class="theme-input-select swal-input" placeholder="e.g. 150.00">
                    </div>
                    <div class="swal-field">
                        <label class="swal-label">Description</label>
                        <input id="ql-desc" type="text" class="theme-input-select swal-input" placeholder="e.g. Lunch at restaurant">
                    </div>
                    <div class="swal-field">
                        <label class="swal-label">Date</label>
                        <input id="ql-date" type="date" class="theme-input-select swal-input" value="${getLocalDateString()}">
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Log Expense',
            confirmButtonColor: '#ef4444',
            preConfirm: () => {
                const category_id = document.getElementById('ql-cat').value;
                const amount = parseFloat(document.getElementById('ql-amount').value);
                const description = document.getElementById('ql-desc').value;
                const date = document.getElementById('ql-date').value;

                if (isNaN(amount) || amount <= 0) {
                    Swal.showValidationMessage('Please enter a valid amount');
                    return false;
                }
                return { type: 'expense', category_id, amount, description, date };
            }
        });

        if (expenseForm) {
            submitQuickLog(expenseForm);
        }

    } else if (type === 'savings') {
        const activeGoals = dashboardGoals.filter(g => parseFloat(g.current_amount) < parseFloat(g.target_amount));
        if (activeGoals.length === 0) {
            Swal.fire('No Active Goals', 'Please create an active savings goal first in the Savings panel.', 'warning');
            return;
        }

        const goalOptions = activeGoals.map(g =>
            `<option value="${g.id}">${escapeHtml(g.goal_name)} (Target: ${CURRENCY}${g.target_amount})</option>`
        ).join('');

        const { value: savingsForm } = await Swal.fire({
            title: 'Log Goal Deposit',
            html: `
                <div class="swal-form-container">
                    <div class="swal-field">
                        <label class="swal-label">Select Savings Goal</label>
                        <select id="ql-goal" class="theme-input-select swal-input">${goalOptions}</select>
                    </div>
                    <div class="swal-field">
                        <label class="swal-label">Deposit Amount</label>
                        <input id="ql-amount" type="number" step="0.01" min="0.01" class="theme-input-select swal-input" placeholder="e.g. 500.00">
                    </div>
                    <div class="swal-field">
                        <label class="swal-label">Notes</label>
                        <input id="ql-notes" type="text" class="theme-input-select swal-input" placeholder="e.g. Monthly savings contribution">
                    </div>
                    <div class="swal-field">
                        <label class="swal-label">Date</label>
                        <input id="ql-date" type="date" class="theme-input-select swal-input" value="${getLocalDateString()}">
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Log Deposit',
            confirmButtonColor: '#10b981',
            preConfirm: () => {
                const goal_id = document.getElementById('ql-goal').value;
                const amount = parseFloat(document.getElementById('ql-amount').value);
                const notes = document.getElementById('ql-notes').value;
                const date = document.getElementById('ql-date').value;

                if (isNaN(amount) || amount <= 0) {
                    Swal.showValidationMessage('Please enter a valid amount');
                    return false;
                }
                return { type: 'savings', goal_id, amount, notes, date };
            }
        });

        if (savingsForm) {
            submitQuickLog(savingsForm);
        }
    }
}

async function submitQuickLog(payload) {
    Swal.fire({
        title: 'Saving entry...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const res = await fetch('../api/dashboard_api.php?action=quick_entry', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRF_TOKEN
            },
            body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.status === 'success') {
            Swal.fire({
                title: 'Logged!',
                text: result.message,
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                loadDashboardData(currentSelectedMonth);
            });
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    } catch (e) {
        Swal.fire('Error', 'Failed to log entry to server.', 'error');
    }
}


function filterDashboardByMonth(month) {
    currentSelectedMonth = month;
    const selectFilter = document.getElementById('dashboardMonthFilter');
    if (selectFilter) selectFilter.value = month;

    const resetBtn = document.getElementById('resetFilterBtn');
    if (resetBtn) resetBtn.style.display = (month === 'all') ? 'none' : 'inline-flex';

    const curMonthLabel = document.getElementById('chartMonthLabel');
    if (curMonthLabel) {
        curMonthLabel.textContent = (month === 'all') ? '(All-Time)' : `(${formatMonthYearLabel(month)})`;
    }

    loadDashboardData(month);
}

function resetDashboardFilter() {
    filterDashboardByMonth('all');
}

function formatMonthYearLabel(monthStr) {
    if (!monthStr || monthStr === 'all') return 'All-Time';
    const [y, mo] = monthStr.split('-');
    return new Date(y, mo - 1).toLocaleString('default', { month: 'short' }) + ` '${y.slice(2)}`;
}

async function navigateSecurely(module) {
    try {
        const res = await fetch(`../api/dashboard_api.php?action=generate_ott&module=${module}`);
        const result = await res.json().catch(() => null);
        if (result && result.status === 'success') {
            const token = result.token;
            let targetUrl = '';
            if (module === 'exp') {
                targetUrl = `../Exp/dashboard.php?ott=${encodeURIComponent(token)}`;
            } else if (module === 'sav') {
                targetUrl = `../Sav/dashboard.php?ott=${encodeURIComponent(token)}`;
            }

            if (currentSelectedMonth && currentSelectedMonth !== 'all') {
                targetUrl += `&month=${encodeURIComponent(currentSelectedMonth)}`;
            }

            window.location.href = targetUrl;
        } else {
            Swal.fire('Security Error', 'Could not generate a secure access token. Please log in again.', 'error').then(() => {
                window.location.href = getLogoutUrl();
            });
        }
    } catch (e) {
        Swal.fire('Connection Error', 'Failed to communicate with secure gateway.', 'error');
    }
}
