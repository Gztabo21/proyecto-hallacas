/* Service worker mínimo: permite instalación PWA; la app sigue yendo a red (PHP). */
const CACHE_VERSION = 'hallacas-jireh-v1';

self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
  event.respondWith(fetch(event.request));
});
