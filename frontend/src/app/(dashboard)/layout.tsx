import { ApartmentCatalogSlugProvider } from '@/components/ApartmentCatalogSlugProvider';
import { OwnerFeaturesProvider } from '@/features/properties/OwnerFeaturesProvider';
import { configureApartmentCatalogSlugs } from '@/features/catalog/apartment-catalog-slug-store';
import { fetchApartmentCatalogSlugSets } from '@/lib/apartment-catalog-slugs-server';
import { fetchHasMyProperties } from '@/lib/my-properties-server';
import DashboardLayoutClient from './DashboardLayoutClient';

/** Кабинет всегда динамический — данные пользователя и объявлений с сервера. */
export const dynamic = 'force-dynamic';

export default async function DashboardLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const [initialHasMyProperties, slugSets] = await Promise.all([
        fetchHasMyProperties(),
        fetchApartmentCatalogSlugSets(),
    ]);

    configureApartmentCatalogSlugs(slugSets);

    return (
        <ApartmentCatalogSlugProvider
            cities={slugSets.cities}
            prefixSlugs={[...slugSets.prefixSlugs]}
            catalogSlugs={[...slugSets.catalogSlugs]}
        >
            <OwnerFeaturesProvider initialHasMyProperties={initialHasMyProperties}>
                <DashboardLayoutClient>{children}</DashboardLayoutClient>
            </OwnerFeaturesProvider>
        </ApartmentCatalogSlugProvider>
    );
}
