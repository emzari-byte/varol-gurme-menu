const WAITER_CACHE = 'varol-waiter-panel-v1';

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(WAITER_CACHE).then(cache => cache.addAll([
      '/menu/css/waiter-panel.css',
      '/menu/sound/notify.wav',
      '/menu/image/catalog/veranda-logo2.png'
    ]))
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => Promise.all(
      keys.filter(key => key !== WAITER_CACHE).map(key => caches.delete(key))
    ))
  );
  self.clients.claim();
});

self.addEventListener('fetch', event => {
  const request = event.request;
  const url = new URL(request.url);

  if (request.method !== 'GET' || url.origin !== self.location.origin) {
    return;
  }

  if (url.pathname.includes('/admin/index.php')) {
    event.respondWith(fetch(request).catch(() => caches.match(request)));
    return;
  }

  event.respondWith(
    caches.match(request).then(cached => {
      return cached || fetch(request).then(response => {
        if (response && response.status === 200) {
          const copy = response.clone();
          caches.open(WAITER_CACHE).then(cache => cache.put(request, copy));
        }

        return response;
      });
    })
  );
});
