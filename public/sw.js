/**
 * SULTAN Banten - Progressive Web App Service Worker
 * Versi Cache: sultan-banten-v1.0.0
 */

const CACHE_NAME = 'sultan-banten-v1.0.0';

// Aset statis inti yang selalu di-cache saat instalasi
const PRECACHE_ASSETS = [
  '/offline.html',
  '/manifest.json',
  '/assets/css/modern-theme.css',
  '/assets/icons/icon-144.png',
  '/assets/icons/icon-180.png',
  '/assets/icons/icon-192.png',
  '/assets/icons/icon-512.png',
  '/assets/icons/icon-maskable-192.png',
  '/assets/icons/icon-maskable-512.png',
  '/assets/favicon.ico'
];

// 1. Install Event: Pre-cache aset statis penting
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(PRECACHE_ASSETS);
    }).then(() => self.skipWaiting())
  );
});

// 2. Activate Event: Bersihkan cache versi lama dan klaim kontrol klien
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((name) => name !== CACHE_NAME)
          .map((name) => caches.delete(name))
      );
    }).then(() => self.clients.claim())
  );
});

// 3. Fetch Event: Strategi Caching Pintar
self.addEventListener('fetch', (event) => {
  const request = event.request;

  // Hanya tangani metode GET
  if (request.method !== 'GET') {
    return;
  }

  const url = new URL(request.url);

  // Abaikan request ke skema selain http/https (misal chrome-extension://)
  if (!url.protocol.startsWith('http')) {
    return;
  }

  // A. Permintaan Navigasi Halaman HTML: Network-First dengan Fallback ke Offline
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((networkResponse) => {
          // Jika sukses, salin ke cache untuk cadangan offline
          if (networkResponse && networkResponse.status === 200) {
            const responseToCache = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(request, responseToCache);
            });
          }
          return networkResponse;
        })
        .catch(() => {
          // Jika jaringan gagal, coba ambil dari cache
          return caches.match(request).then((cachedResponse) => {
            if (cachedResponse) {
              return cachedResponse;
            }
            // Jika halaman belum pernah dibuka, tampilkan offline.html
            return caches.match('/offline.html');
          });
        })
    );
    return;
  }

  // B. Aset Statis Lokal (CSS, JS, Fonts, Gambar di /assets/ atau /uploads/): Stale-While-Revalidate
  const isStaticAsset = url.origin === self.location.origin && 
    (url.pathname.startsWith('/assets/') || url.pathname.startsWith('/uploads/'));

  if (isStaticAsset) {
    event.respondWith(
      caches.match(request).then((cachedResponse) => {
        const fetchPromise = fetch(request).then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            const responseToCache = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(request, responseToCache);
            });
          }
          return networkResponse;
        }).catch(() => cachedResponse);

        return cachedResponse || fetchPromise;
      })
    );
    return;
  }

  // C. Permintaan Lainnya: Coba Network dulu, jika gagal cari di Cache
  event.respondWith(
    fetch(request).catch(() => caches.match(request))
  );
});

// Listener untuk pesan skip waiting dari halaman
self.addEventListener('message', (event) => {
  if (event.data && event.data.action === 'skipWaiting') {
    self.skipWaiting();
  }
});
