import { fetchPublicApi, fetchPublicApiNullable } from "@/lib/server-api";
import type { Landmark, LandmarkListItem } from "@/features/landmarks/types";

export async function fetchCityLandmarks(citySlug: string): Promise<LandmarkListItem[]> {
  try {
    return await fetchPublicApi<LandmarkListItem[]>(
      `/cities/${encodeURIComponent(citySlug)}/landmarks`,
      {
        next: { revalidate: 3600, tags: [`city-landmarks-${citySlug}`] },
      },
    );
  } catch {
    return [];
  }
}

export async function fetchLandmark(
  citySlug: string,
  slug: string,
): Promise<Landmark | null> {
  return fetchPublicApiNullable<Landmark>(
    `/cities/${encodeURIComponent(citySlug)}/landmarks/${encodeURIComponent(slug)}`,
    {
      next: { revalidate: 3600, tags: [`landmark-${citySlug}-${slug}`] },
    },
  );
}
