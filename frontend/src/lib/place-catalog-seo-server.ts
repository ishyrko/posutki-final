import { cache } from "react";
import { fetchPublicApiNullable } from "@/lib/server-api";
import type { CatalogPlaceDetail } from "@/features/city-places/types";

const fetchDistrictCatalogCached = cache(async (citySlug: string, slug: string) => {
  return fetchPublicApiNullable<CatalogPlaceDetail>(
    `/cities/${encodeURIComponent(citySlug)}/districts/${encodeURIComponent(slug)}`,
    {
      cache: "force-cache",
      next: {
        tags: ["place-seo", `district-seo-${citySlug}-${slug}`],
      },
    },
  );
});

const fetchMicrodistrictCatalogCached = cache(async (citySlug: string, slug: string) => {
  return fetchPublicApiNullable<CatalogPlaceDetail>(
    `/cities/${encodeURIComponent(citySlug)}/microdistricts/${encodeURIComponent(slug)}`,
    {
      cache: "force-cache",
      next: {
        tags: ["place-seo", `microdistrict-seo-${citySlug}-${slug}`],
      },
    },
  );
});

const fetchResidentialComplexCatalogCached = cache(async (citySlug: string, slug: string) => {
  return fetchPublicApiNullable<CatalogPlaceDetail>(
    `/cities/${encodeURIComponent(citySlug)}/residential-complexes/${encodeURIComponent(slug)}`,
    {
      cache: "force-cache",
      next: {
        tags: ["place-seo", `residential-complex-seo-${citySlug}-${slug}`],
      },
    },
  );
});

export async function fetchDistrictCatalogSeo(
  citySlug: string,
  slug: string,
): Promise<CatalogPlaceDetail | null> {
  return fetchDistrictCatalogCached(citySlug, slug);
}

export async function fetchMicrodistrictCatalogSeo(
  citySlug: string,
  slug: string,
): Promise<CatalogPlaceDetail | null> {
  return fetchMicrodistrictCatalogCached(citySlug, slug);
}

export async function fetchResidentialComplexCatalogSeo(
  citySlug: string,
  slug: string,
): Promise<CatalogPlaceDetail | null> {
  return fetchResidentialComplexCatalogCached(citySlug, slug);
}
