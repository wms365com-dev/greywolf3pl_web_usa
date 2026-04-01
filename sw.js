/* Grey Wolf 3PL - simple offline cache for faster repeat visits */
const CACHE_NAME = "gw-site-v20260320171500";
const CORE_ASSETS = [
  "./assets/retailers/best-buy.png",
  "./assets/retailers/canadian-tire.png",
  "./assets/retailers/costco.png",
  "./assets/retailers/homedepot.png",
  "./assets/retailers/rona.png",
  "./assets/retailers/sail.png",
  "./assets/retailers/staple.png",
  "./assets/retailers/walmart.png",
  "./canada-flag.png",
  "./driver-help.html",
  "./favicon.png",
  "./hero_new.jpg",
  "./international.html",
  "./assets/warehouse/hero.JPG",
  "./index.html",
  "./logo_wolf_invert.png",
  "./logo_wolf_large_new.png",
  "./privacy.html",
  "./cookie-policy.html",
  "./sitemap.html",
  "./style.css",
  "./tracking.html",
  "./usa-flag.png"
];

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(CORE_ASSETS)).then(() => self.skipWaiting())
  );
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.map((k) => (k !== CACHE_NAME ? caches.delete(k) : null)))
    ).then(() => self.clients.claim())
  );
});

// Stale-while-revalidate for GET requests
self.addEventListener("fetch", (event) => {
  const req = event.request;
  if (req.method !== "GET") return;

  event.respondWith(
    caches.match(req).then((cached) => {
      const fetchPromise = fetch(req).then((netRes) => {
        // Only cache successful basic/cors responses
        try {
          if (netRes && (netRes.status === 200 || netRes.type === "opaque")) {
            const copy = netRes.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
          }
        } catch(e) {}
        return netRes;
      }).catch(() => cached);

      return cached || fetchPromise;
    })
  );
});
