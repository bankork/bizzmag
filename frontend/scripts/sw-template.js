/*
 * Service worker template (Stage 3 ADR-08, SEC-013, NFR-PWA-004).
 *
 * scripts/generate-sw.mjs fills in the version and the precache list after
 * `next build` and writes the result to out/sw.js.
 *
 * Rules:
 * - Precache only the static build output (JS, CSS, fonts, icons, pages).
 * - Never cache API responses. Requests to another origin (the API) and to
 *   any same-origin /api/ or /sanctum/ path are left to the network.
 * - Page navigations: network first, cached page shell if offline, then the
 *   offline page.
 */

const VERSION = "__VERSION__";
const CACHE = `salon-ops-${VERSION}`;
const PRECACHE = __PRECACHE__;
const OFFLINE_URL = "/offline.html";

self.addEventListener("install", (event) => {
  event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)));
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) => Promise.all(keys.filter((key) => key.startsWith("salon-ops-") && key !== CACHE).map((key) => caches.delete(key))))
      .then(() => self.clients.claim()),
  );
});

self.addEventListener("message", (event) => {
  if (event.data && event.data.type === "SKIP_WAITING") {
    self.skipWaiting();
  }
});

self.addEventListener("fetch", (event) => {
  const request = event.request;
  const url = new URL(request.url);

  if (request.method !== "GET" || url.origin !== self.location.origin) {
    return;
  }

  if (url.pathname.startsWith("/api/") || url.pathname.startsWith("/sanctum/")) {
    return;
  }

  if (request.mode === "navigate") {
    event.respondWith(
      fetch(request).catch(async () => {
        const cache = await caches.open(CACHE);
        const page = (await cache.match(request, { ignoreSearch: true })) || (await cache.match(url.pathname.replace(/\/?$/, "/")));

        return page || cache.match(OFFLINE_URL);
      }),
    );

    return;
  }

  event.respondWith(caches.match(request).then((cached) => cached || fetch(request)));
});
