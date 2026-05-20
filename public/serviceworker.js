// Increment the version number whenever you want to force a cache refresh
const VERSION = 'v1.8';
const staticCacheName = "pwa-" + VERSION;

const filesToCache = [
    '/offline.html',
    '/images/icons/icon-72x72.png',
    '/images/icons/icon-96x96.png',
    '/images/icons/icon-128x128.png',
    '/images/icons/icon-144x144.png',
    '/images/icons/icon-152x152.png',
    '/images/icons/icon-192x192.png',
    '/images/icons/icon-384x384.png',
    '/images/icons/icon-512x512.png',
];

// Cache on install
self.addEventListener("install", event => {
    console.log(`%c PWA Update Available! Updating to version: ${VERSION} `, 'background: #f97316; color: #fff; font-weight: bold; padding: 4px; border-radius: 4px;');
    self.skipWaiting();
    event.waitUntil(
        caches.open(staticCacheName)
            .then(cache => {
                return cache.addAll(filesToCache);
            })
    )
});

// Clear old caches on activate
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames
                    .filter(cacheName => (cacheName.startsWith("pwa-") && cacheName !== staticCacheName))
                    .map(cacheName => caches.delete(cacheName))
            );
        }).then(() => {
            console.log(`%c PWA Updated Successfully to version: ${VERSION} `, 'background: #10b981; color: #fff; font-weight: bold; padding: 4px; border-radius: 4px;');
            return self.clients.claim();
        })
    );
});

// Smart Fetch Handler
self.addEventListener("fetch", event => {
    // Skip non-GET requests, API requests, and video/audio files
    if (event.request.method !== 'GET' ||
        event.request.url.includes('/api/') ||
        event.request.url.match(/\.(mp4|webm|ogg|wav|mp3|mov|avi|mkv)/i)) {
        return;
    }

    const isImage = event.request.url.match(/\.(png|jpg|jpeg|gif|ico|svg)$/i) || event.request.url.includes('favicon.ico');

    // For image assets, use a Cache-First with Network-Fallback & Runtime Caching strategy
    if (isImage) {
        event.respondWith(
            caches.match(event.request).then(cachedResponse => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(event.request).then(networkResponse => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(staticCacheName).then(cache => {
                            cache.put(event.request, responseToCache);
                        });
                    }
                    return networkResponse;
                }).catch(() => {
                    // Fallback to PWA icon if completely offline and not in cache
                    return caches.match('/images/icons/icon-192x192.png');
                });
            })
        );
        return;
    }

    // Network First strategy for the main application pages to ensure fresh content
    event.respondWith(
        fetch(event.request)
            .then(response => {
                return response;
            })
            .catch(() => {
                // If network fails, try cache
                return caches.match(event.request).then(cachedResponse => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // If offline and navigate, show offline page
                    if (event.request.mode === 'navigate') {
                        return caches.match('/offline.html');
                    }
                });
            })
    );
});
