import {
  buildCatalogCanonicalPath,
  type ParsedSegments,
} from "@/features/catalog/slugs";
import { getSiteOrigin } from "@/lib/site-url";

function toAbsoluteUrl(path: string): string {
  const origin = getSiteOrigin();
  return path.startsWith("http") ? path : `${origin}${path.startsWith("/") ? path : `/${path}`}`;
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
