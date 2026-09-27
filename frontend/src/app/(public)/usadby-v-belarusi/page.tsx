import CatalogPageWithSuspense from "@/features/catalog/CatalogPageWithSuspense";
import { PageBreadcrumbs } from "@/components/PageBreadcrumbs";
import { CatalogSlugProviderFromSets } from "@/components/CatalogSlugProviderFromSets";
import {
  ALL_HOUSES_CATALOG_PATH,
  IMPLICIT_DEAL_TYPE,
  type ParsedSegments,
} from "@/features/catalog/slugs";
import { fetchApartmentCatalogSlugSets } from "@/lib/apartment-catalog-slugs-server";
import { fetchNationwideHouseCatalogSeo } from "@/lib/nationwide-house-catalog-seo-server";
import { JsonLdScript } from "@/lib/json-ld/json-ld-script";
import { buildBreadcrumbJsonLd, type Crumb } from "@/lib/breadcrumbs";
import { buildFaqPageJsonLd, type FaqItem } from "@/lib/json-ld/faq";
import { buildPageMetadata } from "@/lib/seo/open-graph";
import { sanitizeArticleHtml } from "@/features/articles/sanitizeArticleHtml";

const PAGE_TITLE = "Усадьбы на сутки в Беларуси";
const PAGE_DESCRIPTION =
  "Снять усадьбу на сутки в Беларуси. Посуточная аренда усадеб без посредников в Беларуси с ценами, описанием и фото на Posutki.by";

const SEO_HEADING = "Аренда усадеб в Беларуси";
const FAQ_HEADING = "Вопросы об аренде усадеб в Беларуси";

const parsed: ParsedSegments = {
  dealType: IMPLICIT_DEAL_TYPE,
  propertyType: "house",
};

const breadcrumbs: Crumb[] = [
  { label: "Главная", href: "/" },
  { label: PAGE_TITLE },
];

export function generateMetadata() {
  return buildPageMetadata({
    title: PAGE_TITLE,
    description: PAGE_DESCRIPTION,
    path: ALL_HOUSES_CATALOG_PATH,
  });
}

export default async function AllHousesPage({
  searchParams,
}: {
  searchParams: Promise<{ page?: string }>;
}) {
  const resolvedSearchParams = await searchParams;
  const pageFromQuery = Number(resolvedSearchParams.page ?? "1");
  const isFirstPage = !Number.isFinite(pageFromQuery) || pageFromQuery <= 1;
  const slugSets = await fetchApartmentCatalogSlugSets();

  let citySeoFooter: { heading: string; html: string; faq?: FaqItem[]; faqTitle?: string } | null =
    null;
  let faqJsonLd: Record<string, unknown> | null = null;

  if (isFirstPage) {
    const content = await fetchNationwideHouseCatalogSeo();
    if (content?.catalogSeoVisible) {
      const rawSeoText = content.catalogSeoText?.trim() ?? null;
      const faqItems = (content.faq ?? []).filter(
        (item): item is FaqItem =>
          Boolean(item?.question?.trim()) && Boolean(item?.answer?.trim()),
      );
      const sanitizedHtml = rawSeoText ? sanitizeArticleHtml(rawSeoText) : null;

      if (sanitizedHtml || faqItems.length > 0) {
        citySeoFooter = {
          heading: SEO_HEADING,
          html: sanitizedHtml ?? "",
          faq: faqItems.length > 0 ? faqItems : undefined,
          faqTitle: faqItems.length > 0 ? FAQ_HEADING : undefined,
        };
      }

      if (faqItems.length > 0) {
        faqJsonLd = buildFaqPageJsonLd(faqItems);
      }
    }
  }

  return (
    <CatalogSlugProviderFromSets sets={slugSets}>
      <JsonLdScript data={buildBreadcrumbJsonLd(breadcrumbs, ALL_HOUSES_CATALOG_PATH)} />
      {faqJsonLd ? <JsonLdScript data={faqJsonLd} /> : null}
      <CatalogPageWithSuspense
        parsed={parsed}
        title={PAGE_TITLE}
        nationwide
        citySeoFooter={citySeoFooter}
      >
        <PageBreadcrumbs items={breadcrumbs} />
      </CatalogPageWithSuspense>
    </CatalogSlugProviderFromSets>
  );
}
