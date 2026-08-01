/**
 * Clear Cache API entries and unregister service workers (if any).
 * Helps mobile browsers that keep stale HTML/JS after a soft reload.
 */
export async function clearClientCaches(): Promise<void> {
  if (typeof window === "undefined") {
    return;
  }

  try {
    if ("caches" in window) {
      const keys = await caches.keys();
      await Promise.all(keys.map((key) => caches.delete(key)));
    }
  } catch {
    /* offline or unsupported */
  }

  try {
    if ("serviceWorker" in navigator) {
      const registrations = await navigator.serviceWorker.getRegistrations();
      await Promise.all(registrations.map((registration) => registration.unregister()));
    }
  } catch {
    /* unsupported */
  }
}

/**
 * Navigate away with a cache-busting query param after clearing client caches.
 * `location.reload()` alone is unreliable on iOS Chrome after deploys.
 */
export async function hardReloadAfterDeploy(buildId?: string): Promise<void> {
  await clearClientCaches();

  const url = new URL(window.location.href);
  if (buildId) {
    url.searchParams.set("_v", buildId);
  } else {
    url.searchParams.set("_cb", String(Date.now()));
  }
  window.location.replace(url.toString());
}
