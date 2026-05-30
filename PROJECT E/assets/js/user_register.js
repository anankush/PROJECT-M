document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registerForm');
    const errorDiv = document.getElementById('registerError');
    const submitBtn = form.querySelector('.auth-submit');
    const passwordInput = form.querySelector('[name="password"]');
    const confirmInput = form.querySelector('[name="confirm_password"]');
    const strengthBar = document.querySelector('.password-strength-bar');
    const strengthHint = document.querySelector('.password-hint');

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    InputValidator.initFormRestrictions('registerForm');

    passwordInput.addEventListener('input', () => {
        const password = passwordInput.value;
        const strength = checkPasswordStrength(password);

        strengthBar.className = 'password-strength-bar';
        if (password.length === 0) {
            strengthBar.style.width = '0';
            strengthHint.textContent = 'Min 8 chars, uppercase, lowercase, number & special char';
        } else if (strength === 'weak') {
            strengthBar.classList.add('strength-weak');
            strengthHint.textContent = 'Weak — add uppercase, numbers & special chars';
        } else if (strength === 'medium') {
            strengthBar.classList.add('strength-medium');
            strengthHint.textContent = 'Medium — add more variety';
        } else {
            strengthBar.classList.add('strength-strong');
            strengthHint.textContent = 'Strong password!';
        }
    });

    confirmInput.addEventListener('input', () => {
        if (confirmInput.value && confirmInput.value !== passwordInput.value) {
            confirmInput.style.borderColor = 'var(--danger)';
        } else {
            confirmInput.style.borderColor = '';
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const email = form.querySelector('[name="email"]').value.trim();
        const password = passwordInput.value;
        const confirmPassword = confirmInput.value;

        const emailValidation = InputValidator.validateEmail(email);
        if (!emailValidation.valid) {
            showError(emailValidation.message);
            return;
        }

        const passwordValidation = InputValidator.validatePasswordRegister(password);
        if (!passwordValidation.valid) {
            showError(passwordValidation.message);
            return;
        }

        const confirmValidation = InputValidator.validateConfirmPassword(password, confirmPassword);
        if (!confirmValidation.valid) {
            showError(confirmValidation.message);
            return;
        }

        const strength = checkPasswordStrength(password);
        if (strength === 'weak') {
            showError('Password is too weak. Use uppercase, lowercase, numbers & special chars.');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating account...';

        try {
            const res = await fetch(`${API_URL}?action=user_register`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ email, password })
            });

            const data = await res.json();

            if (data.status === 'success') {
                window.location.href = 'login.php?registered=1';
            } else {
                showError(data.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-user-plus"></i> Create Account';
            }
        } catch (err) {
            showError('An error occurred. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-user-plus"></i> Create Account';
        }
    });

    document.querySelectorAll('.password-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.parentElement.querySelector('input');
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });
    });

    function showError(message) {
        errorDiv.textContent = message;
        errorDiv.classList.add('show');
        setTimeout(() => errorDiv.classList.remove('show'), 5000);
    }

    function checkPasswordStrength(password) {
        let score = 0;
        if (password.length >= 8) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^a-zA-Z0-9]/.test(password)) score++;

        if (score <= 2) return 'weak';
        if (score <= 3) return 'medium';
        return 'strong';
    }
});
