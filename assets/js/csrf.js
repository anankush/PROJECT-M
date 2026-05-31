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
    const response = await originalFetch(resource, config);
    
    if (response.status === 401 || response.status === 403) {
        try {
            const clone = response.clone();
            const data = await clone.json();
            if (data && data.redirect) {
                window.location.href = data.redirect;
                return response;
            }
        } catch (e) {}
        
        let depth = 0;
        const path = window.location.pathname;
        if (path.includes('/admin/')) depth = 1;
        else if (path.includes('/Exp/user/')) depth = 2;
        else if (path.includes('/Sav/user/')) depth = 2;
        else if (path.includes('/auth/')) depth = 1;
        else if (path.includes('/dashboard/')) depth = 1;
        
        let prefix = '';
        for (let i = 0; i < depth; i++) {
            prefix += '../';
        }
        
        if (response.status === 403) {
            window.location.href = prefix + 'error.php?code=security';
        } else if (response.status === 401) {
            window.location.href = prefix + 'error.php?code=unauthorized';
        }
    }
    
    return response;
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
