// input_validation.js
document.addEventListener('DOMContentLoaded', function() {
    const emailInputs = document.querySelectorAll('input[type="email"]');
    const passwordInputs = document.querySelectorAll('input[type="password"]');

    emailInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const email = this.value;
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email && !re.test(email)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Email Format',
                    text: 'Please enter a valid email address.',
                    background: 'rgba(20, 14, 50, 0.9)',
                    color: '#fff',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
                this.classList.add('error');
            } else {
                this.classList.remove('error');
            }
        });
    });
});
