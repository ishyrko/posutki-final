import { fetchPublicApiPaginated } from "@/lib/server-api";
import { HEADER_REGION_MINSK_SLUG } from "@/lib/region-header";
import type { Property } from "@/features/properties/types";

/** Slugs must match city cards on the home page; filters mirror catalog list API. */
const HOME_CITY_COUNT_FILTERS: ReadonlyArray<{
  slug: string;
  regionSlug?: string;
  citySlug?: string;
}> = [
  { slug: HEADER_REGION_MINSK_SLUG, regionSlug: HEADER_REGION_MINSK_SLUG },
  { slug: "brest", regionSlug: "brest" },
  { slug: "vitebsk", regionSlug: "vitebsk" },
  { slug: "grodno", regionSlug: "grodno" },
  { slug: "gomel", regionSlug: "gomel" },
  { slug: "mogilev", regionSlug: "mogilev" },
  { slug: "baranovichi", citySlug: "baranovichi" },
  { slug: "pinsk", citySlug: "pinsk" },
  { slug: "bobruysk", citySlug: "bobruysk" },
  { slug: "molodechno", citySlug: "molodechno" },
  { slug: "orsha", citySlug: "orsha" },
  { slug: "novopolotsk", citySlug: "novopolotsk" },
  { slug: "svetlogorsk", citySlug: "svetlogorsk" },
  { slug: "smorgon", citySlug: "smorgon" },
];

async function fetchApartmentCount(filter: {
  regionSlug?: string;
  citySlug?: string;
}): Promise<number> {
  const params = new URLSearchParams({
    limit: "1",
    page: "1",
    type: "apartment",
    dealType: "daily",
    sortBy: "createdAt",
    sortOrder: "DESC",
  });

  if (filter.citySlug) {
    params.set("citySlug", filter.citySlug);
  } else if (filter.regionSlug) {
    params.set("regionSlug", filter.regionSlug);
  }

  const { pagination } = await fetchPublicApiPaginated<Property[]>(
    `/properties?${params.toString()}`,
    { next: { revalidate: 300 } },
  );

  return pagination.total;
}

export async function fetchCityApartmentCountsForHome(): Promise<Record<string, number>> {
  try {
    const pairs = await Promise.all(
      HOME_CITY_COUNT_FILTERS.map(async ({ slug, regionSlug, citySlug }) => {
        const total = await fetchApartmentCount({ regionSlug, citySlug });
        return [slug, total] as const;
      }),
    );

    return Object.fromEntries(pairs);
  } catch {
    return {};
  }
}
