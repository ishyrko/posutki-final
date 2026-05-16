/** Id фильтров удобств в каталоге (совпадают с {@link CatalogPage} `CATALOG_AMENITY_OPTIONS`). */
export const CATALOG_AMENITY_FILTER_IDS = [
  "wifi",
  "ac",
  "tv",
  "washer",
  "kitchen",
  "dishwasher",
  "jacuzzi",
  "parking",
  "dryer",
] as const;

export type CatalogAmenityFilterId = (typeof CATALOG_AMENITY_FILTER_IDS)[number];

export const AMENITY_QUERY_PARAM = "amenity";

const VALID_IDS = new Set<string>(CATALOG_AMENITY_FILTER_IDS);

/** Один или несколько id через запятую: `?amenity=jacuzzi` или `?amenity=wifi,parking`. */
export function parseAmenitiesFromQuery(raw: string | null): CatalogAmenityFilterId[] {
  if (!raw) return [];
  const ids: CatalogAmenityFilterId[] = [];
  for (const part of raw.split(",")) {
    const id = part.trim();
    if (VALID_IDS.has(id) && !ids.includes(id as CatalogAmenityFilterId)) {
      ids.push(id as CatalogAmenityFilterId);
    }
  }
  return ids;
}
