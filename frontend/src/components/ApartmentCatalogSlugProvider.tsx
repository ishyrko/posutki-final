'use client';

import type { ReactNode } from 'react';
import {
    configureApartmentCatalogSlugs,
    type ApartmentCatalogSlugSets,
} from '@/features/catalog/apartment-catalog-slug-store';

interface ApartmentCatalogSlugProviderProps {
    sets: ApartmentCatalogSlugSets;
    children: ReactNode;
}

export function ApartmentCatalogSlugProvider({ sets, children }: ApartmentCatalogSlugProviderProps) {
    configureApartmentCatalogSlugs(sets);
    return children;
}
