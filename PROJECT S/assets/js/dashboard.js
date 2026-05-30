/* ============================================================
   PROJECT S — Savings Dashboard JavaScript
   Bucket CRUD, Transaction Modal, Summary Stats
   ============================================================ */

// ── Constants (injected by PHP) ───────────────────────────────
// API_URL, CSRF_TOKEN, CURRENCY, ROOT_URL — defined in dashboard.php <script> block

let activeBucketId   = null;
let activeBucketName = '';
let activeTxTab      = 'deposit';
let allBuckets       = [];

// ── Sidebar ───────────────────────────────────────────────────
function toggleSidebar() {
    document.getElementById('appSidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('open');
}

// ── Logout ────────────────────────────────────────────────────
async function doLogout() {
    try {
        await fetch(ROOT_URL + 'auth_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'logout', csrf_token: CSRF_TOKEN }),
            keepalive: true,
        });
    } finally {
        window.location.href = ROOT_URL + 'login.php';
    }
}

// ── Initialise ────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadSummary();
    loadBuckets();
});

// ── Summary Stats ─────────────────────────────────────────────
async function loadSummary() {
    try {
        const res  = await fetch(`${API_URL}?action=get_summary`);
        const data = await res.json();
        if (data.status !== 'success') return;
        document.getElementById('statDeposited').textContent = CURRENCY + fmtNum(data.total_deposited);
        document.getElementById('statWithdrawn').textContent = CURRENCY + fmtNum(data.total_withdrawn);
        document.getElementById('statNet').textContent       = CURRENCY + fmtNum(data.net_savings);
        document.getElementById('statBuckets').textContent   = data.bucket_count;
    } catch (e) { console.error(e); }
}

// ── Buckets ───────────────────────────────────────────────────
async function loadBuckets() {
    try {
        const res  = await fetch(`${API_URL}?action=get_buckets`);
        const data = await res.json();
        allBuckets = data.status === 'success' ? data.data : [];
        renderBuckets();
    } catch (e) { console.error(e); }
}

function renderBuckets() {
    const grid  = document.getElementById('bucketsGrid');
    const empty = document.getElementById('emptyState');

    if (allBuckets.length === 0) {
        grid.innerHTML = '';
        grid.appendChild(empty);
        empty.style.display = 'flex';
        return;
    }
    empty.style.display = 'none';

    grid.innerHTML = allBuckets.map(b => {
        const pct      = b.progress;
        const isFull   = pct >= 100;
        const deadline = b.deadline ? 'Deadline: ' + b.deadline : 'No deadline';

        return `
        <div class="bucket-card glass" id="bucket-${b.id}">
            <div class="bucket-card-header">
                <div class="bucket-icon"><i class="fas fa-piggy-bank"></i></div>
                <div class="bucket-meta">
                    <div class="bucket-name">${escHtml(b.bucket_name)}</div>
                    <div class="bucket-deadline"><i class="fas fa-calendar-alt"></i> ${escHtml(deadline)}</div>
                </div>
                <div class="bucket-actions">
                    <button class="icon-btn edit"   onclick="openEditBucket(${b.id})" title="Edit goal"><i class="fas fa-pen"></i></button>
                    <button class="icon-btn delete" onclick="deleteBucket(${b.id}, '${escHtml(b.bucket_name)}')" title="Delete goal"><i class="fas fa-trash"></i></button>
                </div>
            </div>
            <div class="bucket-amounts">
                <div>
                    <span class="amount-current">${CURRENCY}${fmtNum(b.current_amount)}</span>
                    <span class="amount-target"> / ${CURRENCY}${fmtNum(b.target_amount)}</span>
                </div>
                <span class="progress-label">${pct}%</span>
            </div>
            <div class="progress-bar-wrap">
                <div class="progress-bar ${isFull ? 'full' : ''}" style="width:${Math.min(pct,100)}%"></div>
            </div>
            <div class="bucket-cta">
                <button class="btn-deposit"  onclick="openTxModal(${b.id}, '${escHtml(b.bucket_name)}', 'deposit')"><i class="fas fa-arrow-down"></i> Deposit</button>
                <button class="btn-withdraw" onclick="openTxModal(${b.id}, '${escHtml(b.bucket_name)}', 'withdraw')"><i class="fas fa-arrow-up"></i> Withdraw</button>
                <button class="btn-history"  onclick="openTxModal(${b.id}, '${escHtml(b.bucket_name)}', 'history')"><i class="fas fa-history"></i> History</button>
            </div>
        </div>`;
    }).join('');
}

