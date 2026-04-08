import { fetchPublicApi } from "@/lib/server-api";
import { RESIDENTIAL_PROPERTY_TYPES } from "@/features/catalog/residential-types";
import type { Property, PropertyListResponse } from "@/features/properties/types";

/**
 * Same filters as FeaturedProperties (client) — keep query params in sync.
 */
export async function fetchFeaturedPropertiesForHome(regionSlug: string): Promise<PropertyListResponse | null> {
  try {
    const params = new URLSearchParams();
    params.set("limit", "4");
    params.set("types", [...RESIDENTIAL_PROPERTY_TYPES].join(","));
    params.set("sortBy", "createdAt");
    params.set("sortOrder", "DESC");
    params.set("regionSlug", regionSlug);
    params.set("dealType", "daily");

    const data = await fetchPublicApi<Property[]>(`/properties?${params.toString()}`, {
      next: { revalidate: 120 },
    });

    return {
      data,
      meta: { total: data.length, page: 1, limit: 4 },
    };
  } catch {
    return null;
  }
}
