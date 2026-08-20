'use client';

import type { ReactNode } from 'react';
import {
    configureApartmentCatalogSlugs,
} from '@/features/catalog/apartment-catalog-slug-store';
import type { ApartmentCatalogCity } from '@/features/home/apartment-catalog-cities';

/** Serializable props for the RSC → client boundary (Set is not Flight-safe everywhere). */
export interface ApartmentCatalogSlugProviderProps {
    cities: ApartmentCatalogCity[];
    prefixSlugs: readonly string[];
    catalogSlugs: readonly string[];
    children: ReactNode;
}

export function ApartmentCatalogSlugProvider({
    cities,
    prefixSlugs,
    catalogSlugs,
    children,
}: ApartmentCatalogSlugProviderProps) {
    configureApartmentCatalogSlugs({
        cities,
        prefixSlugs: new Set(prefixSlugs),
        catalogSlugs: new Set(catalogSlugs),
    });
    return children;
}
