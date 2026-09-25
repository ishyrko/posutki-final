import { cache } from "react";
import { fetchPublicApiNullable } from "@/lib/server-api";
import type { FaqItem } from "@/lib/json-ld/faq";

export type RegionCatalogContent = {
  id: number;
  name: string;
  slug: string;
  code?: string;
  catalogSeoVisible?: boolean;
  catalogSeoText?: string | null;
  faq?: FaqItem[] | null;
};

const fetchRegionCatalogCached = cache(async (regionSlug: string) => {
  return fetchPublicApiNullable<RegionCatalogContent>(
    `/regions/${encodeURIComponent(regionSlug)}`,
    {
      cache: "force-cache",
      next: {
        tags: ["region-seo", `region-seo-${regionSlug}`],
      },
    },
  );
});

export async function fetchRegionCatalogSeo(
  regionSlug: string,
): Promise<RegionCatalogContent | null> {
  return fetchRegionCatalogCached(regionSlug);
}
