import { cache } from 'react';
import { fetchPublicApi } from '@/lib/server-api';
import {
    configureApartmentCatalogSlugs,
    type ApartmentCatalogSlugSets,
} from '@/features/catalog/apartment-catalog-slug-store';
import type { ApartmentCatalogCity } from '@/features/home/apartment-catalog-cities';

interface ApartmentCatalogApiResponse {
    cities: ApartmentCatalogCity[];
    prefixSlugs: string[];
    catalogSlugs: string[];
}

export const fetchApartmentCatalogSlugSets = cache(async (): Promise<ApartmentCatalogSlugSets> => {
    const data = await fetchPublicApi<ApartmentCatalogApiResponse>('/address/cities/apartment-catalog', {
        next: { revalidate: 300 },
    });

    return {
        cities: data.cities ?? [],
        prefixSlugs: new Set(data.prefixSlugs ?? []),
        catalogSlugs: new Set(data.catalogSlugs ?? []),
    };
});

export const ensureApartmentCatalogSlugsConfigured = cache(async (): Promise<ApartmentCatalogSlugSets> => {
    const sets = await fetchApartmentCatalogSlugSets();
    configureApartmentCatalogSlugs(sets);
    return sets;
});
