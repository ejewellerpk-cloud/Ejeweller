// Increment VERSION whenever you deploy — forces clients to drop old caches
const VERSION = 'v2.2';
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

self.addEventListener('install', (event) => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(staticCacheName).then((cache) => cache.addAll(filesToCache))
    );
});

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
            .then(() => self.clients.claim())
    );
});

const MEDIA_EXT = /\.(mp4|webm|ogg|wav|mp3|mov|avi|mkv|m4v|flv)(\?|$)/i;
const IMAGE_EXT = /\.(png|jpg|jpeg|gif|ico|svg|webp)(\?|$)/i;

/** Never cache — Chrome throws ERR_CACHE_OPERATION_NOT_SUPPORTED on large/range media. */
const mustBypassCache = (request) => {
    if (request.method !== 'GET') return true;
    if (request.headers.get('range')) return true;
    if (request.destination === 'video' || request.destination === 'audio') return true;

    const url = request.url || '';
    // Never touch API/XHR — prevents redirect/cache loops (508) on production
    if (url.includes('/api/') || url.includes('/install')) return true;
    if (MEDIA_EXT.test(url)) return true;
    if (url.includes('/storage/') && MEDIA_EXT.test(url)) return true;
    // Any non-image under /storage/ (uploads, PDFs, etc.)
    if (url.includes('/storage/') && !IMAGE_EXT.test(url)) return true;

    return false;
};

const passthrough = (request) =>
    fetch(request, { cache: 'no-store', credentials: 'same-origin' });

self.addEventListener('fetch', (event) => {
    if (mustBypassCache(event.request)) {
        event.respondWith(passthrough(event.request));
        return;
    }

    const isImage =
        IMAGE_EXT.test(event.request.url) || event.request.url.includes('favicon.ico');

    if (isImage) {
        event.respondWith(
            caches.match(event.request).then((cached) => {
                if (cached) return cached;
                return fetch(event.request)
                    .then((networkResponse) => {
                        if (
                            networkResponse &&
                            networkResponse.status === 200 &&
                            networkResponse.type === 'basic'
                        ) {
                            const clone = networkResponse.clone();
                            caches.open(staticCacheName).then((cache) => {
                                cache.put(event.request, clone).catch(() => {});
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
        fetch(event.request).catch(() =>
            caches.match(event.request).then((cached) => {
                if (cached) return cached;
                if (event.request.mode === 'navigate') {
                    return caches.match('/offline.html');
                }
            })
        )
    );
});
