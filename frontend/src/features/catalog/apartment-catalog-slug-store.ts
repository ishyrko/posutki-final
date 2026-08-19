import type { ApartmentCatalogCity } from '@/features/home/apartment-catalog-cities';

export interface ApartmentCatalogSlugSets {
    cities: ApartmentCatalogCity[];
    prefixSlugs: ReadonlySet<string>;
    catalogSlugs: ReadonlySet<string>;
}

let store: ApartmentCatalogSlugSets | null = null;

export function configureApartmentCatalogSlugs(sets: ApartmentCatalogSlugSets): void {
    store = sets;
}

function requireStore(): ApartmentCatalogSlugSets {
    if (!store) {
        throw new Error(
            'Apartment catalog slugs are not configured. Ensure ApartmentCatalogSlugProvider wraps the app.',
        );
    }

    return store;
}

export function isCityPrefixSlug(slug: string): boolean {
    return requireStore().prefixSlugs.has(slug);
}

export function isCatalogApartmentCitySlug(slug: string): boolean {
    return requireStore().catalogSlugs.has(slug);
}

export function getCityPrefixSlugs(): ReadonlySet<string> {
    return requireStore().prefixSlugs;
}

export function getCatalogApartmentCitySlugs(): ReadonlySet<string> {
    return requireStore().catalogSlugs;
}

export function getCityPrefixSlugList(): readonly string[] {
    return [...requireStore().prefixSlugs];
}

export function getCatalogApartmentCitySlugList(): readonly string[] {
    return [...requireStore().catalogSlugs];
}
