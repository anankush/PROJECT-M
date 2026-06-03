
let currentCategoryId = null;
let currentCategoryName = '';
let categories = [];
let email = '';
let userCurrency = '₹';
let isManualLogout = false;
let totalBudget = 0.00;
let totalExpenditure = 0.00;

if (window.DataStore) {
    DataStore.subscribe((state) => {
        categories = state.categories || [];
        totalBudget = state.totalBudget || 0;
        totalExpenditure = state.totalExpenditure || 0;

        const tbd = document.getElementById('totalBudgetDisplay');
        if (tbd) tbd.innerHTML = `${userCurrency}${totalBudget.toFixed(2)}`;

        const ta = document.getElementById('totalAmount');
        if (ta) ta.innerHTML = `${userCurrency}${totalExpenditure.toFixed(2)}`;

        let balance = totalBudget - totalExpenditure;
        let balanceEl = document.getElementById('totalBalanceDisplay');
        if (balanceEl) {
            balanceEl.innerHTML = `${userCurrency}${balance.toFixed(2)}`;
            updateBalanceCard('totalBalanceBox', balanceEl, balance);
        }

        renderTabs();
        if (document.getElementById('budgetsTableBody')) renderBudgetsTable();
    });
}


function setElementDisplay(id, displayType) {
    const el = document.getElementById(id);
    if (el) el.style.display = displayType;
}

function escapeHtml(unsafe) {
    if (unsafe == null) return '';
    return String(unsafe)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
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


window.addEventListener('beforeunload', function (e) {
    if (!isManualLogout) {
        fetch(`${API_URL}?action=user_logout`, { method: 'POST', headers: { 'X-CSRF-Token': CSRF_TOKEN }, keepalive: true });
    }
});

function toggleSidebar() {
    document.getElementById('appSidebar').classList.toggle('open');
    document.querySelector('.sidebar-overlay').classList.toggle('open');
}

async function loadView(viewName) {
    window.currentViewName = viewName;
    try {
        const res = await fetch(viewName);
        if (!res.ok) throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        const html = await res.text();
        const mc = document.getElementById('main-content');
        if (mc) mc.innerHTML = html;
        initDashboard();
        if (window.innerWidth <= 768) {
            const sidebar = document.getElementById('appSidebar');
            if (sidebar && sidebar.classList.contains('open')) toggleSidebar();
        }
    } catch (e) {
        const mc = document.getElementById('main-content');
        if (mc) mc.innerHTML = `<div style="text-align:center; padding: 50px;"><i class="fas fa-exclamation-triangle fa-3x" style="color:var(--danger); margin-bottom: 20px;"></i><h2 style="color:white;">Failed to load module.</h2><p style="color:var(--text-muted);">Please try refreshing the page.</p></div>`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('main-content')) {
        loadView('view_expenses.php');
    }
});

async function handleMonthChange() {
    await applyMonthFilter();
    if (window.innerWidth <= 768 && !currentCategoryId) {
        const sidebar = document.getElementById('appSidebar');
        if (sidebar && !sidebar.classList.contains('open')) {
            toggleSidebar();
        }
    }
}

function initDashboard() {
    const urlParams = new URLSearchParams(window.location.search);
    const monthParam = urlParams.get('month');
    // Only pre-fill if coming from a month-specific link (e.g. dashboard chart click)
    const initialMonth = monthParam || null;
    const monthInput = document.getElementById('monthFilter');

    if (monthInput) {
        let defaultDate = initialMonth;
        if (!defaultDate && window.DataStore && window.DataStore.state.currentMonth) {
            defaultDate = window.DataStore.state.currentMonth;
        }
        if (!defaultDate) {
            const now = new Date();
            defaultDate = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
        }

        window.monthFlatpickr = flatpickr("#monthFilter", {
            plugins: [
                new monthSelectPlugin({
                    shorthand: true,
                    dateFormat: "Y-m",
                    altFormat: "F Y",
                    theme: "dark"
                })
            ],
            disableMobile: "true",
            defaultDate: defaultDate,
            onChange: function (selectedDates, dateStr, instance) {
                handleMonthChange();
            }
        });
    }

    // Otherwise leave blank — user selects month manually
    if (document.getElementById('budgetsTableBody')) {
        renderBudgetsTable();
    }
    document.addEventListener('wheel', function (e) {
        if (e.target.type === 'number') {
            e.preventDefault();
        }
    }, { passive: false });
    checkAuth();
}

function renderBudgetsTable() {
    const tbody = document.getElementById('budgetsTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';
    if (categories.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;">No sections found. Create one first.</td></tr>';
    } else {
        categories.forEach(cat => {
            const tr = document.createElement('tr');
            const budgetVal = parseFloat(cat.budget) || 0;
            tr.innerHTML = `
                        <td>${escapeHtml(cat.category_name)}</td>
                        <td style="color:var(--aurora-2); font-weight:600;">${userCurrency}${budgetVal.toFixed(2)}</td>
                        <td style="text-align:right;">
                            <div class="action-btns" style="justify-content:flex-end;">
                                <button class="icon-btn edit" title="Rename" onclick="triggerRename(${cat.id}, '${escapeHtml(cat.category_name)}')">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button class="icon-btn edit" title="Set Budget" onclick="triggerEditBudget(${cat.id}, '${escapeHtml(cat.category_name)}')" style="background:rgba(6,182,212,0.1);color:#06b6d4;border-color:rgba(6,182,212,0.2);">
                                    <i class="fas fa-wallet"></i>
                                </button>
                                <button class="icon-btn edit" title="Notes" onclick="triggerNotes(${cat.id}, '${escapeHtml(cat.category_name)}')" style="background:rgba(245,158,11,0.1);color:#f59e0b;border-color:rgba(245,158,11,0.2);">
                                    <i class="far fa-sticky-note"></i>
                                </button>
                                <button class="icon-btn delete" title="Delete" onclick="deleteSpecificCategory(${cat.id}, '${escapeHtml(cat.category_name)}')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    `;
            tbody.appendChild(tr);
        });
    }

    // Render Overall Budget Cards
    const overallBudget = window.DataStore ? window.DataStore.state.overallBudget : 0;
    const allocatedBudget = categories.reduce((sum, cat) => sum + (parseFloat(cat.budget) || 0), 0);
    const unallocatedBudget = overallBudget - allocatedBudget;

    const overallDisplay = document.getElementById('overallBudgetDisplay');
    const allocatedDisplay = document.getElementById('allocatedBudgetDisplay');
    const unallocatedDisplay = document.getElementById('unallocatedBudgetDisplay');
    const unallocatedCard = document.getElementById('unallocatedBudgetCard');
    const unallocatedLabel = document.getElementById('unallocatedLabel');

    if (overallDisplay) {
        overallDisplay.innerText = `${userCurrency}${overallBudget.toFixed(2)}`;
    }
    if (allocatedDisplay) {
        allocatedDisplay.innerText = `${userCurrency}${allocatedBudget.toFixed(2)}`;
    }
    if (unallocatedDisplay) {
        unallocatedDisplay.innerText = `${userCurrency}${Math.abs(unallocatedBudget).toFixed(2)}`;
        if (unallocatedBudget < 0) {
            unallocatedDisplay.innerText = `-${userCurrency}${Math.abs(unallocatedBudget).toFixed(2)}`;
            if (unallocatedCard) {
                unallocatedCard.style.background = 'rgba(239, 68, 68, 0.1)';
                unallocatedCard.style.borderColor = 'rgba(239, 68, 68, 0.3)';
                unallocatedCard.style.color = '#ef4444';
            }
            if (unallocatedLabel) unallocatedLabel.innerText = 'Over-Allocated';
        } else {
            if (unallocatedCard) {
                unallocatedCard.style.background = 'rgba(16, 185, 129, 0.1)';
                unallocatedCard.style.borderColor = 'rgba(16, 185, 129, 0.3)';
                unallocatedCard.style.color = '#10b981';
            }
            if (unallocatedLabel) unallocatedLabel.innerText = 'Remaining to Allocate';
        }
    }
}

async function triggerEditOverallBudget() {
    const monthVal = document.getElementById('monthFilter') ? document.getElementById('monthFilter').value : '';
    if (!monthVal) {
        Swal.fire('Error', 'Please select a month first.', 'error');
        return;
    }

    const [y, m] = monthVal.split('-');
    const monthName = new Date(y, m - 1).toLocaleString('default', { month: 'long' });
    const currentOverall = window.DataStore ? window.DataStore.state.overallBudget : 0;

    const { value: newOverall } = await Swal.fire({
        title: `Set Overall Budget for ${monthName} ${y}`,
        html: `<div class="swal-form-container"><input id="overallBudgetInput" type="number" step="0.01" class="theme-input-select swal-input" value="${currentOverall === 0 ? '' : currentOverall}" placeholder="0.00" style="text-align:center;"></div>`,
        width: 380,
        showCancelButton: true,
        confirmButtonColor: '#8b5cf6',
        focusConfirm: false,
        didOpen: () => {
            const obInput = document.getElementById('overallBudgetInput');
            if (obInput) {
                obInput.focus();
                obInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        Swal.clickConfirm();
                    }
                });
            }
        },
        preConfirm: () => {
            const v = document.getElementById('overallBudgetInput').value;
            if (v === '') return 0; // allow setting to 0 / clearing
            if (isNaN(parseFloat(v)) || parseFloat(v) < 0) {
                Swal.showValidationMessage('Please enter a valid positive amount');
                return false;
            }
            return parseFloat(v);
        }
    });

    if (newOverall !== undefined && newOverall !== null) {
        const res = await fetch(`${API_URL}?action=update_overall_budget`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
            body: JSON.stringify({ month: monthVal, budget: newOverall })
        });
        const result = await res.json();
        if (result.status === 'success') {
            await fetchCategories(); // refresh data
            Swal.fire('Saved!', 'Overall monthly budget updated successfully.', 'success');
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    }
}

