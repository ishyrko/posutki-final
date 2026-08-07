import {
  buildCatalogCanonicalPath,
  buildPropertyUrlFromRegionName,
  resolveCatalogCitySlug,
  type ParsedSegments,
} from "@/features/catalog/slugs";
import type { Property, PropertyFilters } from "@/features/properties/types";
import { getSiteOrigin } from "@/lib/site-url";

function toAbsoluteUrl(path: string): string {
  const origin = getSiteOrigin();
  return path.startsWith("http") ? path : `${origin}${path.startsWith("/") ? path : `/${path}`}`;
}

export function buildBaseCatalogFiltersFromParsed(parsed: ParsedSegments): PropertyFilters {
  const filters: PropertyFilters = {
    page: 1,
    limit: 12,
    sortBy: "createdAt",
    sortOrder: "DESC",
    dealType: parsed.dealType ?? "daily",
  };

  if (parsed.cityDistrictSlug || parsed.landmarkSlug) {
    filters.citySlug = resolveCatalogCitySlug(parsed);
  } else if (parsed.citySlug) {
    filters.citySlug = parsed.citySlug;
  } else if (parsed.regionSlug) {
    filters.regionSlug = parsed.regionSlug;
  } else {
    filters.regionSlug = "minsk";
  }

  if (parsed.propertyType) {
    filters.type = parsed.propertyType;
  }

  if (parsed.cityDistrictSlug) {
    filters.cityDistrictSlug = parsed.cityDistrictSlug;
  }

  if (parsed.landmarkSlug) {
    filters.landmarkSlug = parsed.landmarkSlug;
  }

  if (parsed.nearMetro) {
    filters.nearMetro = true;
  }

  return filters;
}

export function buildCatalogFiltersQuery(filters: PropertyFilters): string {
  const params = new URLSearchParams();

  if (filters.page) params.set("page", String(filters.page));
  if (filters.limit) params.set("limit", String(filters.limit));
  if (filters.type) params.set("type", filters.type);
  if (filters.dealType) params.set("dealType", filters.dealType);
  if (filters.regionSlug) params.set("regionSlug", filters.regionSlug);
  if (filters.citySlug) params.set("citySlug", filters.citySlug);
  if (filters.cityDistrictSlug) params.set("cityDistrictSlug", filters.cityDistrictSlug);
  if (filters.landmarkSlug) params.set("landmarkSlug", filters.landmarkSlug);
  if (filters.nearMetro) params.set("nearMetro", "1");
  if (filters.sortBy) params.set("sortBy", filters.sortBy);
  if (filters.sortOrder) params.set("sortOrder", filters.sortOrder);

  return params.toString();
}

export function buildCatalogBreadcrumbJsonLd(
  parsed: ParsedSegments,
  pageTitle: string,
): Record<string, unknown> {
  const origin = getSiteOrigin();
  const catalogPath = buildCatalogCanonicalPath(parsed);

  return {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    itemListElement: [
      {
        "@type": "ListItem",
        position: 1,
        name: "Главная",
        item: `${origin}/`,
      },
      {
        "@type": "ListItem",
        position: 2,
        name: pageTitle,
        item: toAbsoluteUrl(catalogPath),
      },
    ],
  };
}

export function buildCatalogItemListJsonLd(
  properties: Property[],
  pageTitle: string,
  catalogPath: string,
): Record<string, unknown> {
  return {
    "@context": "https://schema.org",
    "@type": "ItemList",
    name: pageTitle,
    url: toAbsoluteUrl(catalogPath),
    numberOfItems: properties.length,
    itemListElement: properties.map((property, index) => {
      const propertyPath = buildPropertyUrlFromRegionName(
        property.type,
        property.id,
        property.address.regionName,
        property.address.citySlug,
      );

      return {
        "@type": "ListItem",
        position: index + 1,
        url: toAbsoluteUrl(propertyPath),
        name: property.title,
      };
    }),
  };
}
