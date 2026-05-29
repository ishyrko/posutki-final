import { fetchPublicApi } from "@/lib/server-api";

export async function fetchRegionHouseCountsForHome(): Promise<Record<string, number>> {
  try {
    return await fetchPublicApi<Record<string, number>>(
      "/properties/home-region-house-counts",
      { next: { revalidate: 300 } },
    );
  } catch {
    return {};
  }
}