function triggerEditBudget(id, name) {
    currentCategoryId = id;
    currentCategoryName = name;
    editSectionBudget().then(() => {
        setTimeout(renderBudgetsTable, 500);
    });
}

function triggerRename(id, name) {
    currentCategoryId = id;
    currentCategoryName = name;
    renameSection().then(() => {
        setTimeout(renderBudgetsTable, 500);
    });
}

function triggerNotes(id, name) {
    currentCategoryId = id;
    currentCategoryName = name;
    openNoteModal();
}

async function applyMonthFilter() {
    const monthInput = document.getElementById('monthFilter');
    if (!monthInput) return;
    const monthVal = monthInput.value;

    if (monthVal) {
        const [y, m] = monthVal.split('-');
        const selectedDate = new Date(y, m - 1);
        const now = new Date();
        const currentMonthDate = new Date(now.getFullYear(), now.getMonth());

        if (selectedDate > currentMonthDate) {
            Swal.fire({ icon: 'warning', title: 'Hold up, Time Traveler!', text: 'You cannot manage expenses for the future.' });
            const currentYear = now.getFullYear();
            const currentMonthStr = String(now.getMonth() + 1).padStart(2, '0');
            const targetMonthVal = `${currentYear}-${currentMonthStr}`;
            if (window.monthFlatpickr) {
                window.monthFlatpickr.setDate(targetMonthVal, false);
            } else {
                monthInput.value = targetMonthVal;
            }
            await applyMonthFilter();
            return;
        }
    }

    const titleSpan = document.getElementById('currentTableTitle');
    const summaryTitle = document.getElementById('labelTotalBudget');
    const expTitle = document.getElementById('labelTotalExpenditure');
    const balTitle = document.getElementById('labelTotalBalance');

    if (monthVal) {
        const [y, m] = monthVal.split('-');
        const monthName = new Date(y, m - 1).toLocaleString('default', { month: 'long' });
        if (summaryTitle) summaryTitle.innerText = `${monthName} ${y} Budget`;
        if (expTitle) expTitle.innerText = `${monthName} ${y} Expenditure`;
        if (balTitle) balTitle.innerText = `${monthName} ${y} Remaining`;
        await fetchCategories();

        if (currentCategoryId) {
            loadCategory(currentCategoryId, currentCategoryName);
        } else {
            const urlParams = new URLSearchParams(window.location.search);
            const catParam = parseInt(urlParams.get('category_id'));
            const matchedCat = catParam ? categories.find(c => c.id === catParam) : null;
            if (matchedCat) {
                loadCategory(matchedCat.id, matchedCat.category_name);
            } else {
                if (titleSpan) titleSpan.innerText = 'Please select a Section';
                const addBtn = document.getElementById('addRecordBtn');
                if (addBtn) addBtn.style.display = 'none';
                const secActions = document.getElementById('sectionActions');
                if (secActions) secActions.style.display = 'none';
                if (document.getElementById('noteBtn')) setElementDisplay('noteBtn', 'none');
                const rb = document.getElementById('refreshBtn');
                if (rb) rb.classList.remove('show');
                const rbm = document.getElementById('refreshBtnMobile');
                if (rbm) rbm.classList.remove('show');
                const dt = document.getElementById('dataTable');
                if (dt) dt.style.display = 'none';
                const es = document.getElementById('emptyState');
                if (es) es.style.display = 'none';
                const srs = document.getElementById('sortRecordsSelect');
                if (srs) srs.style.display = 'none';
                const sbb = document.getElementById('sectionBudgetBox');
                if (sbb) sbb.style.display = 'none';
                const seb = document.getElementById('sectionExpenditureBox');
                if (seb) seb.style.display = 'none';
                const sbalb = document.getElementById('sectionBalanceBox');
                if (sbalb) sbalb.style.display = 'none';
            }
        }
    } else {
        if (summaryTitle) summaryTitle.innerText = 'Overall Budget';
        if (expTitle) expTitle.innerText = 'Overall Expenditure';
        if (balTitle) balTitle.innerText = 'Overall Remaining';
        if (titleSpan) titleSpan.innerText = 'Please select a Year and Month to view records or add new ones.';

        const addRecordBtn = document.getElementById('addRecordBtn');
        if (addRecordBtn) {
            addRecordBtn.style.display = 'none';
            const secAct = document.getElementById('sectionActions');
            if (secAct) secAct.style.display = 'none';
            if (document.getElementById('noteBtn')) setElementDisplay('noteBtn', 'none');
            document.getElementById('refreshBtn').classList.remove('show');
            document.getElementById('refreshBtnMobile').classList.remove('show');
            setElementDisplay('dataTable', 'none');
            setElementDisplay('emptyState', 'none');
            setElementDisplay('sortRecordsSelect', 'none');
            setElementDisplay('sectionBudgetBox', 'none');
            setElementDisplay('sectionExpenditureBox', 'none');
            setElementDisplay('sectionBalanceBox', 'none');
        }
        await fetchCategories();
    }
}

async function checkAuth() {
    try {
        const res = await fetch(`${API_URL}?action=check_session`);
        const data = await res.json();
        if (data.is_user) {
            email = data.email;
            userCurrency = data.currency;
            totalBudget = parseFloat(data.total_budget) || 0;
            document.getElementById('userNameDisplay').innerText = `Welcome, ${email}`;
            const tbd = document.getElementById('totalBudgetDisplay');
            if (tbd) tbd.innerHTML = `${userCurrency}${totalBudget.toFixed(2)}`;
            setElementDisplay('appUI', 'flex');
            if (document.getElementById('monthFilter')) {
                applyMonthFilter();
            } else if (window.currentViewName !== 'budgets.php') {
                await fetchCategories();
            }
        } else {
            window.location.href = '../../auth/login.php';
        }
    } catch (e) {
    }
}

async function logout() {
    isManualLogout = true;
    const confirm = await Swal.fire({
        title: 'Logout?',
        text: 'Are you sure you want to logout?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, Logout',
        cancelButtonText: 'Cancel'
    });
    if (confirm.isConfirmed) {
        await fetch(`${API_URL}?action=user_logout`, { method: 'POST', headers: { 'X-CSRF-Token': CSRF_TOKEN } });
        window.location.href = 'login.php';
    } else {
        isManualLogout = false;
    }
}

let fetchRequestId = 0;
async function fetchCategories() {
    try {
        const monthInput = document.getElementById('monthFilter');
        let monthVal = '';
        if (monthInput) monthVal = monthInput.value;
        if (window.DataStore) {
            await DataStore.fetchAllData(monthVal);
        }
    } catch (e) { }
}

function renderTabs() {
    const container = document.getElementById('categoryTabs');
    container.innerHTML = '';
    categories.forEach(cat => {
        const li = document.createElement('li');
        li.style.display = 'flex';
        li.style.alignItems = 'center';
        const safeName = escapeHtml(cat.category_name);
        li.innerHTML = `<button class="category-tab ${currentCategoryId === cat.id ? 'active' : ''}" style="flex:1;" onclick="loadCategory(${cat.id}, '${escapeHtml(cat.category_name)}')"><i class="fas fa-folder"></i> <span style="flex:1; text-align:left; overflow:hidden; text-overflow:ellipsis;">${safeName}</span></button>`;
        container.appendChild(li);
    });
}

async function fetchTotalExpenditure() {
    // Forward to fetchCategories to trigger a full DataStore update
    await fetchCategories();
}

function updateBalanceCard(boxId, el, balance) {
    const box = document.getElementById(boxId);
    if (balance < 0) {
        box.className = 'summary-card card-balance';
        el.style.color = 'var(--danger)';
    } else if (balance > 0) {
        box.className = 'summary-card card-balance positive';
        el.style.color = 'var(--success)';
    } else {
        box.className = 'summary-card card-balance zero';
        el.style.color = 'var(--text-muted)';
    }
}

