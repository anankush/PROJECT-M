// assets/js/csrf.js
const originalFetch = window.fetch;
window.fetch = async function () {
    let [resource, config] = arguments;
    if (config && (config.method === 'POST' || config.method === 'PUT' || config.method === 'DELETE')) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            config.headers = {
                ...config.headers,
                'X-CSRF-Token': csrfToken
            };
        }
    }
    return await originalFetch(resource, config);
};

// Strict Logout on Page Refresh (Reload)
if (performance.getEntriesByType('navigation')[0]?.type === 'reload') {
    sessionStorage.clear();
    let path = window.location.pathname;
    if (path.includes('/user/') || path.includes('/Exp/') || path.includes('/Sav/')) {
        window.location.href = '../../auth/logout.php';
    } else {
        window.location.href = '../auth/logout.php';
    }
}

