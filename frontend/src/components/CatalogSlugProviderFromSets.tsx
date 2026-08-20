import { ApartmentCatalogSlugProvider } from "@/components/ApartmentCatalogSlugProvider";
import { configureApartmentCatalogSlugs, type ApartmentCatalogSlugSets } from "@/features/catalog/apartment-catalog-slug-store";

/** Server wrapper: configures the RSC module store and passes slug sets to the client provider. */
export function CatalogSlugProviderFromSets({
    sets,
    children,
}: {
    sets: ApartmentCatalogSlugSets;
    children: React.ReactNode;
}) {
    configureApartmentCatalogSlugs(sets);

    return (
        <ApartmentCatalogSlugProvider
            cities={sets.cities}
            prefixSlugs={[...sets.prefixSlugs]}
            catalogSlugs={[...sets.catalogSlugs]}
        >
            {children}
        </ApartmentCatalogSlugProvider>
    );
}
