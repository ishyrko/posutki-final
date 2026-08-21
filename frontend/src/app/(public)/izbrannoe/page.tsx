import { CatalogSlugProviderFromSets } from '@/components/CatalogSlugProviderFromSets';
import { fetchApartmentCatalogSlugSets } from '@/lib/apartment-catalog-slugs-server';
import { PublicFavoritesPageClient } from './PublicFavoritesPageClient';

export default async function PublicFavoritesPage() {
    const slugSets = await fetchApartmentCatalogSlugSets();

    return (
        <CatalogSlugProviderFromSets sets={slugSets}>
            <PublicFavoritesPageClient />
        </CatalogSlugProviderFromSets>
    );
}