async function loadCategory(id, name) {
    if (!document.getElementById('currentTableTitle')) {
        await loadView('view_expenses.php');
        return loadCategory(id, name);
    }
    currentCategoryId = id;
    currentCategoryName = name;
    window.currentCustomSchema = {};
    document.getElementById('currentTableTitle').innerText = name;
    const addBtn = document.getElementById('addRecordBtn');
    if (addBtn) addBtn.style.display = window.innerWidth <= 768 ? 'flex' : 'inline-flex';
    const secAct = document.getElementById('sectionActions');
    if (secAct) secAct.style.display = 'flex';
    if (document.getElementById('noteBtn')) setElementDisplay('noteBtn', 'inline-flex');
    const rBtn = document.getElementById('refreshBtn');
    if (rBtn) rBtn.classList.add('show');
    const rBtnM = document.getElementById('refreshBtnMobile');
    if (rBtnM) rBtnM.classList.add('show');
    renderTabs();

    if (window.innerWidth <= 768) {
        const sidebar = document.getElementById('appSidebar');
        if (sidebar && sidebar.classList.contains('open')) toggleSidebar();
    }

    const table = document.getElementById('dataTable');
    const emptyState = document.getElementById('emptyState');
    const loader = document.getElementById('tableLoader');

    table.style.display = 'none';
    emptyState.style.display = 'none';
    loader.style.display = 'block';

    try {
        let monthVal = document.getElementById('monthFilter').value;
        if (!monthVal) {
            const now = new Date();
            monthVal = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
            if (window.monthFlatpickr) {
                window.monthFlatpickr.setDate(monthVal, false);
            } else {
                document.getElementById('monthFilter').value = monthVal;
            }
            await applyMonthFilter();
            return;
        }
        const res = await fetch(`${API_URL}?action=get_records&category_id=${id}&month=${monthVal}`);
        const data = await res.json();
        loader.style.display = 'none';

        if (data.status === 'success') {
            try {
                const cumRes = await fetch(`${API_URL}?action=get_cumulative_stats&category_id=${id}&month=${monthVal}`);
                const cumData = await cumRes.json();
                if (cumData.status === 'success') {
                    totalBudget = parseFloat(cumData.cumulative_budget) || 0;
                    totalExpenditure = parseFloat(cumData.cumulative_expenditure) || 0;
                    document.getElementById('labelTotalBudget').innerText = `Total Budget (${name})`;
                    document.getElementById('labelTotalExpenditure').innerText = `Total Expenditure (${name})`;
                    document.getElementById('labelTotalBalance').innerText = `Total Remaining (${name})`;
                    document.getElementById('totalBudgetDisplay').innerHTML = `${userCurrency}${totalBudget.toFixed(2)}`;
                    document.getElementById('totalAmount').innerHTML = `${userCurrency}${totalExpenditure.toFixed(2)}`;
                    let balance = totalBudget - totalExpenditure;
                    let balanceEl = document.getElementById('totalBalanceDisplay');
                    balanceEl.innerHTML = `${userCurrency}${balance.toFixed(2)}`;
                    updateBalanceCard('totalBalanceBox', balanceEl, balance);
                }
            } catch (err) { }

            if (data.schema) window.currentCustomSchema = data.schema;

            let currentCat = categories.find(c => c.id === currentCategoryId);
            let secBudget = currentCat ? parseFloat(currentCat.budget) || 0 : 0;
            document.getElementById('sectionBudgetDisplay').innerHTML = `${userCurrency}${secBudget.toFixed(2)}`;

            setElementDisplay('sectionBudgetBox', 'block');
            setElementDisplay('sectionExpenditureBox', 'block');
            setElementDisplay('sectionBalanceBox', 'block');

            const [y, m] = monthVal.split('-');
            const mName = new Date(y, m - 1).toLocaleString('default', { month: 'short' });
            document.querySelector('#sectionBudgetBox .metric-label').innerHTML = `Section Budget (${mName} ${y}) <i class="fas fa-edit" style="cursor:pointer; font-size:0.8rem; margin-left:5px;" onclick="showBudgetManageInfo()" title="Edit Section Budget"></i>`;
            document.querySelector('#sectionExpenditureBox .metric-label').innerText = `Section Expenditure (${mName} ${y})`;
            document.querySelector('#sectionBalanceBox .metric-label').innerHTML = `Section Remaining (${mName} ${y}) <i class="fas fa-piggy-bank" id="sweepSavingsBtn" style="cursor:pointer; font-size:0.9rem; margin-left:8px; color:#10b981; display:none;" onclick="triggerSweepToSavings()" title="Sweep remaining to Savings Goal"></i>`;

            let secExp = 0;
            if (data.data.length > 0) {
                window.currentRecords = data.data;
                let totalRecords = window.currentRecords.length;
                window.currentRecords.forEach((r, idx) => { r.serial_no = totalRecords - idx; });
                setElementDisplay('sortRecordsSelect', 'inline-block');
                secExp = data.data.reduce((sum, r) => sum + (parseFloat(r.amount) || 0), 0);
                sortRecords();
                table.style.display = 'table';
            } else {
                window.currentRecords = [];
                setElementDisplay('sortRecordsSelect', 'none');
                emptyState.style.display = 'block';
            }

            document.getElementById('sectionAmount').innerHTML = `${userCurrency}${secExp.toFixed(2)}`;
            let secBal = secBudget - secExp;
            let secBalEl = document.getElementById('sectionBalanceDisplay');
            secBalEl.innerHTML = `${userCurrency}${secBal.toFixed(2)}`;
            secBalEl.style.color = secBal < 0 ? 'var(--danger)' : secBal > 0 ? 'var(--success)' : '#0ea5e9';

            const sweepBtn = document.getElementById('sweepSavingsBtn');
            if (sweepBtn) {
                if (secBal > 0) {
                    sweepBtn.style.display = 'inline-block';
                    sweepBtn.dataset.amount = secBal;
                    sweepBtn.dataset.section = name;
                } else {
                    sweepBtn.style.display = 'none';
                }
            }

            if (window.innerWidth <= 768) {
                const sidebar = document.getElementById('appSidebar');
                if (sidebar.classList.contains('open')) toggleSidebar();
            }
        }
    } catch (e) { loader.style.display = 'none'; }
}

function formatTime12Hour(timeStr) {
    if (!timeStr) return '';
    try {
        const parts = timeStr.split(':');
        if (parts.length < 2) return timeStr;
        let hours = parseInt(parts[0], 10);
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        return `${hours.toString().padStart(2, '0')}:${parts[1]} ${ampm}`;
    } catch (e) { return timeStr; }
}

function renderTableData(records) {
    const tbody = document.getElementById('tableBody');
    const thead = document.getElementById('tableHead');
    let customSchema = window.currentCustomSchema || {};
    let customKeys = Object.keys(customSchema);

    let headHtml = `<tr><th>Serial No</th><th>Date</th><th>Time</th><th>Amount</th><th>Description</th>`;
    customKeys.forEach(k => { headHtml += `<th>${k}</th>`; });
    headHtml += `<th>Timestamp</th><th style="text-align:right;">Actions</th></tr>`;
    thead.innerHTML = headHtml;

    tbody.innerHTML = '';
    records.forEach((row) => {
        const tr = document.createElement('tr');
        let rowHtml = `<td>${row.serial_no}</td><td>${escapeHtml(row.entry_date)}</td><td>${formatTime12Hour(row.entry_time)}</td><td style="color:var(--success); font-weight:600;">${userCurrency}${row.amount}</td><td>${escapeHtml(row.description)}</td>`;
        customKeys.forEach(k => {
            let val = '-';
            if (row.custom_data && row.custom_data[k] !== undefined) {
                if (typeof row.custom_data[k] === 'object' && row.custom_data[k] !== null) {
                    val = row.custom_data[k].value;
                    if (row.custom_data[k].type === 'currency' && val !== '') val = `<span style="color:var(--success); font-weight:500;">${userCurrency}${val}</span>`;
                    else if (row.custom_data[k].type === 'time' && val !== '') val = formatTime12Hour(val);
                    else val = escapeHtml(val);
                } else { val = escapeHtml(row.custom_data[k]); }
            }
            rowHtml += `<td>${val}</td>`;
        });
        rowHtml += `<td style="font-size:0.8rem; color:var(--text-muted);">${escapeHtml(row.created_at)}</td><td style="text-align:right;"><div class="action-btns" style="justify-content:flex-end;"><button class="icon-btn edit" onclick='editRecord(${JSON.stringify(row).replace(/'/g, "&#39;")})' title="Edit"><i class="fas fa-pen" style="font-size:0.85rem; pointer-events:none;"></i></button><button class="icon-btn delete" onclick="deleteRecord(${row.id})" title="Delete"><i class="fas fa-trash-alt" style="font-size:0.85rem; pointer-events:none;"></i></button></div></td>`;
        tr.innerHTML = rowHtml;
        tbody.appendChild(tr);
    });
}

function sortRecords() {
    if (!window.currentRecords) return;
    const sortType = document.getElementById('sortRecordsSelect').value;
    let sorted = [...window.currentRecords];
    if (sortType === 'newest') sorted.sort((a, b) => b.id - a.id);
    else if (sortType === 'oldest') sorted.sort((a, b) => a.id - b.id);
    else if (sortType === 'highest') sorted.sort((a, b) => parseFloat(b.amount) - parseFloat(a.amount));
    else if (sortType === 'lowest') sorted.sort((a, b) => parseFloat(a.amount) - parseFloat(b.amount));
    renderTableData(sorted);
}

