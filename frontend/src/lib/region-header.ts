/** Region slugs used in the site header (city switcher). URL без префикса области = Минск. */
export const HEADER_CITY_SLUGS = [
  "minsk",
  "brest",
  "vitebsk",
  "gomel",
  "grodno",
  "mogilev",
] as const;

export const HEADER_CITY_SLUG_SET = new Set<string>(HEADER_CITY_SLUGS);

/** sessionStorage key: while set, header prefers this slug on property detail pages. */
export const LISTING_REGION_SESSION_KEY = "listingRegionSlug";

/** Dispatched after listing region is synced so Header re-reads storage (same-tab). */
export const REGION_SYNC_EVENT = "rnb-region-changed";

export const HEADER_REGION_MINSK_SLUG = "minsk";

/**
 * Prefixes catalog URLs with a regional path segment (e.g. `/brest/kvartiry/...`).
 * Non-catalog links and Минск are returned unchanged.
 */
export function withRegionalCatalogHref(href: string, regionSlug: string): string {
  if (regionSlug === HEADER_REGION_MINSK_SLUG) return href;
  if (!href.startsWith("/")) return href;

  const isRegionalCatalogHref =
    href.startsWith("/kvartiry/") ||
    href.startsWith("/doma/") ||
    href.startsWith("/dachi/") ||
    href.startsWith("/vozle-metro/");

  if (!isRegionalCatalogHref) return href;

  const regionPrefix = `/${regionSlug}`;
  if (href.startsWith(`${regionPrefix}/`)) return href;

  return `${regionPrefix}${href}`;
}

/**
 * Maps API `address.regionName` (RU/EN, with or without «область») to a header city slug.
 */
export function regionNameToHeaderSlug(regionName?: string): string | undefined {
  if (!regionName) return undefined;

  const normalized = regionName
    .toLowerCase()
    .replace(/\s*область\s*/g, "")
    .replace(/обл\.?/g, "")
    .replace(/\s+/g, " ")
    .trim();

  const compact = normalized.replace(/\s/g, "");

  const map: Record<string, string> = {
    brest: "brest",
    брест: "brest",
    vitebsk: "vitebsk",
    витебск: "vitebsk",
    gomel: "gomel",
    гомель: "gomel",
    grodno: "grodno",
    гродно: "grodno",
    mogilev: "mogilev",
    могилев: "mogilev",
    могилёв: "mogilev",
    minsk: "minsk",
    минск: "minsk",
    минская: "minsk",
  };

  return map[normalized] ?? map[compact];
}
