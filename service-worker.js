const KIRPI_PWA_CACHE = 'kirpi-core-static-v2';

const KIRPI_STATIC_ASSETS = [
  '/assets/css/tabler.min.css',
  '/assets/css/tabler-icons.min.css',
  '/assets/css/app.css',
  '/assets/css/toastr.min.css',
  '/assets/js/jquery-3.7.1.min.js',
  '/assets/js/bootstrap.bundle.min.js',
  '/assets/js/tabler.min.js',
  '/assets/js/toastr.min.js',
  '/assets/js/app.js',
  '/assets/js/report-table.js',
  '/assets/js/pwa.js',
  '/assets/img/logo.svg'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(KIRPI_PWA_CACHE)
      .then((cache) => cache.addAll(KIRPI_STATIC_ASSETS))
      .then(() => self.skipWaiting())
      .catch(() => undefined)
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys
        .filter((key) => key.startsWith('kirpi-core-') && key !== KIRPI_PWA_CACHE)
        .map((key) => caches.delete(key))
      ))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;

  if (request.method !== 'GET') {
    return;
  }

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) {
    return;
  }

  if (!url.pathname.startsWith('/assets/')) {
    return;
  }

  event.respondWith(
    caches.match(request).then((cached) => {
      if (cached) {
        return cached;
      }

      return fetch(request).then((response) => {
        if (!response || response.status !== 200 || response.type !== 'basic') {
          return response;
        }

        const responseClone = response.clone();
        caches.open(KIRPI_PWA_CACHE).then((cache) => cache.put(request, responseClone));
        return response;
      });
    })
  );
});
