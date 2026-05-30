function formatTime(seconds) {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return m + ':' + String(s).padStart(2, '0');
}

async function forgotPassword(apiUrl, csrfToken, role = 'user', prefillEmail = '') {
    const { value: email } = await Swal.fire({
        title: 'Reset Password',
        html: `<p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 10px;">Enter your registered email to receive an OTP</p>`,
        input: 'email',
        inputValue: prefillEmail,
        inputPlaceholder: 'Enter your email address',
        inputAttributes: {
            style: 'max-width: 320px; margin: 0 auto; padding: 10px 14px;'
        },
        showCancelButton: true,
        confirmButtonText: 'Send OTP',
        confirmButtonColor: '#8b5cf6',
        inputValidator: (v) => !v && 'Please enter your email'
    });

    if (!email) return;

    const otpResult = await sendOtpWithRateLimit(apiUrl, csrfToken, email, role);
    if (!otpResult || !otpResult.success) return;
    const otpRemaining = otpResult.remaining || 120;

    const { value: otp } = await Swal.fire({
        title: 'Enter OTP',
        html: `<p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 10px;">We sent a 6-digit OTP to <b>${escapeHtml(email)}</b></p>
               <div id="otpCountdown" style="font-size:1.5rem; font-weight:700; color:#8b5cf6; font-family:var(--font-mono); margin:10px 0;">${formatTime(otpRemaining)}</div>
               <p style="font-size:0.75rem; color:var(--text-muted);">OTP expires in 2 minutes. You can request a new one after it expires.</p>`,
        input: 'text',
        inputAttributes: {
            maxlength: 6,
            autocomplete: 'off',
            placeholder: '6-digit OTP',
            style: 'max-width: 200px; margin: 0 auto; padding: 10px 14px; text-align: center; letter-spacing: 4px;'
        },
        showCancelButton: true,
        confirmButtonText: 'Verify OTP',
        confirmButtonColor: '#8b5cf6',
        didOpen: () => {
            let remaining = otpRemaining;
            const timer = setInterval(() => {
                remaining--;
                const el = document.getElementById('otpCountdown');
                if (el) el.textContent = formatTime(remaining);
                if (remaining <= 0) {
                    clearInterval(timer);
                    Swal.close();
                }
            }, 1000);
        },
        inputValidator: (v) => {
            if (!v || v.length !== 6) return 'Please enter a valid 6-digit OTP';
        },
        preConfirm: async (otpVal) => {
            Swal.showLoading();
            const verifyRes = await fetch(`${apiUrl}?action=verify_otp_only`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ email: email, otp: otpVal })
            });
            const verifyData = await verifyRes.json();

            if (verifyData.status !== 'success') {
                Swal.showValidationMessage(verifyData.message);
                Swal.hideLoading();
                return false;
            }
            return otpVal;
        }
    });

    if (!otp) return;

    const { value: newPassword } = await Swal.fire({
        title: 'Create New Password',
        html: `<p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 10px;">Must contain: 8+ chars, uppercase, lowercase, number & special char</p>`,
        input: 'password',
        inputAttributes: {
            autocomplete: 'new-password',
            placeholder: 'New Password',
            style: 'max-width: 320px; margin: 0 auto; padding: 10px 14px;'
        },
        showCancelButton: true,
        confirmButtonText: 'Save Password',
        confirmButtonColor: '#10b981',
        inputValidator: (v) => {
            if (!v) return 'Please enter a new password';
            if (v.length < 8) return 'Password must be at least 8 characters';
        }
    });

    if (!newPassword) return;

    Swal.fire({
        title: 'Saving...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    const finalRes = await fetch(`${apiUrl}?action=reset_password_final`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({
            email: email,
            new_password: newPassword,
            role: role
        })
    });
    const finalData = await finalRes.json();

    Swal.fire(
        finalData.status === 'success' ? 'Success!' : 'Error',
        finalData.message,
        finalData.status
    );
}

async function sendOtpWithRateLimit(apiUrl, csrfToken, email, role) {
    Swal.fire({
        title: 'Sending OTP...',
        html: 'Please wait while we send the OTP to your email.',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000);

        const res = await fetch(`${apiUrl}?action=request_password_reset`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ email: email, role: role }),
            signal: controller.signal
        });
        clearTimeout(timeoutId);
        const data = await res.json();
        Swal.close();

        if (data.status === 'success') {
            return { success: true, remaining: data.remaining || 120 };
        }

        if (data.status === 'rate_limited') {
            await showCountdownModal(data.remaining);
            return sendOtpWithRateLimit(apiUrl, csrfToken, email, role);
        }

        Swal.fire('Error', data.message, 'error');
        return { success: false };
    } catch (err) {
        Swal.close();
        if (err.name === 'AbortError') {
            Swal.fire('Timeout', 'Request took too long. OTP may still have been sent to your email — please check.', 'warning');
        } else {
            Swal.fire('Error', 'An error occurred. Please try again.', 'error');
        }
        return { success: false };
    }
}

function showCountdownModal(seconds) {
    return new Promise((resolve) => {
        let remaining = seconds;
        let timerInterval;

        Swal.fire({
            title: 'Please Wait',
            html: `<p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 12px;">An OTP was already sent to your email.</p>
                   <div id="countdownTimer" style="font-size: 2rem; font-weight: 700; color: #8b5cf6; font-family: var(--font-mono);">${formatTime(remaining)}</div>
                   <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 8px;">You can request a new OTP after the timer expires.</p>`,
            allowOutsideClick: false,
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonText: 'Cancel',
            cancelButtonColor: '#ef4444',
            didOpen: () => {
                timerInterval = setInterval(() => {
                    remaining--;
                    const el = document.getElementById('countdownTimer');
                    if (el) el.textContent = formatTime(remaining);

                    if (remaining <= 0) {
                        clearInterval(timerInterval);
                        Swal.close();
                    }
                }, 1000);
            },
            willClose: () => {
                clearInterval(timerInterval);
            }
        }).then((result) => {
            if (result.dismiss === Swal.DismissReason.cancel) {
                resolve(false);
            } else {
                resolve(true);
            }
        });
    });
}

function formatTime(seconds) {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${m}:${s.toString().padStart(2, '0')}`;
}
