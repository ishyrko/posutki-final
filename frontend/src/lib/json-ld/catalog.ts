import {
  buildCatalogBreadcrumbTrail,
  buildBreadcrumbJsonLd,
  type CatalogBreadcrumbNames,
} from "@/lib/breadcrumbs";
import type { ParsedSegments } from "@/features/catalog/slugs";
import { buildCatalogCanonicalPath } from "@/features/catalog/slugs";

export function buildCatalogBreadcrumbJsonLd(
  parsed: ParsedSegments,
  names: CatalogBreadcrumbNames = {},
  currentPath?: string,
): Record<string, unknown> {
  return buildBreadcrumbJsonLd(
    buildCatalogBreadcrumbTrail(parsed, names),
    currentPath ?? buildCatalogCanonicalPath(parsed),
  );
}
