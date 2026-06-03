
async function isDisposableEmail(email) {
    if (!email || !email.includes('@')) {
        return false;
    }

    const parts = email.trim().split('@');
    const domain = parts[parts.length - 1].toLowerCase();

    // Source 1: Kickbox Open API (Direct live query)
    try {
        const response = await fetch(`https://open.kickbox.com/v1/disposable/${domain}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            if (data && typeof data.disposable !== 'undefined') {
                return data.disposable === true;
            }
        }
    } catch (err) {
    }

    // Source 2: GitHub Community Disposable Domains Database (Live Fallback)
    try {
        const response = await fetch('https://raw.githubusercontent.com/disposable-email-domains/disposable-email-domains/master/disposable_email_blocklist.conf', {
            method: 'GET'
        });
        
        if (response.ok) {
            const text = await response.text();
            if (text) {
                const domains = text.split('\n').map(d => d.trim().toLowerCase()).filter(Boolean);
                return domains.includes(domain);
            }
        }
    } catch (err) {
    }

    // If both live check pathways fail (e.g. offline/no-network), registration is allowed
    // to prevent blocking genuine users, relying entirely on the server-side MX/TXT validator.
    return false;
}
