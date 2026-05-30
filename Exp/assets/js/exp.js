
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

        // Removed aggressive popstate logout that breaks navigation and causes 404s

        window.addEventListener('beforeunload', function(e) {
            if (!isManualLogout) {
                fetch(`${API_URL}?action=user_logout`, { method: 'POST', headers: { 'X-CSRF-Token': CSRF_TOKEN }, keepalive: true });
            }
        });

        function toggleSidebar() {
            document.getElementById('appSidebar').classList.toggle('open');
            document.querySelector('.sidebar-overlay').classList.toggle('open');
        }

        async function loadView(viewName) {
            try {
                const res = await fetch(viewName);
                if (!res.ok) throw new Error(`HTTP ${res.status}: ${res.statusText}`);
                const html = await res.text();
                const mc = document.getElementById('main-content');
                if (mc) mc.innerHTML = html;
                initDashboard();
            } catch (e) {
                console.error("Failed to load view", e);
                const mc = document.getElementById('main-content');
                if (mc) mc.innerHTML = `<div style="text-align:center; padding: 50px;"><i class="fas fa-exclamation-triangle fa-3x" style="color:var(--danger); margin-bottom: 20px;"></i><h2 style="color:white;">Failed to load module.</h2><p style="color:var(--text-muted);">${e.message}</p></div>`;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            if(document.getElementById('main-content')) {
                loadView('view_expenses.php');
            }
        });

        function initDashboard() {
            if (document.getElementById('monthFilter')) {
                window.monthFlatpickr = flatpickr("#monthFilter", {
                    disableMobile: true,
                    plugins: [new monthSelectPlugin({ shorthand: true, dateFormat: "Y-m", altFormat: "Y-m", theme: "dark" })],
                    onChange: function() { 
                        applyMonthFilter().then(() => {
                            if (window.innerWidth <= 768 && !currentCategoryId) {
                                const sidebar = document.getElementById('appSidebar');
                                if (!sidebar.classList.contains('open')) toggleSidebar();
                            }
                        }); 
                    }
                });
            }
            if (document.getElementById('budgetsTableBody')) {
                renderBudgetsTable();
            }
            document.addEventListener('wheel', function(e) {
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
                return;
            }

            categories.forEach(cat => {
                const tr = document.createElement('tr');
                const budgetVal = parseFloat(cat.budget) || 0;
                tr.innerHTML = `
                    <td>${escapeHtml(cat.category_name)}</td>
                    <td style="color:var(--aurora-2); font-weight:600;">${userCurrency}${budgetVal.toFixed(2)}</td>
                    <td style="text-align:right;">
                        <button class="btn btn-ghost" onclick="triggerRename(${cat.id}, '${escapeHtml(cat.category_name)}')">
                            <i class="fas fa-pen"></i> Rename
                        </button>
                        <button class="btn btn-ghost" onclick="triggerEditBudget(${cat.id}, '${escapeHtml(cat.category_name)}')">
                            <i class="fas fa-edit"></i> Budget
                        </button>
                        <button class="btn btn-ghost" onclick="triggerNotes(${cat.id}, '${escapeHtml(cat.category_name)}')">
                            <i class="far fa-sticky-note"></i> Notes
                        </button>
                        <button class="btn btn-ghost" style="color:var(--danger);" onclick="deleteSpecificCategory(${cat.id}, '${escapeHtml(cat.category_name)}')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
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
                    monthInput.value = `${currentYear}-${currentMonthStr}`;
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

                if (window.innerWidth <= 768 && !currentCategoryId) {
                    const sidebar = document.getElementById('appSidebar');
                    if (!sidebar.classList.contains('open')) {
                        toggleSidebar();
                    }
                }

                if (currentCategoryId) {
                    loadCategory(currentCategoryId, currentCategoryName);
                } else {
                    if (titleSpan) titleSpan.innerText = 'Please select a Section';
                    const addBtn = document.getElementById('addRecordBtn');
                    if (addBtn) addBtn.style.display = 'none';
                    const secActions = document.getElementById('sectionActions');
                    if (secActions) secActions.style.display = 'none';
                    if(document.getElementById('noteBtn')) document.getElementById('noteBtn').style.display = 'none';
                    const rb = document.getElementById('refreshBtn');
                    if(rb) rb.classList.remove('show');
                    const rbm = document.getElementById('refreshBtnMobile');
                    if(rbm) rbm.classList.remove('show');
                    const dt = document.getElementById('dataTable');
                    if(dt) dt.style.display = 'none';
                    const es = document.getElementById('emptyState');
                    if(es) es.style.display = 'none';
                    const srs = document.getElementById('sortRecordsSelect');
                    if(srs) srs.style.display = 'none';
                    const sbb = document.getElementById('sectionBudgetBox');
                    if(sbb) sbb.style.display = 'none';
                    const seb = document.getElementById('sectionExpenditureBox');
                    if(seb) seb.style.display = 'none';
                    const sbalb = document.getElementById('sectionBalanceBox');
                    if(sbalb) sbalb.style.display = 'none';
                }
            } else {
                if (summaryTitle) summaryTitle.innerText = 'Overall Budget';
                if (expTitle) expTitle.innerText = 'Overall Expenditure';
                if (balTitle) balTitle.innerText = 'Overall Remaining';
                if (titleSpan) titleSpan.innerText = 'Please select a Year and Month to view records or add new ones.';
                
                const addRecordBtn = document.getElementById('addRecordBtn');
                if (addRecordBtn) {
                    addRecordBtn.style.display = 'none';
                    document.getElementById('sectionActions').style.display = 'none';
                    if(document.getElementById('noteBtn')) document.getElementById('noteBtn').style.display = 'none';
                    document.getElementById('refreshBtn').classList.remove('show');
                    document.getElementById('refreshBtnMobile').classList.remove('show');
                    document.getElementById('dataTable').style.display = 'none';
                    document.getElementById('emptyState').style.display = 'none';
                    document.getElementById('sortRecordsSelect').style.display = 'none';
                    document.getElementById('sectionBudgetBox').style.display = 'none';
                    document.getElementById('sectionExpenditureBox').style.display = 'none';
                    document.getElementById('sectionBalanceBox').style.display = 'none';
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
                    document.getElementById('appUI').style.display = 'flex';
                    if (document.getElementById('monthFilter')) {
                        applyMonthFilter();
                    } else {
                        await fetchCategories();
                    }
                } else {
                    window.location.href = '../../auth/login.php';
                }
            } catch (e) {
                console.error(e);
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
            } catch (e) { console.error(e); }
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
            document.getElementById('addRecordBtn').style.display = 'inline-flex';
            document.getElementById('sectionActions').style.display = 'flex';
            if(document.getElementById('noteBtn')) document.getElementById('noteBtn').style.display = 'inline-flex';
            document.getElementById('refreshBtn').classList.add('show');
            document.getElementById('refreshBtnMobile').classList.add('show');
            renderTabs();

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
                    document.getElementById('monthFilter').type = 'month';
                    document.getElementById('monthFilter').value = monthVal;
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
                    } catch (err) { console.error(err); }

                    if (data.schema) window.currentCustomSchema = data.schema;

                    let currentCat = categories.find(c => c.id === currentCategoryId);
                    let secBudget = currentCat ? parseFloat(currentCat.budget) || 0 : 0;
                    document.getElementById('sectionBudgetDisplay').innerHTML = `${userCurrency}${secBudget.toFixed(2)}`;

                    document.getElementById('sectionBudgetBox').style.display = 'block';
                    document.getElementById('sectionExpenditureBox').style.display = 'block';
                    document.getElementById('sectionBalanceBox').style.display = 'block';

                    const [y, m] = monthVal.split('-');
                    const mName = new Date(y, m - 1).toLocaleString('default', { month: 'short' });
                    document.querySelector('#sectionBudgetBox .metric-label').innerHTML = `Section Budget (${mName} ${y}) <i class="fas fa-edit" style="cursor:pointer; font-size:0.8rem; margin-left:5px;" onclick="editSectionBudget()" title="Edit Section Budget"></i>`;
                    document.querySelector('#sectionExpenditureBox .metric-label').innerText = `Section Expenditure (${mName} ${y})`;
                    document.querySelector('#sectionBalanceBox .metric-label').innerText = `Section Remaining (${mName} ${y})`;

                    let secExp = 0;
                    if (data.data.length > 0) {
                        window.currentRecords = data.data;
                        let totalRecords = window.currentRecords.length;
                        window.currentRecords.forEach((r, idx) => { r.serial_no = totalRecords - idx; });
                        document.getElementById('sortRecordsSelect').style.display = 'inline-block';
                        secExp = data.data.reduce((sum, r) => sum + (parseFloat(r.amount) || 0), 0);
                        sortRecords();
                        table.style.display = 'table';
                    } else {
                        window.currentRecords = [];
                        document.getElementById('sortRecordsSelect').style.display = 'none';
                        emptyState.style.display = 'block';
                    }

                    document.getElementById('sectionAmount').innerHTML = `${userCurrency}${secExp.toFixed(2)}`;
                    let secBal = secBudget - secExp;
                    let secBalEl = document.getElementById('sectionBalanceDisplay');
                    secBalEl.innerHTML = `${userCurrency}${secBal.toFixed(2)}`;
                    secBalEl.style.color = secBal < 0 ? 'var(--danger)' : secBal > 0 ? 'var(--success)' : '#0ea5e9';

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
                rowHtml += `<td style="font-size:0.8rem; color:var(--text-muted);">${escapeHtml(row.created_at)}</td><td style="text-align:right;"><div class="action-btns" style="justify-content:flex-end;"><button class="icon-btn edit" onclick='editRecord(${JSON.stringify(row).replace(/'/g, "&#39;")})' title="Edit"><i class="fas fa-edit"></i></button><button class="icon-btn delete" onclick="deleteRecord(${row.id})" title="Delete"><i class="fas fa-trash"></i></button></div></td>`;
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
            const date = data ? data.entry_date : new Date().toISOString().split('T')[0];
            const time = data ? convertTo12Hr(data.entry_time) : new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
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
                <div class="swal-field"><label class="swal-label">Date</label><input id="f-date" type="date" class="theme-input-select swal-input" value="${date}" onchange="checkDateMonth(this.value)"></div>
                <div id="date-hint" style="font-size:0.8rem; color:#f59e0b; display:none; text-align:left; margin-top:-10px;"></div>
                <div class="swal-field"><label class="swal-label">Time</label><input id="f-time" type="text" class="theme-input-select swal-input" value="${time}" placeholder="HH:MM AM/PM"></div>
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
                didOpen: () => { flatpickr("#f-date", { altInput: true, altFormat: "d-m-Y", dateFormat: "Y-m-d", allowInput: false }); flatpickr("#f-time", { enableTime: true, noCalendar: true, dateFormat: "h:i K", time_24hr: false, allowInput: false, defaultHour: 12 }); },
                preConfirm: () => {
                    const date = document.getElementById('f-date').value;
                    const time = convertTo24Hr(document.getElementById('f-time').value);
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
                didOpen: () => { flatpickr("#f-date", { altInput: true, altFormat: "d-m-Y", dateFormat: "Y-m-d", allowInput: false }); flatpickr("#f-time", { enableTime: true, noCalendar: true, dateFormat: "h:i K", time_24hr: false, allowInput: false, defaultHour: 12 }); },
                preConfirm: () => {
                    const date = document.getElementById('f-date').value;
                    const time = convertTo24Hr(document.getElementById('f-time').value);
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
                if (result.status === 'success') { currentCategoryName = newName.trim(); document.getElementById('currentTableTitle').innerText = currentCategoryName; Swal.fire({ icon: 'success', title: 'Renamed!', text: `Section renamed to "${currentCategoryName}"`, timer: 1500, showConfirmButton: false }); fetchCategories(); }
                else Swal.fire('Error', result.message, 'error');
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
                        document.getElementById('addRecordBtn').style.display = 'none';
                        document.getElementById('sectionActions').style.display = 'none';
                        if(document.getElementById('noteBtn')) document.getElementById('noteBtn').style.display = 'none';
                        document.getElementById('refreshBtn').classList.remove('show');
                        document.getElementById('refreshBtnMobile').classList.remove('show');
                        document.getElementById('dataTable').style.display = 'none';
                        document.getElementById('emptyState').style.display = 'block';
                        document.getElementById('sectionBudgetBox').style.display = 'none';
                        document.getElementById('sectionExpenditureBox').style.display = 'none';
                        document.getElementById('sectionBalanceBox').style.display = 'none';
                    }
                    fetchCategories(); fetchTotalExpenditure();
                } else Swal.fire('Error', result.message, 'error');
            }
        }

        async function editSectionBudget() {
            if (!currentCategoryId) { Swal.fire('Info', 'Please select a section first.', 'info'); return; }
            
            const monthVal = document.getElementById('monthFilter') ? document.getElementById('monthFilter').value : '';
            let budgetType = 'overall';
            let targetMonth = '';

            if (monthVal) {
                const choice = await Swal.fire({
                    title: 'Budget Type',
                    text: 'Do you want to set this budget for the overall section, or just for the currently selected month?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'This Month',
                    cancelButtonText: 'Overall',
                    confirmButtonColor: '#8b5cf6',
                    cancelButtonColor: '#6366f1'
                });
                
                if (choice.dismiss === Swal.DismissReason.backdrop || choice.dismiss === Swal.DismissReason.esc || choice.dismiss === Swal.DismissReason.close) {
                    return;
                }
                budgetType = choice.isConfirmed ? 'monthly' : 'overall';
                targetMonth = choice.isConfirmed ? monthVal : '';
            }

            let currentCat = categories.find(c => c.id === currentCategoryId);
            let currentBudget = currentCat ? parseFloat(currentCat.budget) || 0 : 0;
            let budgetTitle = `Set Overall Budget for ${currentCategoryName}`;
            
            if (budgetType === 'monthly') { 
                const [y, m] = monthVal.split('-'); 
                const monthName = new Date(y, m - 1).toLocaleString('default', { month: 'long' }); 
                budgetTitle = `Set Budget for ${currentCategoryName} in ${monthName} ${y}`; 
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
                    bInput.focus();
                    bInput.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') {
                            Swal.clickConfirm();
                        }
                    });
                },
                preConfirm: () => {
                    const v = document.getElementById('budgetInput').value;
                    if (!v) { Swal.showValidationMessage('Please enter a valid amount'); return false; }
                    return v;
                }
            });
            if (newBudget !== undefined && newBudget !== null && newBudget !== '') {
                const res = await fetch(`${API_URL}?action=update_category_budget`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN }, body: JSON.stringify({ category_id: currentCategoryId, budget: newBudget, month: targetMonth }) });
                const result = await res.json();
                if (result.status === 'success') { await fetchCategories(); if (currentCategoryId) loadCategory(currentCategoryId, currentCategoryName); Swal.fire('Saved!', '', 'success'); }
                else Swal.fire('Error', result.message, 'error');
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
        window.selCurr = function(el, val) { document.querySelectorAll('.curr-card').forEach(c => c.classList.remove('active')); el.classList.add('active'); window.tempCurrency = val; }

        async function openSettings() {
            window.tempCurrency = userCurrency;
            let gridHtml = '';
            allCurrencies.forEach(c => { gridHtml += `<div class="set-card curr-card ${userCurrency===c?'active':''}" onclick="selCurr(this, '${c}')">${c}</div>`; });

            const { value: formValues } = await Swal.fire({
                title: 'Global Settings', width: 600,
                html: `<div style="text-align:left; margin-bottom:15px;"><label style="font-weight:600; color:var(--text-primary);">Select Currency (160+ Currencies)</label><div class="set-grid" style="max-height: 300px; overflow-y: auto; padding-right:10px; margin-top:10px;">${gridHtml}</div></div><hr style="border-color:rgba(255,255,255,0.1); margin:15px 0;"><div style="text-align:left; margin-bottom:15px;"><label style="font-weight:600; color:var(--text-primary);">Backup & Restore</label><div style="display:flex; gap:10px; margin-top:10px;"><button type="button" class="btn btn-ghost" style="flex:1;" onclick="exportData()"><i class="fas fa-download"></i> Export Data (JSON)</button><button type="button" class="btn btn-ghost" style="flex:1;" onclick="document.getElementById('importFile').click()"><i class="fas fa-upload"></i> Import Data (JSON)</button></div></div><hr style="border-color:rgba(239,68,68,0.3); margin:15px 0;"><div style="text-align:left; margin-bottom:15px;"><label style="font-weight:600; color:var(--danger);">Danger Zone</label><div style="margin-top:10px;"><button type="button" class="btn btn-danger" style="width:100%;" onclick="deleteMyAccount()"><i class="fas fa-trash-alt"></i> Permanently Delete My Account</button></div></div>`,
                focusConfirm: false, showCancelButton: true, confirmButtonText: 'Save Changes', confirmButtonColor: '#8b5cf6',
                preConfirm: () => { return { currency: window.tempCurrency, language: 'en' } }
            });
            if (formValues) {
                const res = await fetch(`${API_URL}?action=update_settings`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN }, body: JSON.stringify(formValues) });
                const result = await res.json();
                if (result.status === 'success') { userCurrency = formValues.currency; document.getElementById('totalBudgetDisplay').innerHTML = `${userCurrency}${totalBudget.toFixed(2)}`; fetchTotalExpenditure(); if (currentCategoryId) loadCategory(currentCategoryId, currentCategoryName); Swal.fire('Saved', 'Settings applied successfully', 'success'); }
                else Swal.fire('Error', result.message, 'error');
            }
        }

        async function deleteMyAccount() {
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
            const res = await fetch(`${API_URL}?action=delete_user_account`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN }, body: JSON.stringify({ password }) });
            const data = await res.json();
            if (data.status === 'success') { await Swal.fire('Deleted!', 'Your account has been deleted.', 'success'); window.location.href = '<?php echo $base; ?>index.php'; }
            else Swal.fire('Error', data.message, 'error');
        }

        async function exportData() {
            const { value: password } = await Swal.fire({
                title: 'Encrypt Export',
                html: '<p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:10px;">Set a password to encrypt your data. You will need this password to import.</p>',
                input: 'password',
                inputPlaceholder: 'Enter encryption password',
                inputAttributes: { style: 'max-width:320px; margin:0 auto; padding:10px 14px;' },
                showCancelButton: true,
                confirmButtonText: 'Export & Encrypt',
                confirmButtonColor: '#8b5cf6',
                inputValidator: (v) => { if (!v || v.length < 4) return 'Password must be at least 4 characters'; }
            });
            if (!password) return;

            Swal.fire({ title: 'Exporting...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            try {
                const res = await fetch(`${API_URL}?action=export_data`, { method: 'POST', headers: { 'X-CSRF-Token': CSRF_TOKEN } });
                const jsonText = await res.text();
                JSON.parse(jsonText);

                const encrypted = CryptoJS.AES.encrypt(jsonText, password).toString();
                const blob = new Blob([encrypted], { type: 'text/plain' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `Expense management-backup-${new Date().toISOString().slice(0,10)}.encrypted`;
                a.click();
                URL.revokeObjectURL(url);
                Swal.fire('Exported!', 'Encrypted backup downloaded.', 'success');
            } catch (e) { Swal.fire('Error', 'Export failed', 'error'); }
        }

        async function handleImport(event) {
            const file = event.target.files[0];
            if (!file) return;

            if (!file.name.endsWith('.encrypted')) {
                Swal.fire('Error', 'Only .encrypted backup files are accepted.', 'error');
                document.getElementById('importFile').value = '';
                return;
            }

            const { value: password } = await Swal.fire({
                title: 'Decrypt Backup',
                html: '<p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:10px;">Enter the password used during export.</p>',
                input: 'password',
                inputPlaceholder: 'Enter decryption password',
                inputAttributes: { style: 'max-width:320px; margin:0 auto; padding:10px 14px;' },
                showCancelButton: true,
                confirmButtonText: 'Decrypt & Import',
                confirmButtonColor: '#8b5cf6',
                inputValidator: (v) => { if (!v) return 'Password is required'; }
            });
            if (!password) { document.getElementById('importFile').value = ''; return; }

            const reader = new FileReader();
            reader.onload = async function(e) {
                try {
                    const decrypted = CryptoJS.AES.decrypt(e.target.result, password);
                    const jsonText = decrypted.toString(CryptoJS.enc.Utf8);
                    if (!jsonText) throw new Error('Wrong password');
                    const data = JSON.parse(jsonText);
                    if (!data.categories) throw new Error("Invalid format");

                    let mode = 'replace';
                    if (categories && categories.length > 0) {
                        const choice = await Swal.fire({
                            title: 'Existing Data Detected',
                            text: 'Your account already contains some records. How would you like to proceed with the imported data?',
                            icon: 'question',
                            showDenyButton: true,
                            showCancelButton: true,
                            confirmButtonText: '<i class="fas fa-object-group"></i> Merge Data',
                            denyButtonText: '<i class="fas fa-sync-alt"></i> Replace Data',
                            buttonsStyling: false,
                            customClass: {
                                confirmButton: 'btn btn-primary',
                                denyButton: 'btn btn-danger',
                                cancelButton: 'btn btn-ghost'
                            }
                        });

                        if (choice.isConfirmed) {
                            mode = 'merge';
                        } else if (choice.isDenied) {
                            mode = 'replace';
                        } else {
                            document.getElementById('importFile').value = '';
                            return;
                        }
                    }

                    Swal.fire({ title: 'Importing...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                    const res = await fetch(`${API_URL}?action=import_data&mode=${mode}`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN }, body: JSON.stringify(data) });
                    const result = await res.json();
                    if (result.status === 'success') {
                        let msg = 'Data restored successfully.';
                        if (result.skipped_duplicates && result.skipped_duplicates > 0) {
                            msg += `<br><br><span style="color:#f59e0b; font-size:0.9rem;"><i class="fas fa-exclamation-triangle"></i> ${result.skipped_duplicates} duplicate records were skipped.</span>`;
                        }
                        Swal.fire({ title: 'Imported!', html: msg, icon: 'success' }).then(() => window.location.reload());
                    } else Swal.fire('Error', result.message, 'error');
                } catch (error) { Swal.fire('Error', error.message === 'Wrong password' ? 'Wrong password!' : 'Invalid or corrupted file', 'error'); }
                document.getElementById('importFile').value = '';
            };
            reader.readAsText(file);
        }

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
    
