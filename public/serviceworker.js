// Increment the version number whenever you want to force a cache refresh
const VERSION = 'v2.0';
const staticCacheName = 'pwa-' + VERSION;

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

const MEDIA_EXT = /\.(mp4|webm|ogg|wav|mp3|m4v|mov|avi|mkv|flv)(\?.*)?$/i;

function isMediaRequest(request) {
    try {
        const path = new URL(request.url).pathname;
        return MEDIA_EXT.test(path);
    } catch (e) {
        return MEDIA_EXT.test(request.url);
    }
}

// Cache on install
self.addEventListener('install', (event) => {
    console.log(
        `%c PWA Update Available! Updating to version: ${VERSION} `,
        'background: #f97316; color: #fff; font-weight: bold; padding: 4px; border-radius: 4px;'
    );
    self.skipWaiting();
    event.waitUntil(
        caches.open(staticCacheName).then((cache) => cache.addAll(filesToCache))
    );
});

// Clear old caches on activate
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((cacheNames) =>
                Promise.all(
                    cacheNames
                        .filter((name) => name.startsWith('pwa-') && name !== staticCacheName)
                        .map((name) => caches.delete(name))
                )
            )
            .then(() => {
                console.log(
                    `%c PWA Updated Successfully to version: ${VERSION} `,
                    'background: #10b981; color: #fff; font-weight: bold; padding: 4px; border-radius: 4px;'
                );
                return self.clients.claim();
            })
    );
});

// Fetch: never put videos/audio through Cache API (avoids ERR_CACHE_OPERATION_NOT_SUPPORTED)
self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const url = event.request.url;

    if (url.includes('/api/') || isMediaRequest(event.request)) {
        event.respondWith(
            fetch(event.request).catch(() =>
                new Response('', { status: 503, statusText: 'Media unavailable offline' })
            )
        );
        return;
    }

    const isImage =
        /\.(png|jpg|jpeg|gif|ico|svg|webp)(\?.*)?$/i.test(new URL(url).pathname) ||
        url.includes('favicon.ico');

    if (isImage) {
        event.respondWith(
            caches.match(event.request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(event.request)
                    .then((networkResponse) => {
                        if (networkResponse && networkResponse.status === 200) {
                            const responseToCache = networkResponse.clone();
                            caches.open(staticCacheName).then((cache) => {
                                cache.put(event.request, responseToCache).catch(() => {});
                            });
                        }
                        return networkResponse;
                    })
                    .catch(() => caches.match('/images/icons/icon-192x192.png'));
            })
        );
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((response) => response)
            .catch(() =>
                caches.match(event.request).then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    if (event.request.mode === 'navigate') {
                        return caches.match('/offline.html');
                    }
                })
            )
    );
});
