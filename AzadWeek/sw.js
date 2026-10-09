const CACHE_NAME = 'azadweek-v17';
const STATIC_ASSETS = [
  './favicon.ico', './assets/favicon.png',
  './assets/odd-week-icon.png', './assets/even-week-icon.png',
  './assets/fonts/Vazirmatn-Regular.ttf', './assets/fonts/Vazirmatn-Bold.ttf'
];
self.addEventListener('install', event => {
  event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS)).catch(() => null));
  self.skipWaiting();
});
self.addEventListener('activate', event => {
  event.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))));
  self.clients.claim();
});
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);
  if (
    event.request.method !== 'GET' ||
    event.request.mode === 'navigate' ||
    url.pathname.endsWith('connect.php') ||
    url.pathname.endsWith('card.php') ||
    url.pathname.endsWith('.html') ||
    url.pathname.endsWith('.js') ||
    url.pathname.endsWith('.ics') ||
    url.pathname.endsWith('/') ||
    url.searchParams.get('action')
  ) {
    return;
  }

  event.respondWith(
    caches.match(event.request).then(cached => cached || fetch(event.request).then(resp => {
      if (resp && resp.ok) {
        const copy = resp.clone();
        caches.open(CACHE_NAME).then(cache => cache.put(event.request, copy)).catch(() => null);
      }
      return resp;
    }).catch(() => cached))
  );
});
