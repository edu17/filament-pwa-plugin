const APP_SCOPE = @json($appScope);
const CACHE_NAME = @json($cacheName);
const CACHE_PREFIX = @json($cachePrefix);
const OFFLINE_URL = @json($offlineUrl);
const PRECACHE_URLS = @json($precacheUrls);

self.addEventListener('install', (event) => {
    const sameOriginUrls = PRECACHE_URLS.filter((url) => {
        return new URL(url, self.location.origin).origin === self.location.origin;
    });

    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => Promise.all(
                sameOriginUrls.map((url) => cache.add(url).catch(() => undefined)),
            ))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys
                    .filter((key) => key.startsWith(CACHE_PREFIX) && key !== CACHE_NAME)
                    .map((key) => caches.delete(key)),
            ))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);
    const isInScope = APP_SCOPE === '/'
        || url.pathname === APP_SCOPE
        || url.pathname.startsWith(`${APP_SCOPE}/`);

    if (request.method !== 'GET' || url.origin !== self.location.origin || !isInScope) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(OFFLINE_URL)),
        );

        return;
    }

    if (! ['script', 'style', 'font'].includes(request.destination)) {
        return;
    }

    event.respondWith(
        caches.match(request).then((cachedResponse) => {
            const networkResponse = fetch(request)
                .then((response) => {
                    if (response.ok) {
                        const responseClone = response.clone();

                        caches.open(CACHE_NAME)
                            .then((cache) => cache.put(request, responseClone));
                    }

                    return response;
                })
                .catch(() => cachedResponse);

            return cachedResponse ?? networkResponse;
        }),
    );
});
