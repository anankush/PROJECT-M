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

// Helper: get the CSRF-protected logout URL injected by PHP via <meta name="logout-url">
function getLogoutUrl() {
    return document.querySelector('meta[name="logout-url"]')?.getAttribute('content') || '#';
}

// Strict Logout on Page Refresh (Reload)
if (performance.getEntriesByType('navigation')[0]?.type === 'reload') {
    sessionStorage.clear();
    window.location.href = getLogoutUrl();
}
