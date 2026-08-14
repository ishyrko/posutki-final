import { fetchPublicApi } from "@/lib/server-api";
import type { FaqItem } from "@/lib/json-ld/faq";

export type CityCatalogContent = {
  catalogSeoText?: string | null;
  faq?: FaqItem[] | null;
};

export async function fetchCityCatalogContent(citySlug: string): Promise<CityCatalogContent | null> {
  try {
    return await fetchPublicApi<CityCatalogContent>(`/cities/${encodeURIComponent(citySlug)}`, {
      cache: "force-cache",
      next: {
        tags: ["city-seo", `city-seo-${citySlug}`],
      },
    });
  } catch {
    return null;
  }
}

/** @deprecated Use fetchCityCatalogContent */
export async function fetchCityCatalogSeoText(citySlug: string): Promise<string | null> {
  const city = await fetchCityCatalogContent(citySlug);
  const text = city?.catalogSeoText?.trim();
  return text ? text : null;
}
