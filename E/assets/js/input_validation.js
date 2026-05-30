function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

function handleApiResponse(response) {
    if (response.status === 404) {
        window.location.href = (typeof BASE_URL !== 'undefined' ? BASE_URL : '/') + '404.php';
        return null;
    }
    return response;
}

const InputValidator = {
    isValidEmail(email) {
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        return emailRegex.test(email);
    },

    hasDangerousChars(str) {
        const dangerous = /[<>\"'`;\\\/\-\-|{}()]/;
        return dangerous.test(str);
    },

    sanitizeInput(str) {
        if (typeof str !== 'string') return str;
        return str
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },

    validateEmail(email) {
        if (!email || email.trim() === '') {
            return { valid: false, message: 'Email is required' };
        }
        if (!this.isValidEmail(email)) {
            return { valid: false, message: 'Invalid email format' };
        }
        return { valid: true };
    },

    validatePasswordLogin(password) {
        if (!password || password === '') {
            return { valid: false, message: 'Password is required' };
        }
        return { valid: true };
    },

    validatePasswordRegister(password) {
        if (!password || password === '') {
            return { valid: false, message: 'Password is required' };
        }
        if (password.length < 8) {
            return { valid: false, message: 'Password must be at least 8 characters' };
        }
        if (!/[a-z]/.test(password)) {
            return { valid: false, message: 'Password must contain a lowercase letter' };
        }
        if (!/[A-Z]/.test(password)) {
            return { valid: false, message: 'Password must contain an uppercase letter' };
        }
        if (!/[0-9]/.test(password)) {
            return { valid: false, message: 'Password must contain a number' };
        }
        if (!/[^a-zA-Z0-9]/.test(password)) {
            return { valid: false, message: 'Password must contain a special character' };
        }
        return { valid: true };
    },

    validateConfirmPassword(password, confirmPassword) {
        if (!confirmPassword || confirmPassword === '') {
            return { valid: false, message: 'Please confirm your password' };
        }
        if (password !== confirmPassword) {
            return { valid: false, message: 'Passwords do not match' };
        }
        return { valid: true };
    },

    restrictInput(inputElement) {
        inputElement.addEventListener('keypress', (e) => {
            const char = String.fromCharCode(e.which);
            if (/[<>]/.test(char)) {
                e.preventDefault();
            }
        });

        inputElement.addEventListener('paste', (e) => {
            setTimeout(() => {
                inputElement.value = this.sanitizeInput(inputElement.value);
            }, 0);
        });
    },

    initFormRestrictions(formId) {
        const form = document.getElementById(formId);
        if (!form) return;

        form.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]').forEach(input => {
            this.restrictInput(input);
        });
    }
};
