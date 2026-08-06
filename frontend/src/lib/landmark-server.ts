import { fetchPublicApiNullable } from "@/lib/server-api";
import type { Landmark } from "@/features/landmarks/types";

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