let customFieldsCount = 0;

function addCustomFieldRow(key = '', val = '', readonly = false, type = 'text') {
    customFieldsCount++;
    const div = document.createElement('div');
    div.className = 'custom-field-row';
    div.id = `cf-row-${customFieldsCount}`;
    let inputType = type === 'currency' ? 'number' : type;
    let stepAttr = (type === 'currency' || type === 'number') ? 'step="0.01"' : '';

    if (readonly) {
        div.innerHTML = `<div class="swal-field"><label class="swal-label">${key} ${type === 'currency' ? `(${userCurrency})` : ''}</label><div style="display:flex; gap:10px; align-items:center;"><input type="hidden" class="cf-key" value="${key}"><input type="hidden" class="cf-type" value="${type}"><input type="${inputType}" ${stepAttr} class="cf-val theme-input-select swal-input" placeholder="Value" value="${val}" style="flex:1;"><i class="fas fa-times cf-remove" onclick="confirmRemoveCustomField('${div.id}')" style="cursor:pointer; color:var(--danger); font-size:1.2rem; padding:10px;"></i></div></div>`;
        document.getElementById('custom-fields-container').appendChild(div);
        const valEl = div.querySelector('.cf-val');
        if (type === 'date') {
            valEl.type = 'text';
            flatpickr(valEl, { altInput: true, altFormat: "d-m-Y", dateFormat: "Y-m-d", allowInput: false, defaultDate: val });
        } else if (type === 'time') {
            valEl.type = 'text';
            flatpickr(valEl, { enableTime: true, noCalendar: true, dateFormat: "h:i K", time_24hr: false, allowInput: false, defaultHour: 12, defaultDate: val });
        }
    } else {
        div.innerHTML = `<div class="swal-field"><div style="display:flex; gap:10px; align-items:center;"><input type="text" class="cf-key theme-input-select swal-input" placeholder="Field Name" value="${key}" style="flex:1;"><select class="cf-type theme-input-select swal-input" style="flex:1;"><option value="text" style="background:var(--bg-deep); color:var(--text-primary);" ${type === 'text' ? 'selected' : ''}>Text</option><option value="number" style="background:var(--bg-deep); color:var(--text-primary);" ${type === 'number' ? 'selected' : ''}>Number</option><option value="currency" style="background:var(--bg-deep); color:var(--text-primary);" ${type === 'currency' ? 'selected' : ''}>Currency</option><option value="date" style="background:var(--bg-deep); color:var(--text-primary);" ${type === 'date' ? 'selected' : ''}>Date</option><option value="time" style="background:var(--bg-deep); color:var(--text-primary);" ${type === 'time' ? 'selected' : ''}>Time</option></select><input type="${inputType}" ${stepAttr} class="cf-val theme-input-select swal-input" placeholder="Value" value="${val}" style="flex:1.5;"><i class="fas fa-times cf-remove" onclick="confirmRemoveCustomField('${div.id}')" style="cursor:pointer; color:var(--danger); font-size:1.2rem; padding:10px;"></i></div></div>`;
        document.getElementById('custom-fields-container').appendChild(div);
        const selectEl = div.querySelector('.cf-type');
        const valEl = div.querySelector('.cf-val');
        let fpInstance = null;
        function setupFlatpickr(t) {
            if (fpInstance) { fpInstance.destroy(); fpInstance = null; }
            valEl.type = t === 'currency' ? 'number' : (t === 'date' || t === 'time' ? 'text' : t);
            if (t === 'currency' || t === 'number') valEl.step = '0.01';
            else valEl.removeAttribute('step');

            if (t === 'date') {
                fpInstance = flatpickr(valEl, { altInput: true, altFormat: "d-m-Y", dateFormat: "Y-m-d", allowInput: false });
            } else if (t === 'time') {
                fpInstance = flatpickr(valEl, { enableTime: true, noCalendar: true, dateFormat: "h:i K", time_24hr: false, allowInput: false, defaultHour: 12 });
            }
        }
        setupFlatpickr(type);
        selectEl.addEventListener('change', (e) => {
            setupFlatpickr(e.target.value);
        });
    }
}

function confirmRemoveCustomField(divId) {
    if (confirm('Are you sure you want to remove this custom field?')) document.getElementById(divId).remove();
}

function convertTo12Hr(timeStr) {
    if (!timeStr) return timeStr;
    const parts = timeStr.split(':');
    if (parts.length < 2) return timeStr;
    let h = parseInt(parts[0]);
    const m = parts[1];
    const period = h >= 12 ? 'PM' : 'AM';
    if (h > 12) h -= 12;
    if (h === 0) h = 12;
    return h + ':' + m + ' ' + period;
}

