


function getLocalDateString(d = new Date()) {
    const tzoffset = d.getTimezoneOffset() * 60000;
    return (new Date(d.getTime() - tzoffset)).toISOString().slice(0, 10);
}



function showToast(message, type = 'success') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: type,
            title: type === 'success' ? 'Success!' : (type === 'error' ? 'Error' : 'Notification'),
            text: message,
            confirmButtonText: 'OK',
            customClass: {
                confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
        });
    } else {
        alert(message);
    }
}


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
