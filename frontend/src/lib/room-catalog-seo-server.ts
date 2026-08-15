import { cache } from "react";
import { fetchPublicApiNullable } from "@/lib/server-api";
import type { CatalogPlaceDetail } from "@/features/city-places/types";

const fetchRoomCatalogCached = cache(async (citySlug: string, roomsBucket: number) => {
  return fetchPublicApiNullable<CatalogPlaceDetail>(
    `/cities/${encodeURIComponent(citySlug)}/rooms/${roomsBucket}`,
    {
      cache: "force-cache",
      next: {
        tags: ["room-seo", `room-seo-${citySlug}-${roomsBucket}`],
      },
    },
  );
});

export async function fetchRoomCatalogSeo(
  citySlug: string,
  roomsBucket: number,
): Promise<CatalogPlaceDetail | null> {
  return fetchRoomCatalogCached(citySlug, roomsBucket);
}
