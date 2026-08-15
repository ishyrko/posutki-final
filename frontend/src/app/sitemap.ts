import type { MetadataRoute } from "next";
import { fetchPublicApi } from "@/lib/server-api";
import { getSiteOrigin } from "@/lib/site-url";
import {
  CITY_PREFIX_SLUG_LIST,
  CITIES_WITH_DISTRICTS_SLUGS,
  CATALOG_APARTMENT_CITY_SLUGS,
  PROPERTY_TYPE_SLUG_TO_VALUE,
  MINSK_CITY_SLUG,
  REGION_SLUGS,
  ROOM_BUCKET_VALUES,
  buildCatalogUrl,
  buildPropertyUrlFromRegionName,
  buildRoomCatalogUrl,
  type RoomBucket,
} from "@/features/catalog/slugs";
import type { CityDistrict } from "@/features/city-districts/types";
import type { CityPlace } from "@/features/city-places/types";
import type { LandmarkListItem } from "@/features/landmarks/types";
import type { Article, ArticleCategory } from "@/features/articles/types";
import type { Property } from "@/features/properties/types";

// Regenerate once a day; ISR avoids pressuring the backend on every crawl.
export const revalidate = 86400;

const SITE_URL = getSiteOrigin();

// Cap property URLs to stay well under Google's 50k per-sitemap limit.
const MAX_PROPERTIES = 10_000;
const PROPERTY_PAGE_SIZE = 100;

type Entry = MetadataRoute.Sitemap[number];

function toAbsolute(path: string): string {
  return `${SITE_URL}${path.startsWith("/") ? path : `/${path}`}`;
}

function parseDate(value: string | null | undefined): Date | undefined {
  if (!value) return undefined;
  const d = new Date(value);
  return Number.isNaN(d.getTime()) ? undefined : d;
}

function staticEntries(now: Date): Entry[] {
  const pages: Array<Pick<Entry, "url" | "changeFrequency" | "priority">> = [
    { url: "/", changeFrequency: "daily", priority: 1.0 },
    { url: "/stati/", changeFrequency: "daily", priority: 0.8 },
    { url: "/o-nas/", changeFrequency: "yearly", priority: 0.4 },
    { url: "/kontakty/", changeFrequency: "yearly", priority: 0.5 },
    { url: "/sdat-kvartiru-na-sutki/", changeFrequency: "monthly", priority: 0.7 },
    { url: "/razmestit/", changeFrequency: "yearly", priority: 0.4 },
    { url: "/oplata/", changeFrequency: "yearly", priority: 0.4 },
    {
      url: "/integratsiya-s-realty-calendar/",
      changeFrequency: "yearly",
      priority: 0.4,
    },
    {
      url: "/politika-konfidentsialnosti/",
      changeFrequency: "yearly",
      priority: 0.2,
    },
    {
      url: "/publichnaya-oferta/",
      changeFrequency: "yearly",
      priority: 0.2,
    },
    {
      url: "/soglasie-na-obrabotku-personalnyh-dannyh/",
      changeFrequency: "yearly",
      priority: 0.2,
    },
    {
      url: "/usloviya-ispolzovaniya/",
      changeFrequency: "yearly",
      priority: 0.2,
    },
  ];

  return pages.map((p) => ({
    url: toAbsolute(p.url),
    lastModified: now,
    changeFrequency: p.changeFrequency,
    priority: p.priority,
  }));
}

/** Landing URLs: property type × optional region. */
function catalogEntries(now: Date): Entry[] {
  const propertyTypes = Object.values(PROPERTY_TYPE_SLUG_TO_VALUE);
  const regions: Array<string | undefined> = [undefined, ...REGION_SLUGS];

  const urls = new Set<string>();

  for (const region of regions) {
    for (const propertyType of propertyTypes) {
      urls.add(buildCatalogUrl({ region, propertyType }));
    }
  }

  for (const city of CITY_PREFIX_SLUG_LIST) {
    urls.add(buildCatalogUrl({ city, propertyType: "apartment" }));
  }

  urls.add(buildCatalogUrl({ propertyType: "apartment", nearMetro: true }));

  urls.delete("/");

  return Array.from(urls).map((path) => ({
    url: toAbsolute(path),
    lastModified: now,
    changeFrequency: "daily" as const,
    priority: 0.7,
  }));
}

