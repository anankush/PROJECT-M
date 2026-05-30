document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('adminLoginForm');
    const errorDiv = document.getElementById('loginError');
    const submitBtn = form.querySelector('.auth-submit');

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    InputValidator.initFormRestrictions('adminLoginForm');

    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    if (error) {
        showError(getErrorMessage(error));
    }

    const registered = urlParams.get('registered');
    if (registered) {
        showSuccess('Account created successfully! Please login.');
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const email = form.querySelector('[name="email"]').value.trim();
        const password = form.querySelector('[name="password"]').value;
        const adminKey = form.querySelector('[name="admin_key"]').value;

        const emailValidation = InputValidator.validateEmail(email);
        if (!emailValidation.valid) {
            showError(emailValidation.message);
            return;
        }

        const passwordValidation = InputValidator.validatePasswordLogin(password);
        if (!passwordValidation.valid) {
            showError(passwordValidation.message);
            return;
        }

        if (!adminKey) {
            showError('Admin Key is required.');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';

        try {
            const res = await fetch(`${API_URL}?action=admin_login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ email, password, admin_key: adminKey })
            });

            const data = await res.json();

            if (data.status === 'success') {
                window.location.href = 'dashboard.php';
            } else {
                showError(data.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-shield-alt"></i> Admin Login';
            }
        } catch (err) {
            showError('An error occurred. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-shield-alt"></i> Admin Login';
        }
    });

    const toggleBtn = document.querySelector('.password-toggle');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const input = form.querySelector('[name="password"]');
            const icon = toggleBtn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });
    }

    function showError(message) {
        errorDiv.textContent = message;
        errorDiv.classList.add('show');
        setTimeout(() => errorDiv.classList.remove('show'), 5000);
    }

    function showSuccess(message) {
        const successDiv = document.getElementById('loginSuccess');
        if (successDiv) {
            successDiv.textContent = message;
            successDiv.classList.add('show');
            setTimeout(() => successDiv.classList.remove('show'), 5000);
        }
    }

    function getErrorMessage(error) {
        const messages = {
            'invalid_credentials': 'Invalid email, password, or admin key.',
            'session_expired': 'Your session has expired. Please login again.'
        };
        return messages[error] || '';
    }
});