// ── Add Bucket ────────────────────────────────────────────────
async function openAddBucket() {
    const { value: form } = await Swal.fire({
        title: '<i class="fas fa-piggy-bank" style="color:#10b981;"></i> New Savings Goal',
        html: bucketFormHtml(),
        showCancelButton: true,
        confirmButtonText: 'Create Goal',
        confirmButtonColor: '#10b981',
        focusConfirm: false,
        background: '#0f1020',
        color: '#f1f5f9',
        preConfirm: () => extractBucketForm(),
    });
    if (!form) return;
    try {
        const res  = await fetch(`${API_URL}?action=add_bucket`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN }, body: JSON.stringify(form) });
        const data = await res.json();
        if (data.status === 'success') { await loadBuckets(); loadSummary(); }
        else Swal.fire('Error', data.message, 'error');
    } catch { Swal.fire('Error', 'Network error. Please try again.', 'error'); }
}

// ── Edit Bucket ───────────────────────────────────────────────
async function openEditBucket(id) {
    const bucket = allBuckets.find(b => b.id === id);
    if (!bucket) return;

    const { value: form } = await Swal.fire({
        title: '<i class="fas fa-pen" style="color:#8b5cf6;"></i> Edit Goal',
        html: bucketFormHtml(bucket),
        showCancelButton: true,
        confirmButtonText: 'Save Changes',
        confirmButtonColor: '#8b5cf6',
        focusConfirm: false,
        background: '#0f1020',
        color: '#f1f5f9',
        preConfirm: () => extractBucketForm(),
    });
    if (!form) return;
    form.id = id;
    try {
        const res  = await fetch(`${API_URL}?action=update_bucket`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN }, body: JSON.stringify(form) });
        const data = await res.json();
        if (data.status === 'success') { await loadBuckets(); loadSummary(); }
        else Swal.fire('Error', data.message, 'error');
    } catch { Swal.fire('Error', 'Network error.', 'error'); }
}

