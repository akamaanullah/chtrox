const CACHE_NAME = 'chatrox-cache-v1';
const ASSETS_TO_CACHE = [
  './',
  './css/style.css',
  './js/themes-shared.js',
  './assets/images/logo.png',
  './assets/images/default-avatar.svg',
  'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
  'https://unpkg.com/lucide@0.468.0'
];

// Install Service Worker and cache core shell assets
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[Service Worker] Caching app shell');
      // Use map to catch individual failures gracefully if some assets are not available
      return Promise.allSettled(
        ASSETS_TO_CACHE.map(asset => {
          return cache.add(asset).catch(err => {
            console.warn(`[Service Worker] Failed to cache asset: ${asset}`, err);
          });
        })
      );
    }).then(() => self.skipWaiting())
  );
});

// Activate Service Worker and clean up old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cache) => {
          if (cache !== CACHE_NAME) {
            console.log('[Service Worker] Clearing old cache', cache);
            return caches.delete(cache);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch events (Network-first for dynamic content/pages, cache fallback)
self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;

  const requestUrl = new URL(event.request.url);

  // Skip WebSocket connections or API endpoints
  if (event.request.url.startsWith('ws:') || 
      event.request.url.startsWith('wss:') ||
      requestUrl.pathname.includes('/api/') || 
      requestUrl.pathname.includes('/ws-ticket') ||
      requestUrl.pathname.includes('/socket.io')) {
    return;
  }

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // If the request succeeded, cache the latest version (except for non-cacheable items)
        if (response.status === 200 && response.type === 'basic') {
          const responseToCache = response.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, responseToCache);
          });
        }
        return response;
      })
      .catch(() => {
        // Offline: try to return cache item
        return caches.match(event.request).then((cachedResponse) => {
          if (cachedResponse) {
            return cachedResponse;
          }
          // If the request was for a page navigation, return the cached index root
          if (event.request.mode === 'navigate') {
            return caches.match('./');
          }
        });
      })
  );
});
