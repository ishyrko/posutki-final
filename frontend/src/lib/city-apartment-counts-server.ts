import { fetchPublicApi } from "@/lib/server-api";

export async function fetchCityApartmentCountsForHome(): Promise<Record<string, number>> {
  try {
    return await fetchPublicApi<Record<string, number>>(
      "/properties/home-city-apartment-counts",
      { next: { revalidate: 300 } },
    );
  } catch {
    return {};
  }
}