/** Room-count landing pages: city × 1–4 rooms (apartments only). */
function roomCatalogEntries(now: Date): Entry[] {
  const urls = CATALOG_APARTMENT_CITY_SLUGS.flatMap((citySlug) =>
    ROOM_BUCKET_VALUES.map((bucket) => buildRoomCatalogUrl(citySlug, bucket as RoomBucket)),
  );

  return urls.map((path) => ({
    url: toAbsolute(path),
    lastModified: now,
    changeFrequency: "daily" as const,
    priority: 0.65,
  }));
}

async function districtEntries(now: Date): Promise<Entry[]> {
  const entries: Entry[] = [];

  for (const citySlug of CITIES_WITH_DISTRICTS_SLUGS) {
    try {
      const districts = await fetchPublicApi<CityDistrict[]>(
        `/cities/${encodeURIComponent(citySlug)}/districts`,
        { next: { revalidate: 3600, tags: [`city-districts-${citySlug}`] } },
      );

      const isCityPrefix = CITY_PREFIX_SLUG_LIST.includes(
        citySlug as (typeof CITY_PREFIX_SLUG_LIST)[number],
      );
      const isRegion = REGION_SLUGS.has(citySlug);

      for (const district of districts) {
        if (!district.slug) continue;

        const path = buildCatalogUrl({
          region: !isCityPrefix && isRegion ? citySlug : undefined,
          city: isCityPrefix ? citySlug : undefined,
          propertyType: "apartment",
          cityDistrict: district.slug,
        });

        entries.push({
          url: toAbsolute(path),
          lastModified: now,
          changeFrequency: "weekly",
          priority: 0.6,
        });
      }
    } catch {
      // Soft-fail per city.
    }
  }

  return entries;
}

async function landmarkEntries(now: Date): Promise<Entry[]> {
  const entries: Entry[] = [];
  const citySlugs = new Set<string>([MINSK_CITY_SLUG, ...REGION_SLUGS, ...CITY_PREFIX_SLUG_LIST]);

  for (const citySlug of citySlugs) {
    try {
      const landmarks = await fetchPublicApi<LandmarkListItem[]>(
        `/cities/${encodeURIComponent(citySlug)}/landmarks`,
        { next: { revalidate: 3600, tags: [`city-landmarks-${citySlug}`] } },
      );

      const isCityPrefix = CITY_PREFIX_SLUG_LIST.includes(
        citySlug as (typeof CITY_PREFIX_SLUG_LIST)[number],
      );
      const isRegion = REGION_SLUGS.has(citySlug);

      for (const landmark of landmarks) {
        if (!landmark.slug) continue;

        const path = buildCatalogUrl({
          region: !isCityPrefix && isRegion ? citySlug : undefined,
          city: isCityPrefix ? citySlug : undefined,
          propertyType: "apartment",
          landmark: landmark.slug,
        });

        entries.push({
          url: toAbsolute(path),
          lastModified: now,
          changeFrequency: "weekly",
          priority: 0.6,
        });
      }
    } catch {
      // Soft-fail per city.
    }
  }

  return entries;
}