function getFormHtml(data = null) {
    const date = data ? data.entry_date : getLocalDateString();
    // Native type="time" expects HH:MM (24-hour) format
    const now = new Date();
    const timeVal = data
        ? (data.entry_time ? data.entry_time.slice(0, 5) : '')
        : `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
    const amt = data ? data.amount : '';
    const desc = data ? data.description : '';
    customFieldsCount = 0;
    setTimeout(() => {
        if (data && data.custom_data) {
            for (let k in data.custom_data) {
                let fieldData = data.custom_data[k];
                if (typeof fieldData === 'object' && fieldData !== null) addCustomFieldRow(k, fieldData.value, true, fieldData.type);
                else addCustomFieldRow(k, fieldData, true, 'text');
            }
        } else if (!data && window.currentCustomSchema && Object.keys(window.currentCustomSchema).length > 0) {
            for (let k in window.currentCustomSchema) addCustomFieldRow(k, '', true, window.currentCustomSchema[k]);
        }
    }, 50);

    return `<div class="swal-form-container">
                <div class="swal-field"><label class="swal-label">Date</label><input id="f-date" type="date" class="theme-input-select swal-input" value="${date}" onkeydown="return false" onchange="checkDateMonth(this.value)" onclick="this.showPicker()"></div>
                <div id="date-hint" style="font-size:0.8rem; color:#f59e0b; display:none; text-align:left; margin-top:-10px;"></div>
                <div class="swal-field"><label class="swal-label">Time</label><input id="f-time" type="time" class="theme-input-select swal-input" value="${timeVal}" onkeydown="return false" onclick="this.showPicker()"></div>
                <div class="swal-field"><label class="swal-label">Amount (${userCurrency})</label><input id="f-amt" type="number" step="0.01" class="theme-input-select swal-input" value="${amt}"></div>
                <div class="swal-field"><label class="swal-label">Description</label><input id="f-desc" type="text" class="theme-input-select swal-input" value="${desc}" maxlength="255"></div>
                <div style="text-align:left; margin-top:10px;"><label class="swal-label">Custom Fields (Unlimited)</label><div id="custom-fields-container"></div><button class="btn" style="background:rgba(255,255,255,0.1); color:white; font-size:0.8rem; padding:0.4rem 0.8rem; margin-top:5px;" onclick="addCustomFieldRow()">+ Add Field</button></div>
            </div>`;
}

function checkDateMonth(dateVal) {
    const currentFilter = document.getElementById('monthFilter').value;
    const hintEl = document.getElementById('date-hint');
    if (dateVal && currentFilter && dateVal.substring(0, 7) !== currentFilter) {
        const [y, m] = dateVal.substring(0, 7).split('-');
        const mName = new Date(y, m - 1).toLocaleString('default', { month: 'long' });
        hintEl.innerHTML = `<i class="fas fa-info-circle"></i> Note: This record will be saved under <b>${mName} ${y}</b>.`;
        hintEl.style.display = 'block';
    } else if (hintEl) { hintEl.style.display = 'none'; }
}

function extractCustomFields() {
    const container = document.getElementById('custom-fields-container');
    const rows = container.querySelectorAll('.custom-field-row');
    let data = {};
    rows.forEach(r => {
        const k = r.querySelector('.cf-key').value.trim();
        const t = r.querySelector('.cf-type').value;
        const v = r.querySelector('.cf-val').value.trim();
        if (k) data[k] = { type: t, value: v };
    });
    return data;
}

async function addCategoryAndSelect() {
    const { value: name } = await Swal.fire({ title: 'New Section', input: 'text', showCancelButton: true, confirmButtonColor: '#8b5cf6', inputValidator: (v) => !v && 'Please enter a name' });
    if (name) {
        const res = await fetch(`${API_URL}?action=add_category`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN }, body: JSON.stringify({ category_name: name }) });
        const result = await res.json();
        if (result.status === 'success') {
            await fetchCategories();
            currentCategoryId = result.category_id;
            currentCategoryName = name;
            Swal.fire('Created!', 'You can now save your record in this section.', 'success').then(() => addRecordForm());
        }
    }
}

function convertTo24Hr(timeStr) {
    if (!timeStr) return timeStr;
    const match = timeStr.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
    if (!match) return timeStr;
    let h = parseInt(match[1]);
    const m = match[2];
    const period = match[3].toUpperCase();
    if (period === 'PM' && h !== 12) h += 12;
    if (period === 'AM' && h === 12) h = 0;
    return String(h).padStart(2, '0') + ':' + m;
}

async function addRecordForm() {
    if (!currentCategoryId) return;
    const { value: formValues } = await Swal.fire({
        title: 'Add New Record',
        html: getFormHtml(),
        width: 480,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Save',
        confirmButtonColor: '#8b5cf6',
        preConfirm: () => {
            const date = document.getElementById('f-date').value;
            const time = document.getElementById('f-time').value; // HH:MM from native time input
            const amt = document.getElementById('f-amt').value || 0;
            if (!date || !time) { Swal.showValidationMessage('Date and Time are required'); return false; }
            return { category_id: currentCategoryId, entry_date: date, entry_time: time, amount: amt, description: document.getElementById('f-desc').value, custom_data: extractCustomFields() }
        }
    });
    if (formValues) saveRecord('add_record', formValues);
}

async function editRecord(row) {
    const { value: formValues } = await Swal.fire({
        title: 'Edit Record',
        html: getFormHtml(row),
        width: 480,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Update',
        confirmButtonColor: '#8b5cf6',
        preConfirm: () => {
            const date = document.getElementById('f-date').value;
            const time = document.getElementById('f-time').value; // HH:MM from native time input
            const amt = document.getElementById('f-amt').value || 0;
            if (!date || !time) { Swal.showValidationMessage('Date and Time are required'); return false; }
            return { id: row.id, entry_date: date, entry_time: time, amount: amt, description: document.getElementById('f-desc').value, custom_data: extractCustomFields() }
        }
    });
    if (formValues) saveRecord('update_record', formValues);
}

async function saveRecord(endpoint, data) {
    const res = await fetch(`${API_URL}?action=${endpoint}`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN }, body: JSON.stringify(data) });
    const result = await res.json();
    if (result.status === 'success') {
        const recordMonth = data.entry_date.substring(0, 7);
        const currentMonthFilter = document.getElementById('monthFilter').value;
        if (currentMonthFilter && recordMonth !== currentMonthFilter) {
            const [y, m] = recordMonth.split('-');
            const mName = new Date(y, m - 1).toLocaleString('default', { month: 'long' });
            Swal.fire({ title: 'Record Saved Successfully!', html: `The record was saved under <b>${mName} ${y}</b>.`, icon: 'success', showCancelButton: true, confirmButtonColor: '#8b5cf6', confirmButtonText: `Take me to ${mName}`, cancelButtonText: 'Stay Here' }).then((r) => {
                if (r.isConfirmed) { if (window.monthFlatpickr) window.monthFlatpickr.setDate(recordMonth, false); else document.getElementById('monthFilter').value = recordMonth; applyMonthFilter(); }
                else { loadCategory(currentCategoryId, currentCategoryName); fetchTotalExpenditure(); }
            });
        } else { Swal.fire('Saved!', '', 'success'); loadCategory(currentCategoryId, currentCategoryName); fetchTotalExpenditure(); }
    } else Swal.fire('Error', result.message, 'error');
}

async function deleteRecord(id) {
    const confirm = await Swal.fire({ title: 'Are you sure?', html: "This will permanently delete this record.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Yes, delete it!' });
    if (confirm.isConfirmed) {
        const res = await fetch(`${API_URL}?action=delete_record`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN }, body: JSON.stringify({ id: id }) });
        const result = await res.json();
        if (result.status === 'success') { Swal.fire('Deleted!', '', 'success'); loadCategory(currentCategoryId, currentCategoryName); fetchTotalExpenditure(); }
        else Swal.fire('Error', result.message, 'error');
    }
}

function refreshCurrentView() {
    document.querySelectorAll('#refreshBtn .refresh-icon, #refreshBtnMobile .refresh-icon').forEach(icon => {
        icon.classList.remove('spin');
        void icon.offsetWidth;
        icon.classList.add('spin');
    });
    if (currentCategoryId) {
        loadCategory(currentCategoryId, currentCategoryName);
    }
    fetchCategories();
}

async function addCategory() {
    const { value: name } = await Swal.fire({ title: 'New Section', input: 'text', inputPlaceholder: 'e.g., Room Rent, Food, etc.', inputAttributes: { style: 'max-width:320px; margin:12px auto 0; padding:10px 14px;' }, showCancelButton: true, confirmButtonColor: '#8b5cf6', inputValidator: (v) => !v && 'Please enter a section name!' });
    if (name) {
        const res = await fetch(`${API_URL}?action=add_category`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN }, body: JSON.stringify({ category_name: name }) });
        const result = await res.json();
        if (result.status === 'success') { Swal.fire('Created!', '', 'success'); fetchCategories(); }
        else Swal.fire('Error', result.message, 'error');
    }
}

async function renameSection() {
    if (!currentCategoryId) return;
    const { value: newName } = await Swal.fire({
        title: 'Rename Section',
        html: `<div class="swal-form-container"><input id="renameInput" class="theme-input-select swal-input" value="${currentCategoryName}" placeholder="Enter new section name..." style="text-align:center;"></div>`,
        width: 380,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonColor: '#8b5cf6',
        confirmButtonText: 'Rename',
        preConfirm: () => {
            const v = document.getElementById('renameInput').value;
            if (!v || !v.trim()) { Swal.showValidationMessage('Section name cannot be empty!'); return false; }
            if (v.trim() === currentCategoryName) { Swal.showValidationMessage('New name is same as current name!'); return false; }
            return v.trim();
        }
    });
    if (newName && newName.trim()) {
        const res = await fetch(`${API_URL}?action=rename_category`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN }, body: JSON.stringify({ category_id: currentCategoryId, category_name: newName.trim() }) });
        const result = await res.json();
        if (result.status === 'success') {
            currentCategoryName = newName.trim();
            const titleEl = document.getElementById('currentTableTitle');
            if (titleEl) titleEl.innerText = currentCategoryName;
            await fetchCategories();
            Swal.fire({ icon: 'success', title: 'Renamed!', text: `Section renamed to "${currentCategoryName}"`, timer: 1500, showConfirmButton: false });
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    }
}

async function deleteSpecificCategory(id, name) {
    const confirm = await Swal.fire({ title: 'Delete Section?', html: `This will permanently delete the section "<b>${name}</b>" and ALL records inside it.`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Yes, delete EVERYTHING!' });
    if (confirm.isConfirmed) {
        const res = await fetch(`${API_URL}?action=delete_category`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN }, body: JSON.stringify({ category_id: id }) });
        const result = await res.json();
        if (result.status === 'success') {
            Swal.fire('Deleted!', 'The section has been removed.', 'success');
            if (currentCategoryId === id) {
                currentCategoryId = null; currentCategoryName = '';
                document.getElementById('currentTableTitle').innerText = 'Select a Section';
                const addBtn = document.getElementById('addRecordBtn');
                if (addBtn) addBtn.style.display = 'none';
                const secAct = document.getElementById('sectionActions');
                if (secAct) secAct.style.display = 'none';
                if (document.getElementById('noteBtn')) setElementDisplay('noteBtn', 'none');
                document.getElementById('refreshBtn').classList.remove('show');
                document.getElementById('refreshBtnMobile').classList.remove('show');
                setElementDisplay('dataTable', 'none');
                setElementDisplay('emptyState', 'block');
                setElementDisplay('sectionBudgetBox', 'none');
                setElementDisplay('sectionExpenditureBox', 'none');
                setElementDisplay('sectionBalanceBox', 'none');
            }
            fetchCategories(); fetchTotalExpenditure();
        } else Swal.fire('Error', result.message, 'error');
    }
}

function showBudgetManageInfo() {
    Swal.fire({
        title: 'Manage Budgets',
        html: 'To edit or manage budgets, please go to <b>Settings > Manage Budgets</b>.',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Go to Manage Budgets',
        confirmButtonColor: '#8b5cf6',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            loadView('budgets.php');
        }
    });
}

function getMonthName(monthStr) {
    if (!monthStr) return '';
    const [y, m] = monthStr.split('-');
    return new Date(y, m - 1).toLocaleString('default', { month: 'long' });
}

function getMonthsRange(startStr, endStr) {
    const months = [];
    let [startYear, startMonth] = startStr.split('-').map(Number);
    let [endYear, endMonth] = endStr.split('-').map(Number);

    let currentYear = startYear;
    let currentMonth = startMonth;

    while (currentYear < endYear || (currentYear === endYear && currentMonth <= endMonth)) {
        const monthStr = `${currentYear}-${String(currentMonth).padStart(2, '0')}`;
        months.push(monthStr);
        currentMonth++;
        if (currentMonth > 12) {
            currentMonth = 1;
            currentYear++;
        }
    }
    return months;
}

async function editSectionBudget() {
    if (!currentCategoryId) { Swal.fire('Info', 'Please select a section first.', 'info'); return; }

    let actionType = null;
    const choice = await Swal.fire({
        title: 'Budget Type',
        text: 'Do you want to manage this budget for the overall section, or just for a specific month?',
        icon: 'question',
        html: `
                    <div style="display:flex; flex-direction:column; gap:12px; margin-top:20px; align-items:center; width:100%;">
                        <button type="button" id="btn-swal-monthly" class="btn btn-primary" style="width:100%; max-width:240px; justify-content:center; padding: 0.75rem;">Specific Month</button>
                        <button type="button" id="btn-swal-overall" class="btn btn-ghost" style="width:100%; max-width:240px; justify-content:center; padding: 0.75rem; background:rgba(99, 102, 241, 0.15); border:1px solid rgba(99, 102, 241, 0.3); color:#818cf8;">Overall</button>
                        <button type="button" id="btn-swal-clear" class="btn btn-danger" style="width:100%; max-width:240px; justify-content:center; padding: 0.75rem; background:rgba(239, 68, 68, 0.15); border:1px solid rgba(239, 68, 68, 0.3); color:#ef4444;">Clear Budget</button>
                        <button type="button" id="btn-swal-back" class="btn btn-ghost" style="width:120px; font-size:0.85rem; padding:0.4rem 1rem; justify-content:center; border-color:rgba(255,255,255,0.15); margin-top:10px;">Back</button>
                    </div>
                `,
        showConfirmButton: false,
        showDenyButton: false,
        showCancelButton: false,
        didOpen: () => {
            document.getElementById('btn-swal-monthly').addEventListener('click', () => {
                actionType = 'monthly';
                Swal.clickConfirm();
            });
            document.getElementById('btn-swal-overall').addEventListener('click', () => {
                actionType = 'overall';
                Swal.clickConfirm();
            });
            document.getElementById('btn-swal-clear').addEventListener('click', () => {
                actionType = 'clear';
                Swal.clickConfirm();
            });
            document.getElementById('btn-swal-back').addEventListener('click', () => Swal.clickCancel());
        }
    });

    if (choice.isDismissed || !actionType) {
        return;
    }

    if (actionType === 'clear') {
        triggerClearBudgetFlow();
        return;
    }

    let budgetType = actionType;
    let targetMonth = '';
    let targetMonths = null;

    if (budgetType === 'monthly') {
        const defaultMonth = document.getElementById('monthFilter') ? document.getElementById('monthFilter').value : '';
        const monthPrompt = await Swal.fire({
            title: 'Select Month',
            html: `<input type="text" id="swalMonthInput" class="theme-input-select swal-input" placeholder="Select Month" readonly style="text-align:center;">`,
            showCancelButton: true,
            confirmButtonText: 'Next',
            confirmButtonColor: '#8b5cf6',
            didOpen: () => {
                flatpickr("#swalMonthInput", {
                    plugins: [
                        new monthSelectPlugin({
                            shorthand: true,
                            dateFormat: "Y-m",
                            altFormat: "F Y",
                            theme: "dark"
                        })
                    ],
                    disableMobile: "true",
                    defaultDate: defaultMonth || null
                });
            },
            preConfirm: () => {
                const val = document.getElementById('swalMonthInput').value;
                if (!val) { Swal.showValidationMessage('Please select a month'); return false; }
                return val;
            }
        });
        if (!monthPrompt.isConfirmed) return;
        targetMonth = monthPrompt.value;
    } else {
        const now = new Date();
        const currentMonthStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;

        const startMonthPrompt = await Swal.fire({
            title: 'Select Starting Month',
            html: `
                        <div style="text-align:center; margin-bottom:10px; font-size:0.9rem; color:var(--text-secondary);">
                            Select the starting month from which the overall budget should apply up to the current month (${getMonthName(currentMonthStr)} ${now.getFullYear()}).
                        </div>
                        <input type="text" id="swalStartMonthInput" class="theme-input-select swal-input" placeholder="Select Starting Month" readonly style="text-align:center;">
                    `,
            showCancelButton: true,
            confirmButtonText: 'Next',
            confirmButtonColor: '#8b5cf6',
            didOpen: () => {
                flatpickr("#swalStartMonthInput", {
                    plugins: [
                        new monthSelectPlugin({
                            shorthand: true,
                            dateFormat: "Y-m",
                            altFormat: "F Y",
                            theme: "dark"
                        })
                    ],
                    disableMobile: "true",
                    defaultDate: currentMonthStr
                });
            },
            preConfirm: () => {
                const val = document.getElementById('swalStartMonthInput').value;
                if (!val) { Swal.showValidationMessage('Please select a starting month'); return false; }
                if (val > currentMonthStr) {
                    Swal.showValidationMessage(`Starting month cannot be after ${getMonthName(currentMonthStr)} ${now.getFullYear()}`);
                    return false;
                }
                return val;
            }
        });
        if (!startMonthPrompt.isConfirmed) return;

        const startMonth = startMonthPrompt.value;
        const monthsRange = getMonthsRange(startMonth, currentMonthStr);

        const confirmChoice = await Swal.fire({
            title: 'Are you sure?',
            html: `This will set the budget for <strong>${currentCategoryName}</strong> for all months from <strong>${getMonthName(startMonth)} ${startMonth.split('-')[0]}</strong> to <strong>${getMonthName(currentMonthStr)} ${now.getFullYear()}</strong> (${monthsRange.length} month(s) total).`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, proceed',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#8b5cf6'
        });

        if (!confirmChoice.isConfirmed) return;
        targetMonths = monthsRange;
    }

    let currentCat = categories.find(c => c.id === currentCategoryId);
    let currentBudget = currentCat ? parseFloat(currentCat.budget) || 0 : 0;
    let budgetTitle = `Set Overall Budget for ${currentCategoryName}`;

    if (budgetType === 'monthly') {
        const [y, m] = targetMonth.split('-');
        const monthName = new Date(y, m - 1).toLocaleString('default', { month: 'long' });
        budgetTitle = `Set Budget for ${currentCategoryName} in ${monthName} ${y}`;
    } else if (targetMonths && targetMonths.length > 0) {
        const startM = targetMonths[0];
        const endM = targetMonths[targetMonths.length - 1];
        budgetTitle = `Set Budget for ${currentCategoryName} (${getMonthName(startM)} to ${getMonthName(endM)})`;
    }

    const { value: newBudget } = await Swal.fire({
        title: budgetTitle,
        html: `<div class="swal-form-container"><input id="budgetInput" type="number" step="0.01" class="theme-input-select swal-input" value="${currentBudget === 0 ? '' : currentBudget}" placeholder="0.00" style="text-align:center;"></div>`,
        width: 380,
        showCancelButton: true,
        confirmButtonColor: '#8b5cf6',
        focusConfirm: false,
        didOpen: () => {
            const bInput = document.getElementById('budgetInput');
            if (bInput) {
                bInput.focus();
                bInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        Swal.clickConfirm();
                    }
                });
            }
        },
        preConfirm: () => {
            const v = document.getElementById('budgetInput').value;
            if (!v) { Swal.showValidationMessage('Please enter a valid amount'); return false; }
            return v;
        }
    });

    if (newBudget !== undefined && newBudget !== null && newBudget !== '') {
        const payload = {
            category_id: currentCategoryId,
            budget: newBudget
        };
        if (budgetType === 'monthly') {
            payload.month = targetMonth;
        } else {
            payload.months = targetMonths;
            await new Promise(r => setTimeout(r, 200));
            const applyFuture = await Swal.fire({
                title: 'Apply to Future?',
                text: 'Do you want to apply this same budget for the future months as well?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
                confirmButtonColor: '#8b5cf6'
            });
            if (applyFuture.isConfirmed) {
                payload.overall_default = true;
            }
        }

        const res = await fetch(`${API_URL}?action=update_category_budget`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
            body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.status === 'success') {
            await fetchCategories();
            if (currentCategoryId) loadCategory(currentCategoryId, currentCategoryName);
            Swal.fire('Saved!', '', 'success');
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    }
}

async function triggerClearBudgetFlow() {
    if (!currentCategoryId) return;

    await new Promise(r => setTimeout(r, 200));
    let clearAction = null;
    await Swal.fire({
        title: 'Clear Budget',
        text: `Choose how you want to clear the budget for ${currentCategoryName}:`,
        icon: 'warning',
        html: `
            <div style="display:flex; flex-direction:column; gap:10px; margin-top:20px; align-items:center; width:100%;">
                <button type="button" id="btn-clear-monthly" class="btn btn-primary" style="width:100%; max-width:240px; justify-content:center; padding: 0.75rem;">Specific Month Budget</button>
                <button type="button" id="btn-clear-overall" class="btn btn-ghost" style="width:100%; max-width:240px; justify-content:center; padding: 0.75rem; background:rgba(245, 158, 11, 0.15); border:1px solid rgba(245, 158, 11, 0.3); color:#f59e0b;">Overall Month Range Clear</button>
                <button type="button" id="btn-clear-all" class="btn btn-danger" style="width:100%; max-width:240px; justify-content:center; padding: 0.75rem; background:rgba(239, 68, 68, 0.15); border:1px solid rgba(239, 68, 68, 0.3); color:#ef4444;">Clear Everything</button>
                <button type="button" id="btn-clear-back" class="btn btn-ghost" style="width:120px; font-size:0.85rem; padding:0.4rem 1rem; justify-content:center; border-color:rgba(255,255,255,0.15); margin-top:10px;">Back</button>
            </div>
        `,
        showConfirmButton: false,
        showDenyButton: false,
        showCancelButton: false,
        didOpen: () => {
            document.getElementById('btn-clear-monthly').addEventListener('click', () => {
                clearAction = 'monthly';
                Swal.clickConfirm();
            });
            document.getElementById('btn-clear-overall').addEventListener('click', () => {
                clearAction = 'overall';
                Swal.clickConfirm();
            });
            document.getElementById('btn-clear-all').addEventListener('click', () => {
                clearAction = 'all';
                Swal.clickConfirm();
            });
            document.getElementById('btn-clear-back').addEventListener('click', () => Swal.clickCancel());
        }
    });

    if (clearAction) {
        executeClearBudget(clearAction);
    }
}

async function executeClearBudget(type) {
    await new Promise(r => setTimeout(r, 200));
    let payload = { category_id: currentCategoryId };

    if (type === 'monthly') {
        const defaultMonth = document.getElementById('monthFilter') ? document.getElementById('monthFilter').value : '';
        const monthPrompt = await Swal.fire({
            title: 'Select Month to Clear',
            html: `<input type="text" id="swalClearMonthInput" class="theme-input-select swal-input" placeholder="Select Month" readonly style="text-align:center;">`,
            showCancelButton: true,
            confirmButtonText: 'Clear',
            confirmButtonColor: '#ef4444',
            didOpen: () => {
                flatpickr("#swalClearMonthInput", {
                    plugins: [
                        new monthSelectPlugin({
                            shorthand: true,
                            dateFormat: "Y-m",
                            altFormat: "F Y",
                            theme: "dark"
                        })
                    ],
                    disableMobile: "true",
                    defaultDate: defaultMonth || null
                });
            },
            preConfirm: () => {
                const val = document.getElementById('swalClearMonthInput').value;
                if (!val) { Swal.showValidationMessage('Please select a month'); return false; }
                return val;
            }
        });

        if (!monthPrompt.isConfirmed) return;
        payload.month = monthPrompt.value;
    } else if (type === 'overall') {
        const now = new Date();
        const currentMonthStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;

        const startMonthPrompt = await Swal.fire({
            title: 'Select Start Month to Clear',
            html: `
                <div style="text-align:center; margin-bottom:10px; font-size:0.9rem; color:var(--text-secondary);">
                    Select the starting month to clear budgets.
                </div>
                <div style="text-align:center; margin-bottom:15px; font-size:0.95rem; color:#ef4444; font-weight:600;">
                    Note: Maximum clearance limit is up to the current month (${getMonthName(currentMonthStr)} ${now.getFullYear()}) only.
                </div>
                <input type="text" id="swalClearStartMonth" class="theme-input-select swal-input" placeholder="Select Start Month" readonly style="text-align:center;">
            `,
            showCancelButton: true,
            confirmButtonText: 'Next',
            confirmButtonColor: '#ef4444',
            didOpen: () => {
                flatpickr("#swalClearStartMonth", {
                    plugins: [
                        new monthSelectPlugin({
                            shorthand: true,
                            dateFormat: "Y-m",
                            altFormat: "F Y",
                            theme: "dark"
                        })
                    ],
                    disableMobile: "true",
                    defaultDate: currentMonthStr,
                    maxDate: currentMonthStr
                });
            },
            preConfirm: () => {
                const val = document.getElementById('swalClearStartMonth').value;
                if (!val) { Swal.showValidationMessage('Please select a starting month'); return false; }
                if (val > currentMonthStr) {
                    Swal.showValidationMessage('Starting month cannot exceed the current month!');
                    return false;
                }
                return val;
            }
        });

        if (!startMonthPrompt.isConfirmed) return;
        const startM = startMonthPrompt.value;

        const endMonthPrompt = await Swal.fire({
            title: 'Select End Month to Clear',
            html: `
                <div style="text-align:center; margin-bottom:10px; font-size:0.9rem; color:var(--text-secondary);">
                    Select the ending month to clear budgets.
                </div>
                <div style="text-align:center; margin-bottom:15px; font-size:0.95rem; color:#ef4444; font-weight:600;">
                    Note: Maximum clearance limit is up to the current month (${getMonthName(currentMonthStr)} ${now.getFullYear()}) only.
                </div>
                <input type="text" id="swalClearEndMonth" class="theme-input-select swal-input" placeholder="Select End Month" readonly style="text-align:center;">
            `,
            showCancelButton: true,
            confirmButtonText: 'Next',
            confirmButtonColor: '#ef4444',
            didOpen: () => {
                flatpickr("#swalClearEndMonth", {
                    plugins: [
                        new monthSelectPlugin({
                            shorthand: true,
                            dateFormat: "Y-m",
                            altFormat: "F Y",
                            theme: "dark"
                        })
                    ],
                    disableMobile: "true",
                    defaultDate: currentMonthStr,
                    maxDate: currentMonthStr
                });
            },
            preConfirm: () => {
                const val = document.getElementById('swalClearEndMonth').value;
                if (!val) { Swal.showValidationMessage('Please select an ending month'); return false; }
                if (val > currentMonthStr) {
                    Swal.showValidationMessage('Ending month cannot exceed the current month!');
                    return false;
                }
                if (val < startM) {
                    Swal.showValidationMessage('Ending month cannot be before the starting month!');
                    return false;
                }
                return val;
            }
        });

        if (!endMonthPrompt.isConfirmed) return;
        const endM = endMonthPrompt.value;

        payload.start_month = startM;
        payload.end_month = endM;
    } else if (type === 'all') {
        payload.clear_all_section = true;
    } else {
        return;
    }

    const confirmClear = await Swal.fire({
        title: 'Are you sure?',
        text: 'This action will permanently delete the selected budget configuration!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, clear it!',
        confirmButtonColor: '#ef4444',
        cancelButtonText: 'Cancel'
    });

    if (!confirmClear.isConfirmed) return;

    try {
        const res = await fetch(`${API_URL}?action=clear_category_budget`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
            body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.status === 'success') {
            await fetchCategories();
            if (currentCategoryId) loadCategory(currentCategoryId, currentCategoryName);
            Swal.fire('Cleared!', result.message, 'success');
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    } catch (e) {
        Swal.fire('Error', 'Communication failed with server.', 'error');
    }
}

async function changePasswordModal() {
    const { value: formValues } = await Swal.fire({
        title: 'Change Password',
        html: `<div class="swal-form-container">
                    <div class="swal-field"><label class="swal-label">Current Password</label><input id="cp-old" type="password" class="theme-input-select swal-input" placeholder="Enter current password"></div>
                    <div class="swal-field"><label class="swal-label">New Password</label><input id="cp-new" type="password" class="theme-input-select swal-input" placeholder="Enter new password"></div>
                    <div style="text-align:right; margin-top:5px;"><a href="javascript:void(0)" onclick="event.preventDefault(); Swal.close(); forgotPassword(API_URL, CSRF_TOKEN, 'user', email);" style="color: var(--danger); font-size: 0.85rem; cursor:pointer;"><i class="fas fa-key"></i> Forgot Current Password?</a></div>
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
        const res = await fetch(`${API_URL}?action=change_password`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN }, body: JSON.stringify({ old_password: formValues.oldp, new_password: formValues.newp }) });
        const data = await res.json();
        if (data.status === 'success') Swal.fire('Success', 'Password changed successfully!', 'success');
        else Swal.fire('Error', data.message, 'error').then(() => changePasswordModal());
    }
}

