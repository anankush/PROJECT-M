/* ============================================================
   PROJECT M — Dashboard JS
   Sidebar toggle, Chart.js render, logout
   ============================================================ */

// ── Sidebar ───────────────────────────────────────────────────
function toggleSidebar() {
    document.getElementById('appSidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('open');
}

// ── Logout ────────────────────────────────────────────────────
async function doLogout() {
    try {
        await fetch(ROOT_BASE_URL + 'auth_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'logout', csrf_token: CSRF_TOKEN }),
            keepalive: true,
        });
    } finally {
        window.location.href = ROOT_BASE_URL + 'login.php';
    }
}

// ── Chart.js — 6-month financial overview ────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('financeChart')?.getContext('2d');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [
                {
                    label: 'Expenses (₹)',
                    data: expenseData,
                    borderColor: '#ec4899',
                    backgroundColor: 'rgba(236,72,153,0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#ec4899',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                },
                {
                    label: 'Savings (₹)',
                    data: savingsData,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#10b981',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { color: '#94a3b8', font: { family: 'Inter', size: 12 } },
                },
                tooltip: {
                    backgroundColor: 'rgba(15,15,25,0.9)',
                    titleColor: '#f1f5f9',
                    bodyColor: '#94a3b8',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    padding: 12,
                    callbacks: {
                        label: ctx => ` ₹${Number(ctx.parsed.y).toLocaleString('en-IN', { minimumFractionDigits: 2 })}`,
                    },
                },
            },
            scales: {
                x: {
                    grid:  { color: 'rgba(255,255,255,0.05)' },
                    ticks: { color: '#94a3b8', font: { family: 'Inter', size: 11 } },
                },
                y: {
                    grid:  { color: 'rgba(255,255,255,0.05)' },
                    ticks: {
                        color: '#94a3b8', font: { family: 'Inter', size: 11 },
                        callback: v => '₹' + Number(v).toLocaleString('en-IN'),
                    },
                    beginAtZero: true,
                },
            },
        },
    });
});
