import { fetchApartmentCatalogSlugSets } from '@/lib/apartment-catalog-slugs-server';
import type { ApartmentCatalogCity } from '@/features/home/apartment-catalog-cities';

export async function fetchApartmentCatalogCitiesForHome(): Promise<ApartmentCatalogCity[]> {
    try {
        const sets = await fetchApartmentCatalogSlugSets();
        return sets.cities;
    } catch {
        return [];
    }
}