window.tempCurrency = userCurrency;
window.selCurr = function (el, val) {
    document.querySelectorAll('.curr-card').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    window.tempCurrency = val;
    const lbl = document.getElementById('selCurrLabel');
    if (lbl) lbl.textContent = val;
};


async function openNoteModal() {
    if (!currentCategoryId) return;

    Swal.fire({ title: 'Loading...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
        const res = await fetch(`${API_URL}?action=get_note&category_id=${currentCategoryId}`);
        const result = await res.json();
        let currentNote = '';
        if (result.status === 'success') {
            currentNote = result.note;
        }

        Swal.close();

        const { value: noteContent } = await Swal.fire({
            title: `Note: ${currentCategoryName}`,
            html: `<textarea id="noteContent" class="theme-input-select swal-input" style="width: 100%; height: 150px; resize: none; text-align: left; padding: 10px;" placeholder="Write your note here... (Max 1000 characters)" maxlength="1000">${currentNote}</textarea>`,
            width: 500,
            showCancelButton: true,
            confirmButtonText: 'Save Note',
            confirmButtonColor: '#8b5cf6',
            cancelButtonText: 'Cancel',
            preConfirm: () => {
                return document.getElementById('noteContent').value;
            }
        });

        if (noteContent !== undefined) {
            const saveRes = await fetch(`${API_URL}?action=save_note`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
                body: JSON.stringify({ category_id: currentCategoryId, note: noteContent })
            });
            const saveResult = await saveRes.json();
            if (saveResult.status === 'success') {
                Swal.fire('Saved!', 'Your note has been saved.', 'success');
            } else {
                Swal.fire('Error', saveResult.message, 'error');
            }
        }
    } catch (error) {
        Swal.fire('Error', 'Failed to fetch note.', 'error');
    }
}

