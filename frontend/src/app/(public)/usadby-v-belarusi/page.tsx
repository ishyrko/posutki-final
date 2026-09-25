import CatalogPage from "@/features/catalog/CatalogPage";
import { PageBreadcrumbs } from "@/components/PageBreadcrumbs";
import { CatalogSlugProviderFromSets } from "@/components/CatalogSlugProviderFromSets";
import {
  ALL_HOUSES_CATALOG_PATH,
  IMPLICIT_DEAL_TYPE,
  type ParsedSegments,
} from "@/features/catalog/slugs";
import { fetchApartmentCatalogSlugSets } from "@/lib/apartment-catalog-slugs-server";
import { JsonLdScript } from "@/lib/json-ld/json-ld-script";
import { buildBreadcrumbJsonLd, type Crumb } from "@/lib/breadcrumbs";
import { buildPageMetadata } from "@/lib/seo/open-graph";

const PAGE_TITLE = "Усадьбы на сутки в Беларуси";
const PAGE_DESCRIPTION =
  "Снять усадьбу на сутки в Беларуси. Посуточная аренда усадеб без посредников в Беларуси с ценами, описанием и фото на Posutki.by";

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

export default async function AllHousesPage() {
  const slugSets = await fetchApartmentCatalogSlugSets();

  return (
    <CatalogSlugProviderFromSets sets={slugSets}>
      <JsonLdScript data={buildBreadcrumbJsonLd(breadcrumbs, ALL_HOUSES_CATALOG_PATH)} />
      <CatalogPage parsed={parsed} title={PAGE_TITLE} nationwide>
        <PageBreadcrumbs items={breadcrumbs} />
      </CatalogPage>
    </CatalogSlugProviderFromSets>
  );
}
