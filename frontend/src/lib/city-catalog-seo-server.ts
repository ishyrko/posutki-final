import { fetchPublicApi } from "@/lib/server-api";

export async function fetchCityCatalogSeoText(citySlug: string): Promise<string | null> {
  try {
    const city = await fetchPublicApi<{ catalogSeoText?: string | null }>(
      `/cities/${encodeURIComponent(citySlug)}`,
      {
        cache: "force-cache",
        next: {
          tags: ["city-seo", `city-seo-${citySlug}`],
        },
      },
    );
    const text = city.catalogSeoText?.trim();
    return text ? text : null;
  } catch {
    return null;
  }
}
