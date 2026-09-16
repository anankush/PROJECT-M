// Money Management — Service Worker
// Handles push notifications. No sensitive data here.

const CACHE_NAME = 'mm-v1';

// Calculate the base path dynamically from the location of sw.js
const BASE_PATH = self.location.pathname.substring(0, self.location.pathname.lastIndexOf('/') + 1);

const NOTIFICATION_ICON = BASE_PATH + 'assets/img/icon-192.png';
const NOTIFICATION_BADGE = BASE_PATH + 'assets/img/icon-72.png';

self.addEventListener('push', function (event) {
    if (!event.data) return;

    let payload;
    try {
        payload = event.data.json();
    } catch (e) {
        payload = { title: 'Money Management', body: event.data.text(), url: BASE_PATH + 'dashboard/' };
    }

    const title = payload.title || 'Money Management';
    const options = {
        body:    payload.body  || '',
        icon:    NOTIFICATION_ICON,
        badge:   NOTIFICATION_BADGE,
        tag:     payload.tag   || 'mm-notification',
        renotify: true,
        data:    { url: payload.url || BASE_PATH + 'dashboard/index.php' },
        actions: [
            { action: 'open',    title: 'Open Dashboard' },
            { action: 'dismiss', title: 'Dismiss' }
        ],
        vibrate: [200, 100, 200]
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    if (event.action === 'dismiss') return;

    const targetUrl = event.notification.data?.url || (BASE_PATH + 'dashboard/index.php');

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            for (const client of clientList) {
                if (client.url.includes(BASE_PATH) && 'focus' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});

self.addEventListener('pushsubscriptionchange', function (event) {
    event.waitUntil(
        self.registration.pushManager.subscribe(event.oldSubscription.options)
            .then(function (subscription) {
                return fetch(BASE_PATH + 'api/push_api.php?action=subscribe', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(subscription)
                });
            })
    );
});
