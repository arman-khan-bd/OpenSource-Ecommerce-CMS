// OpenSource eCommerce CMS — Native PWA & Web Push Service Worker
// Built on Open Web Standards: Web Push API & RFC 8292 VAPID Protocol

const CACHE_NAME = 'cms-pwa-v2';
const STATIC_ASSETS = [
  '/',
  '/admin',
  '/manifest.json',
  '/icon-192.png',
  '/icon-512.png',
  '/badge-72.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_ASSETS).catch(() => {});
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Display Notification in the Service Worker (sw.js)
// When a push arrives, the Service Worker wakes up and shows the native system dropdown notification:
self.addEventListener('push', (event) => {
  let data = { title: 'New Alert', body: 'Default body', url: '/' };

  if (event.data) {
    try {
      data = event.data.json();
    } catch (e) {
      data.body = event.data.text() || data.body;
    }
  }

  const options = {
    body: data.body || 'Default body',
    icon: data.icon || '/icon-192.png',
    badge: data.badge || '/badge-72.png',
    vibrate: [100, 50, 100],
    data: {
      url: data.url || '/'
    }
  };

  event.waitUntil(
    self.registration.showNotification(data.title || 'New Alert', options)
  );
});

// Handle clicking the dropdown notification
self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const targetUrl = (event.notification.data && event.notification.data.url) 
    ? event.notification.data.url 
    : '/';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
      for (let client of windowClients) {
        if (client.url === targetUrl && 'focus' in client) {
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(targetUrl);
      }
    })
  );
});

// Network-first with cache fallback strategy for offline support
self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;

  let url;
  try {
    url = new URL(event.request.url);
  } catch (e) {
    return;
  }

  // Only handle standard HTTP and HTTPS requests; ignore chrome-extension://, moz-extension://, etc.
  if (url.protocol !== 'http:' && url.protocol !== 'https:') {
    return;
  }

  // Skip API or dynamic admin save requests from static caching
  if (url.pathname.startsWith('/api/') || url.pathname.includes('/save') || url.pathname.includes('/delete')) {
    return;
  }

  event.respondWith(
    fetch(event.request)
      .then((networkResponse) => {
        if (networkResponse && networkResponse.status === 200) {
          const responseToCache = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, responseToCache).catch(() => {});
          }).catch(() => {});
        }
        return networkResponse;
      })
      .catch(() => {
        return caches.match(event.request).then((cachedResponse) => {
          if (cachedResponse) {
            return cachedResponse;
          }
          if (event.request.mode === 'navigate') {
            return caches.match('/');
          }
        });
      })
  );
});
