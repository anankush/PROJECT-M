
const PUSH_API = '../api/push_api.php';
let pushEnabled = false;
let swRegistration = null;

const VAPID_PUBLIC_KEY = window.VAPID_PUBLIC_KEY || '';

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw     = atob(base64);
    return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
}

async function initPush() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
    if (!VAPID_PUBLIC_KEY) return;

    try {
        const basePath = window.location.pathname.includes('/PROJECT%20M/') ? '/PROJECT%20M/' : '/';
        swRegistration = await navigator.serviceWorker.register(basePath + 'sw.js', { scope: basePath });
        const sub = await swRegistration.pushManager.getSubscription();
        pushEnabled = !!sub;
        if (sub) {
            await savePushSubscription(sub);
        }
        checkPushPermissionPrompt();
    } catch (e) {}
}

function checkPushPermissionPrompt() {
    if (Notification.permission === 'default') {
        setTimeout(showPushToast, 3000);
    }
}

function showPushToast() {
    if (typeof Swal === 'undefined') return;
    Swal.fire({
        title: '🔔 Enable Notifications?',
        text:  'Get alerts for budget limits, savings goals & security events.',
        icon:  'question',
        showCancelButton:  true,
        confirmButtonText: 'Enable',
        cancelButtonText:  'Later',
        confirmButtonColor: '#8b5cf6',
        toast: false,
        position: 'center',
    }).then(result => {
        if (result.isConfirmed) requestPushPermission();
    });
}

async function requestPushPermission() {
    if (!swRegistration) return;
    try {
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') return;
        const sub = await swRegistration.pushManager.subscribe({
            userVisibleOnly:      true,
            applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
        });
        await savePushSubscription(sub);
        pushEnabled = true;
        if (typeof showToast === 'function') showToast('Notifications enabled!', 'success');
    } catch (e) {}
}

async function savePushSubscription(sub) {
    try {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (!csrfMeta) return;
        await fetch(PUSH_API + '?action=subscribe', {
            method:  'POST',
            headers: {
                'Content-Type':  'application/json',
                'X-CSRF-Token': csrfMeta.content,
            },
            body: JSON.stringify(sub.toJSON()),
        });
    } catch (e) {}
}

async function disablePushNotifications() {
    if (!swRegistration) return;
    try {
        const sub = await swRegistration.pushManager.getSubscription();
        if (!sub) return;
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        await fetch(PUSH_API + '?action=unsubscribe', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfMeta?.content || '' },
            body:    JSON.stringify({ endpoint: sub.endpoint }),
        });
        await sub.unsubscribe();
        pushEnabled = false;
    } catch (e) {}
}

async function loadPushPrefs() {
    try {
        const res  = await fetch(PUSH_API + '?action=get_prefs');
        const data = await res.json();
        if (data.status !== 'success') return null;
        return data;
    } catch (e) { return null; }
}

async function savePushPrefs(prefs) {
    try {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        await fetch(PUSH_API + '?action=update_prefs', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfMeta?.content || '' },
            body:    JSON.stringify(prefs),
        });
    } catch (e) {}
}

document.addEventListener('DOMContentLoaded', () => {
    initPush();
});
