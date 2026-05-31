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
                if (typeof Swal !== 'undefined') Swal.close();
                window.location.href = data.redirect;
                return new Promise(() => { });
            }
        } catch (e) { }

        let depth = 0;
        const path = window.location.pathname;
        if (path.includes('/admin/')) depth = 1;
        else if (path.includes('/Exp/user/')) depth = 2;
        else if (path.includes('/Sav/user/')) depth = 2;
        else if (path.includes('/auth/')) depth = 1;
        else if (path.includes('/dashboard/')) depth = 1;

        let prefix = '';
        for (let i = 0; i < depth; i++) prefix += '../';

        if (typeof Swal !== 'undefined') Swal.close();
        if (response.status === 403) {
            window.location.href = prefix + 'error.php?code=security';
        } else if (response.status === 401) {
            window.location.href = prefix + 'error.php?code=unauthorized';
        }
        return new Promise(() => { });
    }

    return response;
};

function getLogoutUrl() {
    return document.querySelector('meta[name="logout-url"]')?.getAttribute('content') || '#';
}

if (performance.getEntriesByType('navigation')[0]?.type === 'reload') {
    const logoutUrl = getLogoutUrl();
    if (logoutUrl && logoutUrl !== '#') {
        sessionStorage.clear();
        window.location.href = logoutUrl;
    }
}

const pathLower = window.location.pathname.toLowerCase();
const isAuthArea = /\/(dashboard|exp\/user|sav\/user|admin|admin_portal)/i.test(pathLower)
                && !pathLower.includes('/auth/')
                && !pathLower.includes('error.php');

if (isAuthArea) {
    let depth = 0;
    if (pathLower.includes('/admin/')) depth = 1;
    else if (pathLower.includes('/exp/user/')) depth = 2;
    else if (pathLower.includes('/sav/user/')) depth = 2;
    else if (pathLower.includes('/dashboard/')) depth = 1;

    let prefix = '';
    for (let i = 0; i < depth; i++) prefix += '../';

    setInterval(() => {
        fetch(prefix + 'includes/session_ping.php', {
            headers: { 'Accept': 'application/json' }
        }).catch(() => {});
    }, 5000);
}