// ── Delete Bucket ─────────────────────────────────────────────
async function deleteBucket(id, name) {
    const confirm = await Swal.fire({
        title: 'Delete Goal?',
        html: `This will permanently delete <b>${escHtml(name)}</b> and all its transactions.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, Delete',
        background: '#0f1020',
        color: '#f1f5f9',
    });
    if (!confirm.isConfirmed) return;
    try {
        const res  = await fetch(`${API_URL}?action=delete_bucket`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN }, body: JSON.stringify({ id }) });
        const data = await res.json();
        if (data.status === 'success') { await loadBuckets(); loadSummary(); }
        else Swal.fire('Error', data.message, 'error');
    } catch { Swal.fire('Error', 'Network error.', 'error'); }
}

// ── Transaction Modal ─────────────────────────────────────────
function openTxModal(bucketId, bucketName, tab = 'deposit') {
    activeBucketId   = bucketId;
    activeBucketName = bucketName;
    document.getElementById('txModalTitle').textContent = bucketName;
    document.getElementById('txModal').style.display   = 'flex';
    setTxTab(tab);
}

function closeTxModal() {
    document.getElementById('txModal').style.display = 'none';
    activeBucketId = null;
}

function setTxTab(tab) {
    activeTxTab = tab;
    ['deposit', 'withdraw', 'history'].forEach(t => {
        document.getElementById('tab' + t.charAt(0).toUpperCase() + t.slice(1))
                .classList.toggle('active', t === tab);
    });
    const isHistory = tab === 'history';
    document.getElementById('txFormWrap').style.display    = isHistory ? 'none' : 'flex';
    document.getElementById('txHistoryWrap').style.display = isHistory ? 'block' : 'none';

    if (!isHistory) {
        const label = tab === 'deposit' ? 'Save Deposit' : 'Save Withdrawal';
        document.getElementById('txBtnText').innerHTML = `<i class="fas fa-check"></i> ${label}`;
    } else {
        loadTxHistory();
    }
}

async function submitTransaction() {
    const amount = parseFloat(document.getElementById('txAmount').value);
    const date   = document.getElementById('txDate').value;
    const time   = document.getElementById('txTime').value;
    const desc   = document.getElementById('txDesc').value.trim();

    if (!amount || amount <= 0) { Swal.fire('Invalid Amount', 'Please enter a positive amount.', 'warning'); return; }
    if (!date)                   { Swal.fire('Date Required', 'Please select a date.', 'warning'); return; }

    document.getElementById('txSubmitBtn').disabled = true;
    document.getElementById('txBtnText').style.display    = 'none';
    document.getElementById('txBtnSpinner').style.display = 'inline';

    try {
        const payload = { bucket_id: activeBucketId, transaction_type: activeTxTab, amount, transaction_date: date, transaction_time: time + ':00', description: desc };
        const res  = await fetch(`${API_URL}?action=add_transaction`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN }, body: JSON.stringify(payload) });
        const data = await res.json();
        if (data.status === 'success') {
            document.getElementById('txAmount').value = '';
            document.getElementById('txDesc').value   = '';
            closeTxModal();
            await loadBuckets();
            loadSummary();
            Swal.fire({ title: 'Saved!', text: 'Transaction recorded successfully.', icon: 'success', timer: 1500, showConfirmButton: false, background: '#0f1020', color: '#f1f5f9' });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    } catch {
        Swal.fire('Error', 'Network error. Please try again.', 'error');
    } finally {
        document.getElementById('txSubmitBtn').disabled = false;
        document.getElementById('txBtnText').style.display    = 'inline';
        document.getElementById('txBtnSpinner').style.display = 'none';
    }
}

async function loadTxHistory() {
    const list = document.getElementById('txHistoryList');
    list.innerHTML = '<p style="color:var(--text-muted); text-align:center; padding:1rem;">Loading…</p>';
    try {
        const res  = await fetch(`${API_URL}?action=get_transactions&bucket_id=${activeBucketId}`);
        const data = await res.json();
        if (data.status !== 'success' || data.data.length === 0) {
            list.innerHTML = '<p style="color:var(--text-muted); text-align:center; padding:1.5rem;">No transactions yet.</p>';
            return;
        }
        list.innerHTML = data.data.map(tx => `
            <div class="tx-row">
                <div class="tx-type-icon ${tx.transaction_type === 'deposit' ? 'tx-deposit-icon' : 'tx-withdraw-icon'}">
                    <i class="fas fa-${tx.transaction_type === 'deposit' ? 'arrow-down' : 'arrow-up'}"></i>
                </div>
                <div class="tx-details">
                    <div class="tx-desc">${escHtml(tx.description || tx.transaction_type)}</div>
                    <div class="tx-date">${tx.transaction_date} · ${tx.transaction_time}</div>
                </div>
                <div class="tx-amount ${tx.transaction_type}">
                    ${tx.transaction_type === 'deposit' ? '+' : '-'}${CURRENCY}${fmtNum(tx.amount)}
                </div>
            </div>`).join('');
    } catch {
        list.innerHTML = '<p style="color:#ef4444; text-align:center; padding:1rem;">Failed to load history.</p>';
    }
}

// Close modal on overlay click
document.getElementById('txModal')?.addEventListener('click', e => {
    if (e.target === document.getElementById('txModal')) closeTxModal();
});

// ── Bucket Form HTML helper ───────────────────────────────────
function bucketFormHtml(b = null) {
    return `
    <div style="display:flex;flex-direction:column;gap:0.9rem;text-align:left;">
        <div><label style="font-size:0.82rem;color:#94a3b8;">Goal Name</label>
            <input id="bName" type="text" value="${b ? escHtml(b.bucket_name) : ''}" placeholder="e.g. Emergency Fund" maxlength="100"
                style="width:100%;padding:0.65rem 1rem;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);border-radius:10px;color:#f1f5f9;font-size:0.95rem;margin-top:4px;outline:none;">
        </div>
        <div><label style="font-size:0.82rem;color:#94a3b8;">Target Amount (${CURRENCY})</label>
            <input id="bTarget" type="number" step="0.01" min="1" value="${b ? b.target_amount : ''}" placeholder="10000.00"
                style="width:100%;padding:0.65rem 1rem;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);border-radius:10px;color:#f1f5f9;font-size:0.95rem;margin-top:4px;outline:none;">
        </div>
        <div><label style="font-size:0.82rem;color:#94a3b8;">Deadline (optional)</label>
            <input id="bDeadline" type="date" value="${b && b.deadline ? b.deadline : ''}"
                style="width:100%;padding:0.65rem 1rem;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);border-radius:10px;color:#f1f5f9;font-size:0.95rem;margin-top:4px;outline:none;">
        </div>
    </div>`;
}

function extractBucketForm() {
    const name   = document.getElementById('bName').value.trim();
    const target = parseFloat(document.getElementById('bTarget').value);
    const dl     = document.getElementById('bDeadline').value;
    if (!name)       { Swal.showValidationMessage('Goal name is required.'); return false; }
    if (!target || target <= 0) { Swal.showValidationMessage('Enter a valid target amount.'); return false; }
    return { bucket_name: name, target_amount: target, deadline: dl || null };
}

// ── Utilities ─────────────────────────────────────────────────
function fmtNum(n) {
    return Number(n).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function escHtml(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
