// assets/js/charts.js
// Phase 7: Full Integration — fetches live data from Exp & Sav summary APIs

Chart.defaults.color = 'rgba(255, 255, 255, 0.6)';
Chart.defaults.font.family = 'Inter, sans-serif';
Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.08)';

let combinedChart = null;
let donutChart = null;
const CURRENCY = '₹'; // Fallback; overridden by API data

document.addEventListener('DOMContentLoaded', () => {
    initCharts();
    loadDashboardData();
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

async function loadDashboardData() {
    const refreshIcon = document.getElementById('refreshIcon');
    if (refreshIcon) {
        refreshIcon.classList.add('fa-spin');
    }

    try {
        const res = await fetch('../api/dashboard_api.php');
        const result = await res.json().catch(() => null);
        const data = result?.status === 'success' ? result.data : null;

        // ── Exp Stat Pills ──────────────────────────────────────────────
        const currency = result?.currency || CURRENCY;
        const budget   = data ? data.total_budget : null;
        const spent    = data ? data.total_spent  : null;
        const balance  = (budget !== null && spent !== null) ? budget - spent : null;

        setStatPill('expBudgetVal', budget, currency);
        setStatPill('expSpentVal',  spent,  currency, false);
        setStatPill('expBalanceVal', balance, currency, true);

        // ── Sav Stat Pill ───────────────────────────────────────────────
        const totalSaved = data ? data.total_saved : null;
        setStatPill('savTotalVal', totalSaved, currency, false, '#06b6d4');

        // ── Update Donut Chart ──────────────────────────────────────────
        if (data && data.breakdown && data.breakdown.length > 0) {
            const donutEmpty = document.getElementById('donutEmpty');
            if (donutEmpty) donutEmpty.style.display = 'none';

            const palette = [
                'rgba(139, 92, 246, 0.85)', 'rgba(239, 68, 68, 0.85)',
                'rgba(245, 158, 11, 0.85)', 'rgba(16, 185, 129, 0.85)',
                'rgba(6, 182, 212, 0.85)',   'rgba(236, 72, 153, 0.85)',
                'rgba(59, 130, 246, 0.85)',  'rgba(251, 191, 36, 0.85)'
            ];
            donutChart.data.labels   = data.breakdown.map(b => b.category_name);
            donutChart.data.datasets[0].data            = data.breakdown.map(b => parseFloat(b.spent));
            donutChart.data.datasets[0].backgroundColor = data.breakdown.map((_, i) => palette[i % palette.length]);
            donutChart.data.datasets[0].borderWidth     = 2;
            donutChart.data.datasets[0].borderColor     = 'rgba(10,10,20,0.6)';
            donutChart.update('active');
        } else if (donutChart) {
            const donutEmpty = document.getElementById('donutEmpty');
            if (donutEmpty) donutEmpty.style.display = 'block';
            donutChart.data.labels = ['No Data'];
            donutChart.data.datasets[0].data = [100];
            donutChart.data.datasets[0].backgroundColor = ['rgba(255,255,255,0.05)'];
            donutChart.update();
        }

        // ── Update Combined Bar Chart ───────────────────────────────────
        // Build a unified month map over last 6 months
        const now = new Date();
        const monthLabels = [];
        for (let i = 5; i >= 0; i--) {
            const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
            monthLabels.push(`${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`);
        }

        // Map API monthly arrays to the labels
        const expMonthly = data?.exp_monthly || [];
        const savMonthly = data?.sav_monthly || [];

        const expMap = Object.fromEntries(expMonthly.map(r => [r.month, parseFloat(r.total_spent)]));
        const savMap = Object.fromEntries(savMonthly.map(r => [r.month, parseFloat(r.net_saved)]));

        const expValues = monthLabels.map(m => expMap[m] || 0);
        const savValues = monthLabels.map(m => savMap[m] || 0);

        // Pretty labels: "May '26"
        const prettyLabels = monthLabels.map(m => {
            const [y, mo] = m.split('-');
            return new Date(y, mo - 1).toLocaleString('default', { month: 'short' }) + ` '${y.slice(2)}`;
        });

        combinedChart.data.labels = prettyLabels;
        combinedChart.data.datasets[0].data = expValues;
        combinedChart.data.datasets[1].data = savValues;
        combinedChart.update('active');

        // Make chart clickable to redirect to that month
        if (combinedChart.options.onClick === undefined) {
            combinedChart.options.onClick = (e, activeEls) => {
                if (activeEls.length > 0) {
                    const idx = activeEls[0].index;
                    const monthRaw = monthLabels[idx]; // YYYY-MM
                    window.location.href = `../Exp/dashboard.php?month=${monthRaw}`;
                }
            };
            combinedChart.options.onHover = (e, activeEls) => {
                e.native.target.style.cursor = activeEls.length > 0 ? 'pointer' : 'default';
            };
            combinedChart.update();
        }

        const curMonthLabel = document.getElementById('chartMonthLabel');
        if (curMonthLabel) curMonthLabel.textContent = `(Last 6 Months)`;

        // ── Populate Month-wise Summary Table ───────────────────────────
        const tbody = document.getElementById('summaryTableBody');
        if (tbody) {
            tbody.innerHTML = '';
            // Render from newest to oldest
            const reversedLabels = [...monthLabels].reverse();
            const reversedPretty = [...prettyLabels].reverse();
            
            reversedLabels.forEach((m, idx) => {
                const tr = document.createElement('tr');
                tr.style.cursor = 'pointer';
                tr.title = `Click to view details for ${reversedPretty[idx]}`;
                tr.onclick = () => { window.location.href = `../Exp/dashboard.php?month=${m}`; };
                
                // If it's the current month, show the actual budget, else '-'
                const isCurrentMonth = (idx === 0); 
                const budgetStr = (isCurrentMonth && budget !== null) ? `${currency}${Number(budget).toFixed(2)}` : `<span style="color:var(--text-muted)">-</span>`;
                
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

        // ── Populate Recent Transactions ──────────────────────────────
        const txList = document.getElementById('recentTransactionsList');
        if (txList) {
            if (data && data.recent_transactions && data.recent_transactions.length > 0) {
                txList.innerHTML = '';
                data.recent_transactions.forEach(tx => {
                    // Formatting time if entry_date includes it, or just use date
                    const d = new Date(tx.entry_date);
                    const formattedDate = d.toLocaleDateString('default', { month: 'short', day: 'numeric', year: 'numeric' });
                    
                    const div = document.createElement('div');
                    div.className = 'tx-item';
                    div.innerHTML = `
                        <div class="tx-info">
                            <span class="tx-desc">${tx.description ? tx.description : '<i>No Description</i>'}</span>
                            <div class="tx-meta">
                                <span><i class="far fa-calendar-alt"></i> ${formattedDate}</span>
                                <span class="tx-cat">${tx.category_name}</span>
                            </div>
                        </div>
                        <div class="tx-amount">
                            -${currency}${Number(tx.amount).toFixed(2)}
                        </div>
                    `;
                    txList.appendChild(div);
                });
            } else {
                txList.innerHTML = `<div style="text-align:center; padding:2rem; color:var(--text-muted);">
                    <i class="fas fa-receipt fa-2x" style="margin-bottom:1rem; opacity:0.5;"></i><br>
                    No recent transactions found.
                </div>`;
            }
        }

    } catch (e) {
        console.error('Dashboard data load failed:', e);
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
