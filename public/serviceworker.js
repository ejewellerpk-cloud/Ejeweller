// Increment VERSION whenever you deploy — forces clients to drop old caches
const VERSION = 'v2.7';
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

const FALLBACK_ICON = '/images/icons/icon-192x192.png';

/** Always a Response — undefined from respondWith → "Failed to convert value to 'Response'". */
const emptyResponse = (status = 504) =>
    new Response('', { status, statusText: status === 504 ? 'Gateway Timeout' : 'Error' });

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
    // HTML / SPA navigations — never wrap. Failed fetch + missing cache returned
    // undefined and crashed with "Failed to convert value to 'Response'".
    if (request.mode === 'navigate' || request.destination === 'document') return true;

    const url = request.url || '';
    // Vite build bundles — never intercept. Wrapping large hashed JS caused
    // net::ERR_CONNECTION_RESET 200 (OK) when the Response was truncated.
    if (url.includes('/build/') || url.includes('/build/assets/')) return true;
    if (/\.(js|css|mjs|map)(\?|$)/i.test(url) && url.includes('/assets/')) return true;
    // Never touch API/XHR — prevents redirect/cache loops (508) on production
    if (url.includes('/api/') || url.includes('/install')) return true;
    if (url.includes('/analytics/')) return true;
    if (MEDIA_EXT.test(url)) return true;
    if (url.includes('/storage/') && MEDIA_EXT.test(url)) return true;
    // Any non-image under /storage/ (uploads, PDFs, etc.)
    if (url.includes('/storage/') && !IMAGE_EXT.test(url)) return true;

    return false;
};

self.addEventListener('fetch', (event) => {
    // Do not call respondWith for API/build/media/navigations — browser talks to network directly.
    if (mustBypassCache(event.request)) {
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
                    .catch(() =>
                        caches
                            .match(FALLBACK_ICON)
                            .then((icon) => icon || emptyResponse(404))
                    );
            })
        );
        return;
    }

    // Fonts / other static GET: network only; never resolve respondWith to undefined.
    event.respondWith(
        fetch(event.request).catch(() =>
            caches.match(event.request).then((cached) => cached || emptyResponse(504))
        )
    );
});
