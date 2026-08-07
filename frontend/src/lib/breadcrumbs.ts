import {
  buildCatalogUrl,
  buildCatalogUrlFromAddress,
  buildPageTitle,
  CITY_PREFIX_SLUGS,
  IMPLICIT_DEAL_TYPE,
  propertyUrlRegionSlug,
  REGION_SLUGS,
  type ParsedSegments,
} from "@/features/catalog/slugs";
import type { Article } from "@/features/articles/types";
import type { Property } from "@/features/properties/types";
import { getSiteOrigin } from "@/lib/site-url";

export type Crumb = { label: string; href?: string };

export type CatalogBreadcrumbNames = {
  metroStationName?: string;
  cityDistrictName?: string;
  landmarkPhrase?: string;
  landmarkName?: string;
};

const HOME_CRUMB: Crumb = { label: "Главная", href: "/" };

function toAbsoluteUrl(path: string): string {
  const origin = getSiteOrigin();
  return path.startsWith("http") ? path : `${origin}${path.startsWith("/") ? path : `/${path}`}`;
}

function stripCatalogFacets(parsed: ParsedSegments): ParsedSegments {
  return {
    ...parsed,
    nearMetro: undefined,
    metroStationSlug: undefined,
    cityDistrictSlug: undefined,
    landmarkSlug: undefined,
  };
}

function hasCatalogFacets(parsed: ParsedSegments): boolean {
  return Boolean(
    parsed.nearMetro ||
      parsed.metroStationSlug ||
      parsed.cityDistrictSlug ||
      parsed.landmarkSlug,
  );
}

function resolveBaseCatalogUrlParams(parsed: ParsedSegments) {
  const key = parsed.citySlug ?? parsed.regionSlug;
  if (key && CITY_PREFIX_SLUGS.has(key)) {
    return { city: key, propertyType: parsed.propertyType };
  }
  if (parsed.regionSlug && REGION_SLUGS.has(parsed.regionSlug)) {
    return { region: parsed.regionSlug, propertyType: parsed.propertyType };
  }
  return { propertyType: parsed.propertyType };
}

function buildBaseCatalogCrumb(parsed: ParsedSegments): Crumb {
  const baseParsed = stripCatalogFacets(parsed);
  const label = buildPageTitle(baseParsed);
  const href = buildCatalogUrl(resolveBaseCatalogUrlParams(parsed));
  return { label, href };
}

export function buildCatalogBreadcrumbTrail(
  parsed: ParsedSegments,
  names: CatalogBreadcrumbNames = {},
): Crumb[] {
  const crumbs: Crumb[] = [HOME_CRUMB];
  const baseCrumb = buildBaseCatalogCrumb(parsed);

  if (!hasCatalogFacets(parsed)) {
    crumbs.push({ label: baseCrumb.label });
    return crumbs;
  }

  crumbs.push(baseCrumb);

  if (parsed.landmarkSlug) {
    crumbs.push({
      label: names.landmarkName ?? names.landmarkPhrase ?? parsed.landmarkSlug,
    });
    return crumbs;
  }

  if (parsed.cityDistrictSlug) {
    crumbs.push({ label: names.cityDistrictName ?? parsed.cityDistrictSlug });
    return crumbs;
  }

  if (parsed.metroStationSlug) {
    crumbs.push({
      label: "Возле метро",
      href: buildCatalogUrl({
        propertyType: parsed.propertyType,
        nearMetro: true,
      }),
    });
    crumbs.push({ label: names.metroStationName ?? parsed.metroStationSlug });
    return crumbs;
  }

  if (parsed.nearMetro) {
    crumbs.push({ label: "Возле метро" });
  }

  return crumbs;
}

function buildParsedFromProperty(property: Property): ParsedSegments {
  const citySlug = property.address.citySlug;
  const regionSlug = propertyUrlRegionSlug(
    property.address.regionName,
    citySlug,
    property.type,
  );

  return {
    dealType: IMPLICIT_DEAL_TYPE,
    propertyType: property.type,
    citySlug: citySlug && CITY_PREFIX_SLUGS.has(citySlug) ? citySlug : undefined,
    regionSlug: regionSlug && REGION_SLUGS.has(regionSlug) ? regionSlug : undefined,
  };
}

export function buildPropertyBreadcrumbTrail(property: Property): Crumb[] {
  const parsed = buildParsedFromProperty(property);
  const baseCrumb = buildBaseCatalogCrumb(parsed);

  return [
    HOME_CRUMB,
    {
      label: baseCrumb.label,
      href: buildCatalogUrlFromAddress(
        property.address.regionName,
        property.address.citySlug,
        property.type,
      ),
    },
    { label: property.title },
  ];
}

export function buildArticlesIndexBreadcrumbTrail(): Crumb[] {
  return [HOME_CRUMB, { label: "Статьи" }];
}

export function buildArticleCategoryBreadcrumbTrail(category: {
  name: string;
  slug: string;
}): Crumb[] {
  return [
    HOME_CRUMB,
    { label: "Статьи", href: "/stati/" },
    { label: category.name },
  ];
}

export function buildArticleBreadcrumbTrail(article: Pick<
  Article,
  "title" | "categoryName" | "categorySlug"
>): Crumb[] {
  const crumbs: Crumb[] = [HOME_CRUMB, { label: "Статьи", href: "/stati/" }];

  if (article.categorySlug && article.categoryName) {
    crumbs.push({
      label: article.categoryName,
      href: `/stati/${article.categorySlug}/`,
    });
  }

  crumbs.push({ label: article.title });
  return crumbs;
}

export function buildBreadcrumbJsonLd(
  crumbs: Crumb[],
  currentPath?: string,
): Record<string, unknown> {
  return {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    itemListElement: crumbs.map((crumb, index) => {
      const isLast = index === crumbs.length - 1;
      const href = crumb.href ?? (isLast ? currentPath : undefined);
      const item: Record<string, unknown> = {
        "@type": "ListItem",
        position: index + 1,
        name: crumb.label,
      };

      if (href) {
        item.item = toAbsoluteUrl(href);
      }

      return item;
    }),
  };
}
