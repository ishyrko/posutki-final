import { ApartmentCatalogSlugProvider } from "@/components/ApartmentCatalogSlugProvider";
import { configureApartmentCatalogSlugs } from "@/features/catalog/apartment-catalog-slug-store";
import { fetchApartmentCatalogSlugSets } from "@/lib/apartment-catalog-slugs-server";

/** Server wrapper: loads catalog city slugs and configures both server module store and client provider. */
export async function ApartmentCatalogSlugGate({
    children,
}: {
    children: React.ReactNode;
}) {
    const slugSets = await fetchApartmentCatalogSlugSets();
    configureApartmentCatalogSlugs(slugSets);

    return (
        <ApartmentCatalogSlugProvider
            cities={slugSets.cities}
            prefixSlugs={[...slugSets.prefixSlugs]}
            catalogSlugs={[...slugSets.catalogSlugs]}
        >
            {children}
        </ApartmentCatalogSlugProvider>
    );
}
