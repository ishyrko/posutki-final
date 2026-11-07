import { getConfiguredCityPrefixSlugs } from "@/features/catalog/apartment-catalog-slug-store";
import { buildCatalogUrl, MINSK_CITY_SLUG } from "@/features/catalog/slugs";
import type { ApartmentCatalogCity } from "@/features/home/apartment-catalog-cities";

/**
 * Apartment catalog URL for a city.
 * Minsk has no region prefix, oblast centers use the region segment,
 * other cities use a city prefix or `/kvartiry/{slug}/`.
 * Pass `prefixSlugs` when the module slug store may be unset (site header).
 */
export function buildCityHref(
  city: ApartmentCatalogCity,
  prefixSlugs?: ReadonlySet<string>,
): string {
  if (city.slug === MINSK_CITY_SLUG) {
    return buildCatalogUrl({ propertyType: "apartment" });
  }

  if (city.isMain) {
    return buildCatalogUrl({ region: city.slug, propertyType: "apartment" });
  }

  const prefixes = prefixSlugs ?? getConfiguredCityPrefixSlugs();
  if (prefixes?.has(city.slug)) {
    return `/${city.slug}/kvartiry/`;
  }

  if (!prefixes) {
    return buildCatalogUrl({ city: city.slug, propertyType: "apartment" });
  }

  return `/kvartiry/${city.slug}/`;
}
