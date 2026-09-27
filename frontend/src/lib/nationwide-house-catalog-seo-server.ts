import { cache } from "react";
import { fetchPublicApiNullable } from "@/lib/server-api";
import type { FaqItem } from "@/lib/json-ld/faq";

export type NationwideHouseCatalogContent = {
  catalogSeoVisible?: boolean;
  catalogSeoText?: string | null;
  faq?: FaqItem[] | null;
};

const fetchNationwideHouseCatalogCached = cache(async () => {
  return fetchPublicApiNullable<NationwideHouseCatalogContent>("/house-catalog", {
    cache: "force-cache",
    next: {
      tags: ["house-catalog-seo"],
    },
  });
});

export async function fetchNationwideHouseCatalogSeo(): Promise<NationwideHouseCatalogContent | null> {
  return fetchNationwideHouseCatalogCached();
}
