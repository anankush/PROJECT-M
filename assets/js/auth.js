/* ============================================================
   PROJECT M — Auth JS (login.php tab switching, form submit)
   ============================================================ */

const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const AUTH_URL   = document.querySelector('meta[name="base-url"]')?.content
                   ?? window.location.origin + '/';

// ── Tab switching ─────────────────────────────────────────────
function switchTab(tab) {
    const isLogin = tab === 'login';
    document.getElementById('loginForm').style.display    = isLogin ? 'flex' : 'none';
    document.getElementById('registerForm').style.display = isLogin ? 'none' : 'flex';
    document.getElementById('loginTab').classList.toggle('active', isLogin);
    document.getElementById('registerTab').classList.toggle('active', !isLogin);
    document.getElementById('authMessage').style.display = 'none';
}

// ── Password visibility toggle ────────────────────────────────
function togglePass(inputId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(inputId + 'Icon') ?? input.parentElement.querySelector('i');
    const show  = input.type === 'password';
    input.type  = show ? 'text' : 'password';
    if (icon) { icon.classList.toggle('fa-eye', !show); icon.classList.toggle('fa-eye-slash', show); }
}

// ── Show message ──────────────────────────────────────────────
function showMessage(text, type = 'error') {
    const el = document.getElementById('authMessage');
    el.className = `alert alert-${type}`;
    el.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'clock' : 'exclamation-circle'}"></i> ${text}`;
    el.style.display = 'flex';
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// ── Generic form submitter ────────────────────────────────────
async function submitAuth(action, formId, btnId, fields) {
    const btn      = document.getElementById(btnId);
    const btnText  = btn.querySelector('.btn-text');
    const spinner  = btn.querySelector('.btn-spinner');

    btn.disabled       = true;
    btnText.style.display  = 'none';
    spinner.style.display  = 'inline';

    const body = new URLSearchParams({ action, csrf_token: CSRF_TOKEN });
    fields.forEach(f => body.append(f.name, f.value));

    try {
        const res  = await fetch('auth_process.php', { method: 'POST', body });
        const data = await res.json();
        if (data.status === 'success') {
            showMessage('Redirecting…', 'success');
            setTimeout(() => { window.location.href = data.redirect; }, 600);
        } else {
            showMessage(data.message ?? 'Something went wrong.');
        }
    } catch {
        showMessage('Network error. Please try again.');
    } finally {
        btn.disabled       = false;
        btnText.style.display  = 'inline';
        spinner.style.display  = 'none';
    }
}

// ── Login form ────────────────────────────────────────────────
document.getElementById('loginForm').addEventListener('submit', e => {
    e.preventDefault();
    const email    = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;
    if (!email || !password) { showMessage('Please fill in all fields.'); return; }
    submitAuth('login', 'loginForm', 'loginBtn', [{ name: 'email', value: email }, { name: 'password', value: password }]);
});

// ── Register form ─────────────────────────────────────────────
document.getElementById('registerForm').addEventListener('submit', e => {
    e.preventDefault();
    const email   = document.getElementById('regEmail').value.trim();
    const pass    = document.getElementById('regPassword').value;
    const confirm = document.getElementById('regConfirm').value;

    if (!email || !pass || !confirm) { showMessage('Please fill in all fields.'); return; }
    if (pass !== confirm)            { showMessage('Passwords do not match.'); return; }
    if (pass.length < 8)             { showMessage('Password must be at least 8 characters.'); return; }

    submitAuth('register', 'registerForm', 'registerBtn', [
        { name: 'email',            value: email },
        { name: 'password',         value: pass  },
        { name: 'confirm_password', value: confirm },
    ]);
});
