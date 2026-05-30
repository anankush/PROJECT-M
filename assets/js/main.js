// assets/js/main.js

// ── Toast Notifications ────────────────────────────────
function showToast(message, type = 'success') {
    if (typeof Swal !== 'undefined') {
        const isMobile = window.innerWidth <= 768;
        Swal.fire({
            toast: true,
            position: isMobile ? 'bottom' : 'top-end',
            icon: type,
            title: message,
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
            customClass: {
                popup: 'aurora-toast'
            },
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
    } else {
        console.log(`[${type}] ${message}`);
    }
}

// ── XSS-safe HTML escaping ─────────────────────────────
function escapeHtml(unsafe) {
    if (!unsafe) return '';
    return String(unsafe)
        .replace(/&/g,  '&amp;')
        .replace(/</g,  '&lt;')
        .replace(/>/g,  '&gt;')
        .replace(/"/g,  '&quot;')
        .replace(/'/g,  '&#039;');
}

// ── Cursor Glow Effect ─────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const cursorGlow = document.createElement('div');
    cursorGlow.className = 'cursor-glow';
    document.body.appendChild(cursorGlow);

    document.addEventListener('mousemove', (e) => {
        requestAnimationFrame(() => {
            cursorGlow.style.transform = `translate(${e.clientX - 250}px, ${e.clientY - 250}px)`;
            cursorGlow.style.opacity = '1';
        });
    });

    document.addEventListener('mouseleave', () => {
        cursorGlow.style.opacity = '0';
    });

    // Animate elements with .fadeInUp class
    document.querySelectorAll('.fadeInUp').forEach(el => {
        el.style.animation = 'fadeInUp 0.6s ease both';
    });
});
