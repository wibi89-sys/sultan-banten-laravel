const CACHE_NAME = 'telram-ekspirasi-v2';
const ASSETS_TO_CACHE = [
  '/telram-offline.html',
  '/manifest.json',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
  '/icons/icon-maskable-192.png',
  '/icons/icon-maskable-512.png',
  '/icons/icon.svg'
];

// Install Event: Cache Core Assets & skip waiting immediately
self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE);
    })
  );
});

// Activate Event: Clean up all older caches and take immediate control of clients
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((name) => {
          if (name !== CACHE_NAME) {
            console.log('[SW] Menghapus cache lama:', name);
            return caches.delete(name);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch Event:
// 1. Navigation / HTML requests: Network-First agar fitur terbaru selalu termuat saat online,
//    fallback ke cache jika offline.
// 2. Static assets (icons, manifest): Stale-While-Revalidate untuk performa kilat.
self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;

  const url = new URL(event.request.url);

  // Strategi Network-First untuk navigasi halaman & file HTML
  const isHtmlRequest = event.request.mode === 'navigate' ||
                        url.pathname.endsWith('.html') ||
                        url.pathname === '/' ||
                        url.pathname.includes('/kalkulator-telram');

  if (isHtmlRequest) {
    event.respondWith(
      fetch(event.request)
        .then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            const responseClone = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(event.request, responseClone);
            });
          }
          return networkResponse;
        })
        .catch(() => {
          // Ketika offline, sajikan dari cache
          return caches.match(event.request).then((cachedResponse) => {
            return cachedResponse || caches.match('/telram-offline.html');
          });
        })
    );
    return;
  }

  // Strategi Stale-While-Revalidate untuk aset statis (icons, manifest)
  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      const fetchPromise = fetch(event.request)
        .then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200 && url.origin === self.location.origin) {
            const responseToCache = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(event.request, responseToCache);
            });
          }
          return networkResponse;
        })
        .catch(() => {/* Offline, biarkan */});

      return cachedResponse || fetchPromise;
    })
  );
});

