import { Suspense, type ComponentProps } from "react";
import CatalogPage from "@/features/catalog/CatalogPage";
import { CatalogPageFallback } from "@/features/catalog/CatalogPageFallback";

type CatalogPageProps = ComponentProps<typeof CatalogPage>;

export default function CatalogPageWithSuspense(props: CatalogPageProps) {
  return (
    <Suspense fallback={<CatalogPageFallback />}>
      <CatalogPage {...props} />
    </Suspense>
  );
}
