import {
  buildCatalogUrl,
  buildRoomCatalogUrl,
  isBaseCityApartmentCatalogPage,
  isDeprecatedFourPlusRoomCatalogPage,
  isRoomCatalogPage,
  isRoomSeoBucket,
  resolveCatalogCitySlug,
  resolveCatalogUrlParamsFromCitySlug,
  type ParsedSegments,
  type RoomBucket,
} from "@/features/catalog/slugs";

export type RoomFilterBucket = "1" | "2" | "3" | "4";

function sortRoomBuckets(a: RoomFilterBucket, b: RoomFilterBucket): number {
  return Number(a) - Number(b);
}

/** Парсинг `?rooms=` — 1|2|3|4|4+|3+ (3+ = 3 и 4). */
export function parseRoomsFromQuery(raw: string | null): RoomFilterBucket[] {
  if (!raw) return [];
  const set = new Set<RoomFilterBucket>();
  for (const part of raw.split(",")) {
    const p = part.trim();
    if (p === "3+") {
      set.add("3");
      set.add("4");
      continue;
    }
    if (p === "1" || p === "2" || p === "3") set.add(p);
    if (p === "4" || p === "4+") set.add("4");
  }
  return [...set].sort(sortRoomBuckets);
}

export function roomFilterBucketToApiValue(bucket: RoomFilterBucket): number {
  return bucket === "4" ? 4 : Number(bucket);
}

export function roomFilterBucketToSeoBucket(bucket: RoomFilterBucket): RoomBucket | null {
  if (bucket === "4") return null;
  return Number(bucket) as RoomBucket;
}

/** 301 с устаревших path `/4-komnatnye/` на каталог с `?rooms=4`. */
export function buildFourPlusRoomCatalogRedirectPath(
  parsed: ParsedSegments,
  preservedParams: URLSearchParams,
): string {
  const citySlug = resolveCatalogCitySlug(parsed);
  const path = buildCatalogUrl({
    propertyType: "apartment",
    ...resolveCatalogUrlParamsFromCitySlug(citySlug),
  });
  preservedParams.set("rooms", "4");
  preservedParams.delete("page");
  const query = preservedParams.toString();
  return query ? `${path}?${query}` : `${path}?rooms=4`;
}

export { isDeprecatedFourPlusRoomCatalogPage };

export function canUseRoomPathNavigation(parsed: ParsedSegments): boolean {
  return isBaseCityApartmentCatalogPage(parsed) || isRoomCatalogPage(parsed);
}

/**
 * 301 с базового каталога квартир при одиночном `?rooms=` на path-лендинг.
 * Мульти-выбор и страницы с другими фасетами не редиректятся.
 */
export function buildRoomLandingRedirectPath(
  parsed: ParsedSegments,
  roomsQuery: string | null,
  preservedParams: URLSearchParams,
): string | null {
  if (!isBaseCityApartmentCatalogPage(parsed) || !roomsQuery) {
    return null;
  }

  const buckets = parseRoomsFromQuery(roomsQuery);
  if (buckets.length !== 1) {
    return null;
  }

  const seoBucket = roomFilterBucketToSeoBucket(buckets[0]!);
  if (seoBucket == null) {
    return null;
  }

  const citySlug = resolveCatalogCitySlug(parsed);
  const path = buildRoomCatalogUrl(citySlug, seoBucket);

  preservedParams.delete("rooms");
  preservedParams.delete("page");
  const query = preservedParams.toString();

  return query ? `${path}?${query}` : path;
}

export function serializeRoomsToQuery(buckets: RoomFilterBucket[]): string | null {
  if (buckets.length === 0) return null;
  return buckets.join(",");
}