async function placeEntries(now: Date): Promise<Entry[]> {
  const entries: Entry[] = [];
  const citySlugs = new Set<string>([MINSK_CITY_SLUG, ...REGION_SLUGS, ...CITY_PREFIX_SLUG_LIST]);

  for (const citySlug of citySlugs) {
    try {
      const places = await fetchPublicApi<CityPlace[]>(
        `/cities/${encodeURIComponent(citySlug)}/places`,
        { next: { revalidate: 3600, tags: [`city-places-${citySlug}`] } },
      );

      const isCityPrefix = CITY_PREFIX_SLUG_LIST.includes(
        citySlug as (typeof CITY_PREFIX_SLUG_LIST)[number],
      );
      const isRegion = REGION_SLUGS.has(citySlug);

      for (const place of places) {
        if (!place.slug) continue;

        const path = buildCatalogUrl({
          region: !isCityPrefix && isRegion ? citySlug : undefined,
          city: isCityPrefix ? citySlug : undefined,
          propertyType: "apartment",
          microdistrict: place.type === "microdistrict" ? place.slug : undefined,
          residentialComplex: place.type === "residential_complex" ? place.slug : undefined,
        });

        entries.push({
          url: toAbsolute(path),
          lastModified: now,
          changeFrequency: "weekly",
          priority: 0.6,
        });
      }
    } catch {
      // Soft-fail per city.
    }
  }

  return entries;
}

async function articleEntries(now: Date): Promise<Entry[]> {
  try {
    const [categories, articles] = await Promise.all([
      fetchPublicApi<ArticleCategory[]>("/article-categories", {
        next: { revalidate: 3600, tags: ["articles"] },
      }),
      fetchPublicApi<Article[]>("/articles?limit=1000", {
        next: { revalidate: 3600, tags: ["articles"] },
      }),
    ]);

    const categoryEntries: Entry[] = categories.map((c) => ({
      url: toAbsolute(`/stati/${c.slug}/`),
      lastModified: now,
      changeFrequency: "weekly",
      priority: 0.6,
    }));

    const articleEntriesList: Entry[] = articles
      .filter((a) => a.categorySlug && a.slug)
      .map((a) => ({
        url: toAbsolute(`/stati/${a.categorySlug}/${a.slug}/`),
        lastModified:
          parseDate(a.updatedAt) ??
          parseDate(a.publishedAt) ??
          parseDate(a.createdAt) ??
          now,
        changeFrequency: "monthly",
        priority: 0.7,
      }));

    return [...categoryEntries, ...articleEntriesList];
  } catch {
    return [];
  }
}

async function propertyEntries(now: Date): Promise<Entry[]> {
  const results: Entry[] = [];

  try {
    for (let page = 1; results.length < MAX_PROPERTIES; page += 1) {
      const batch = await fetchPublicApi<Property[]>(
        `/properties?status=published&page=${page}&limit=${PROPERTY_PAGE_SIZE}&sortBy=createdAt&sortOrder=DESC`,
        { next: { revalidate: 3600, tags: ["properties-sitemap"] } },
      );

      if (!batch.length) break;

      for (const property of batch) {
        if (property.status && property.status !== "published") continue;

        const url = buildPropertyUrlFromRegionName(
          property.type,
          property.id,
          property.address.regionName,
          property.address.citySlug,
        );

        results.push({
          url: toAbsolute(url),
          lastModified: parseDate(property.createdAt) ?? now,
          changeFrequency: "weekly",
          priority: 0.8,
        });

        if (results.length >= MAX_PROPERTIES) break;
      }

      if (batch.length < PROPERTY_PAGE_SIZE) break;
    }
  } catch {
    // Soft-fail: keep static/catalog/article URLs even if the API is unreachable.
  }

  return results;
}

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const now = new Date();

  const [articles, properties, districts, landmarks, places] = await Promise.all([
    articleEntries(now),
    propertyEntries(now),
    districtEntries(now),
    landmarkEntries(now),
    placeEntries(now),
  ]);

  return [
    ...staticEntries(now),
    ...catalogEntries(now),
    ...roomCatalogEntries(now),
    ...districts,
    ...landmarks,
    ...places,
    ...articles,
    ...properties,
  ];
}
