

window.tempCurrency = userCurrency;

window.selCurr = function (el, val) {
    document.querySelectorAll('.curr-card').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    window.tempCurrency = val;
    const lbl = document.getElementById('selCurrLabel');
    if (lbl) lbl.textContent = val;
};

window.filterCurrencies = function (q) {
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

async function openGlobalSettings() {
    window.tempCurrency = userCurrency;


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

    const pushSectionHtml = `
        <hr style="border-color:rgba(255,255,255,0.1); margin:20px 0;">
        <div style="text-align:left; margin-bottom:15px;">
            <label style="font-weight:600; color:var(--text-primary);">🔔 Push Notifications</label>
            <div id="push-settings-area" style="margin-top:12px;">
                <div style="font-size:0.85rem; color:var(--text-muted); text-align:center;">
                    <i class="fas fa-circle-notch fa-spin"></i> Loading...
                </div>
            </div>
        </div>`;

    const { value: formValues } = await Swal.fire({
        title: 'Global Settings', width: 600,
        html: currencySection + pushSectionHtml +
            `<hr style="border-color:rgba(255,255,255,0.1); margin:20px 0;">
             <div style="text-align:left; margin-bottom:15px;">
                 <label style="font-weight:600; color:var(--text-primary);">Security</label>
                 <div style="margin-top:10px;">
                     <button type="button" class="btn btn-ghost" style="width:100%; justify-content:flex-start; background:rgba(139, 92, 246, 0.1); border:1px solid rgba(139, 92, 246, 0.2);" onclick="Swal.close(); setTimeout(changePasswordModal, 300);">
                         <i class="fas fa-key" style="color:var(--aurora-1);"></i> Change Account Password
                     </button>
                 </div>
             </div>
             <hr style="border-color:rgba(255,255,255,0.1); margin:20px 0;">
             <div style="text-align:left; margin-bottom:15px;">
                 <label style="font-weight:600; color:var(--text-primary);">Backup &amp; Restore</label>
                 <div style="display:flex; gap:10px; margin-top:10px;" class="settings-btn-group">
                     <button type="button" class="btn btn-ghost" style="flex:1;" onclick="exportData()"><i class="fas fa-download"></i> Export Data</button>
                     <button type="button" class="btn btn-ghost" style="flex:1;" onclick="document.getElementById('importFile').click()"><i class="fas fa-upload"></i> Import Data</button>
                 </div>
             </div>
             <hr style="border-color:rgba(239,68,68,0.3); margin:20px 0;">
             <div style="text-align:left; margin-bottom:15px;">
                 <label style="font-weight:600; color:var(--danger);">Danger Zone</label>
                 <div style="margin-top:10px;">
                     <button type="button" class="btn btn-danger" style="width:100%; justify-content:flex-start;" onclick="deleteMyAccount()">
                         <i class="fas fa-trash-alt"></i> Permanently Delete My Account
                     </button>
                 </div>
             </div>`,
        focusConfirm: false, showCancelButton: true, confirmButtonText: 'Save Changes', confirmButtonColor: '#8b5cf6',
        didOpen: async () => {
            const s = document.getElementById('currencySearch');
            if (s) setTimeout(() => s.focus(), 50);
            await renderPushSettingsUI();
        },
        preConfirm: () => { return { currency: window.tempCurrency, language: 'en' }; }
    });


    if (formValues) {
        const res = await fetch(`${API_URL}?action=update_settings`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN }, body: JSON.stringify(formValues) });
        const result = await res.json();
        if (result.status === 'success') {
            userCurrency = formValues.currency;
            Swal.fire({
                title: 'Saved',
                text: 'Settings applied successfully',
                icon: 'success'
            }).then(() => window.location.reload());
        } else {
            Swal.fire('Error', result.message, 'error');
        }
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
    if (data.status === 'success') {
        await Swal.fire('Deleted!', 'Your account has been deleted.', 'success');
        window.location.href = '../index.php';
    } else Swal.fire('Error', data.message, 'error');
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
        
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const timestamp = `${year}-${month}-${day}_${hours}-${minutes}-${seconds}`;
        
        a.download = `Money_Management_Backup_${timestamp}.encrypted`;
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
    reader.onload = async function (e) {
        try {
            const decrypted = CryptoJS.AES.decrypt(e.target.result, password);
            const jsonText = decrypted.toString(CryptoJS.enc.Utf8);
            if (!jsonText) throw new Error('Wrong password');
            const data = JSON.parse(jsonText);
            if (!data.categories && !data.savings_goals && !data.monthly_overall_budgets) throw new Error("Invalid format");

            let hasExistingData = false;
            try {
                const checkRes = await fetch(`${API_URL}?action=check_existing_data`);
                const checkData = await checkRes.json();
                if (checkData.status === 'success') {
                    hasExistingData = checkData.has_data;
                }
            } catch (err) { }

            let mode = 'replace';
            if (hasExistingData) {
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

async function renderPushSettingsUI() {
    const area = document.getElementById('push-settings-area');
    if (!area) return;

    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        area.innerHTML = `
            <div style="font-size:0.85rem; color:var(--danger); text-align:center; padding:10px; background:rgba(239, 68, 68, 0.1); border-radius:var(--radius-sm); border:1px solid rgba(239, 68, 68, 0.2);">
                <i class="fas fa-exclamation-triangle"></i> Push notifications are not supported by this browser.
            </div>`;
        return;
    }

    const data = await loadPushPrefs();
    
    const permission = Notification.permission;
    let enabled = false;
    let prefs = {
        budget_alert: 1,
        budget_exceeded: 1,
        savings_goal: 1,
        monthly_summary: 1,
        login_alert: 1
    };

    if (data && data.status === 'success') {
        enabled = data.has_subscription && permission === 'granted';
        prefs = data.prefs;
    }

    const styles = `
        <style>
        .push-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            gap: 12px;
        }
        .push-row:last-child {
            border-bottom: none;
        }
        .push-label {
            display: flex;
            flex-direction: column;
            text-align: left;
            flex: 1;
            min-width: 0;
        }
        .push-title {
            font-size: 0.9rem;
            color: var(--text-primary);
            font-weight: 500;
        }
        .push-desc {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 22px;
            flex-shrink: 0;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(255, 255, 255, 0.1);
            transition: .3s ease;
            border-radius: 22px;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 2px;
            bottom: 2px;
            background-color: #fff;
            transition: .3s ease;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        input:checked + .slider {
            background-color: var(--aurora-1);
            border-color: var(--aurora-1);
            box-shadow: 0 0 8px rgba(139, 92, 246, 0.3);
        }
        input:checked + .slider:before {
            transform: translateX(22px);
        }
        .push-sub-settings {
            transition: all 0.3s ease;
            margin-top: 10px;
            padding-left: 10px;
            border-left: 2px solid rgba(139, 92, 246, 0.2);
        }
        .push-sub-settings.disabled {
            opacity: 0.4;
            pointer-events: none;
            border-left-color: rgba(255,255,255,0.05);
        }
        </style>
    `;

    const html = `
        ${styles}
        <div class="push-row">
            <div class="push-label">
                <span class="push-title">Enable Push Notifications</span>
                <span class="push-desc">Receive real-time notifications on this device</span>
            </div>
            <label class="switch">
                <input type="checkbox" id="push-master-toggle" ${enabled ? 'checked' : ''}>
                <span class="slider"></span>
            </label>
        </div>
        
        <div class="push-sub-settings ${enabled ? '' : 'disabled'}" id="push-sub-settings-container">
            <div class="push-row">
                <div class="push-label">
                    <span class="push-title">Budget Warn Alert</span>
                    <span class="push-desc">Notify when a category budget reaches 80% used</span>
                </div>
                <label class="switch">
                    <input type="checkbox" class="push-pref-toggle" data-pref="budget_alert" ${prefs.budget_alert ? 'checked' : ''} ${enabled ? '' : 'disabled'}>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="push-row">
                <div class="push-label">
                    <span class="push-title">Budget Exceeded Alert</span>
                    <span class="push-desc">Notify when you exceed a category budget</span>
                </div>
                <label class="switch">
                    <input type="checkbox" class="push-pref-toggle" data-pref="budget_exceeded" ${prefs.budget_exceeded ? 'checked' : ''} ${enabled ? '' : 'disabled'}>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="push-row">
                <div class="push-label">
                    <span class="push-title">Savings Goal Completed</span>
                    <span class="push-desc">Notify when a savings target is fully achieved</span>
                </div>
                <label class="switch">
                    <input type="checkbox" class="push-pref-toggle" data-pref="savings_goal" ${prefs.savings_goal ? 'checked' : ''} ${enabled ? '' : 'disabled'}>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="push-row">
                <div class="push-label">
                    <span class="push-title">Monthly Spending Summary</span>
                    <span class="push-desc">Receive an end-of-month review of spent vs saved</span>
                </div>
                <label class="switch">
                    <input type="checkbox" class="push-pref-toggle" data-pref="monthly_summary" ${prefs.monthly_summary ? 'checked' : ''} ${enabled ? '' : 'disabled'}>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="push-row">
                <div class="push-label">
                    <span class="push-title">Security Login Alert</span>
                    <span class="push-desc">Instant alert when a new login occurs</span>
                </div>
                <label class="switch">
                    <input type="checkbox" class="push-pref-toggle" data-pref="login_alert" ${prefs.login_alert ? 'checked' : ''} ${enabled ? '' : 'disabled'}>
                    <span class="slider"></span>
                </label>
            </div>
        </div>
    `;

    area.innerHTML = html;

    const master = document.getElementById('push-master-toggle');
    const container = document.getElementById('push-sub-settings-container');
    const subToggles = document.querySelectorAll('.push-pref-toggle');

    master.addEventListener('change', async () => {
        master.disabled = true;
        if (master.checked) {
            if (Notification.permission === 'denied') {
                Swal.showValidationMessage('Permission was previously blocked. Please enable notification permissions in browser settings.');
                master.checked = false;
                master.disabled = false;
                return;
            }
            await requestPushPermission();
            const currentSub = await swRegistration?.pushManager?.getSubscription();
            if (currentSub && Notification.permission === 'granted') {
                container.classList.remove('disabled');
                subToggles.forEach(t => t.disabled = false);
            } else {
                master.checked = false;
                if (Notification.permission === 'denied') {
                    Swal.showValidationMessage('Notification permission denied by user.');
                }
            }
        } else {
            await disablePushNotifications();
            container.classList.add('disabled');
            subToggles.forEach(t => {
                t.disabled = true;
            });
        }
        master.disabled = false;
    });

    subToggles.forEach(toggle => {
        toggle.addEventListener('change', async () => {
            const newPrefs = {};
            document.querySelectorAll('.push-pref-toggle').forEach(t => {
                newPrefs[t.getAttribute('data-pref')] = t.checked;
            });
            await savePushPrefs(newPrefs);
        });
    });
}