async function showReadOnlyNote() {
    if (!currentCategoryId) return;

    Swal.fire({ title: 'Loading...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
        const res = await fetch(`${API_URL}?action=get_note&category_id=${currentCategoryId}`);
        const result = await res.json();
        let currentNote = '';
        if (result.status === 'success') {
            currentNote = result.note;
        }

        Swal.close();

        if (!currentNote || !currentNote.trim()) {
            Swal.fire({
                title: `Note: ${currentCategoryName}`,
                html: `<div style="text-align: center; color: var(--text-secondary); font-style: italic; padding: 20px;">No note added for this section. You can add one under Manage Budgets.</div>`,
                icon: 'info',
                confirmButtonText: 'OK',
                confirmButtonColor: '#8b5cf6'
            });
            return;
        }

        Swal.fire({
            title: `Note: ${currentCategoryName}`,
            html: `<div style="text-align: left; background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); border-radius: 10px; padding: 15px; max-height: 250px; overflow-y: auto; white-space: pre-wrap; font-family: var(--font-body); color: var(--text-primary); line-height: 1.6;">${escapeHtml(currentNote)}</div>`,
            icon: 'info',
            confirmButtonText: 'Close',
            confirmButtonColor: '#8b5cf6'
        });
    } catch (error) {
        Swal.fire('Error', 'Failed to fetch note.', 'error');
    }
}

async function forgotPassword(apiUrl, csrfToken, role, userEmail) {
    // Step 1: Confirm and send OTP
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
        // Send request to send OTP
        const res = await fetch(`${apiUrl}?action=send_reset_otp`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken }
        });
        const result = await res.json();

        if (result.status !== 'success') {
            Swal.fire('Error', result.message || 'Failed to send OTP.', 'error');
            return;
        }

        // Step 2: Show OTP Verification Popup
        const otpPrompt = await Swal.fire({
            title: 'Enter OTP',
            html: `
                        <div class="swal-form-container">
                            <p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:15px;">OTP has been sent to your email. It will expire in 2 minutes.</p>
                            <div class="swal-field">
                                <input id="reset-otp" type="text" class="theme-input-select swal-input" placeholder="Enter 6-digit OTP" maxlength="6" style="text-align:center; font-size:1.2rem; letter-spacing:8px; font-weight:bold;">
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

        // Verify OTP
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

        // Step 3: Show Reset Password Popup
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
                if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/.test(pwd)) {
                    Swal.showValidationMessage('Must have 1 uppercase, 1 lowercase, 1 number & 1 special character.');
                    return false;
                }
                return pwd;
            }
        });

        if (!resetPrompt.isConfirmed) return;
        const newPassword = resetPrompt.value;

        Swal.fire({ title: 'Updating password...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        // Call reset_password_with_otp endpoint
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

async function triggerSweepToSavings() {
    const sweepBtn = document.getElementById('sweepSavingsBtn');
    if (!sweepBtn) return;

    const remAmount = parseFloat(sweepBtn.dataset.amount) || 0;
    const secName = sweepBtn.dataset.section || '';

    if (remAmount <= 0) {
        Swal.fire('Info', 'There is no remaining budget to sweep.', 'info');
        return;
    }

    Swal.fire({
        title: 'Fetching Savings Goals...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const res = await fetch('../../Sav/api/api.php?action=get_goals');
        const result = await res.json();
        Swal.close();

        if (result.status !== 'success') {
            Swal.fire('Error', 'Failed to retrieve savings goals.', 'error');
            return;
        }

        const goalsList = result.data || [];
        // Keep only active goals (unachieved)
        const activeGoals = goalsList.filter(g => {
            const target = parseFloat(g.target_amount) || 0;
            const current = parseFloat(g.current_amount) || 0;
            return current < target;
        });

        if (activeGoals.length === 0) {
            Swal.fire({
                title: 'No Active Goals',
                html: `You do not have any active (unachieved) savings goals. Please create one in the Savings module first.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Go to Savings',
                confirmButtonColor: '#8b5cf6',
                cancelButtonText: 'Cancel'
            }).then((r) => {
                if (r.isConfirmed) {
                    window.location.href = '../../Sav/user/index.php';
                }
            });
            return;
        }

        let goalOptionsHtml = '';
        activeGoals.forEach(g => {
            const target = parseFloat(g.target_amount) || 0;
            const current = parseFloat(g.current_amount) || 0;
            const remaining = target - current;
            goalOptionsHtml += `<option value="${g.id}" data-name="${escapeHtml(g.goal_name)}" style="background:var(--bg-deep); color:var(--text-primary);">${escapeHtml(g.goal_name)} (Needs ${userCurrency}${remaining.toFixed(2)})</option>`;
        });

        const now = new Date();
        const currentMonthStr = now.toLocaleString('default', { month: 'long', year: 'numeric' });

        const { value: formValues } = await Swal.fire({
            title: 'Sweep to Savings',
            html: `
                        <div class="swal-form-container">
                            <p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:15px;">
                                Move your excess budget of <strong>${userCurrency}${remAmount.toFixed(2)}</strong> from <strong>${escapeHtml(secName)}</strong> directly into a savings goal.
                            </p>
                            <div class="swal-field">
                                <label class="swal-label">Select Savings Goal</label>
                                <select id="sweep-goal-id" class="theme-input-select swal-input" style="background:var(--bg-deep);">
                                    ${goalOptionsHtml}
                                </select>
                            </div>
                            <div class="swal-field">
                                <label class="swal-label">Sweep Amount (${userCurrency})</label>
                                <input id="sweep-amount" type="number" step="0.01" max="${remAmount}" min="0.01" class="theme-input-select swal-input" value="${remAmount.toFixed(2)}">
                            </div>
                            <div class="swal-field">
                                <label class="swal-label">Remarks / Description</label>
                                <input id="sweep-notes" type="text" class="theme-input-select swal-input" value="Sweep remaining budget from ${escapeHtml(secName)} (${currentMonthStr})">
                            </div>
                        </div>
                    `,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Confirm Sweep 💸',
            confirmButtonColor: '#10b981',
            preConfirm: () => {
                const goalId = document.getElementById('sweep-goal-id').value;
                const amt = parseFloat(document.getElementById('sweep-amount').value) || 0;
                const notes = document.getElementById('sweep-notes').value.trim();

                const selectEl = document.getElementById('sweep-goal-id');
                const selectedOption = selectEl.options[selectEl.selectedIndex];
                const goalName = selectedOption.getAttribute('data-name');

                if (!goalId) {
                    Swal.showValidationMessage('Please select a savings goal.');
                    return false;
                }
                if (amt <= 0 || amt > remAmount) {
                    Swal.showValidationMessage(`Please enter a valid amount between ${userCurrency}0.01 and ${userCurrency}${remAmount.toFixed(2)}`);
                    return false;
                }
                return { goal_id: goalId, goal_name: goalName, amount: amt, notes: notes };
            }
        });

        if (formValues) {
            Swal.fire({ title: 'Processing Sweep...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            const currentDateStr = getLocalDateString(now);
            const currentTimeStr = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

            // 1. Add deposit to Savings Goal
            const savRes = await fetch('../../Sav/api/api.php?action=add_deposit', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
                body: JSON.stringify({
                    goal_id: formValues.goal_id,
                    amount: formValues.amount,
                    type: 'deposit',
                    date: currentDateStr,
                    notes: formValues.notes
                })
            });
            const savResult = await savRes.json();

            if (savResult.status !== 'success') {
                Swal.fire('Error', 'Failed to deposit to Savings goal: ' + savResult.message, 'error');
                return;
            }

            // 2. Add record (expense) to current section/category
            const expRes = await fetch(`${API_URL}?action=add_record`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
                body: JSON.stringify({
                    category_id: currentCategoryId,
                    entry_date: currentDateStr,
                    entry_time: currentTimeStr,
                    amount: formValues.amount,
                    description: `Sweep: ${formValues.goal_name}`,
                    custom_data: {
                        "Sweep Destination": { type: "text", value: formValues.goal_name }
                    }
                })
            });
            const expResult = await expRes.json();

            if (expResult.status === 'success') {
                Swal.fire({
                    title: 'Sweep Successful! 🎉',
                    html: `Successfully swept <strong>${userCurrency}${formValues.amount.toFixed(2)}</strong> to <strong>${escapeHtml(formValues.goal_name)}</strong>.`,
                    icon: 'success',
                    confirmButtonColor: '#8b5cf6'
                });

                loadCategory(currentCategoryId, currentCategoryName);
                fetchTotalExpenditure();
            } else {
                Swal.fire('Partial Success', 'Deposit recorded in Savings, but failed to log record in Expense module: ' + expResult.message, 'warning');
                loadCategory(currentCategoryId, currentCategoryName);
                fetchTotalExpenditure();
            }
        }
    } catch (err) {
        Swal.fire('Error', 'Failed to process the sweep operation.', 'error');
    }
}

