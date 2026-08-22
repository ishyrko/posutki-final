import type { ApartmentCatalogCity } from '@/features/home/apartment-catalog-cities';

export interface ApartmentCatalogSlugSets {
    cities: ApartmentCatalogCity[];
    citiesBySlug: ReadonlyMap<string, ApartmentCatalogCity>;
    prefixSlugs: ReadonlySet<string>;
    catalogSlugs: ReadonlySet<string>;
}

let store: ApartmentCatalogSlugSets | null = null;

export function configureApartmentCatalogSlugs(sets: Omit<ApartmentCatalogSlugSets, 'citiesBySlug'> & {
    citiesBySlug?: ReadonlyMap<string, ApartmentCatalogCity>;
}): void {
    store = {
        ...sets,
        citiesBySlug: sets.citiesBySlug ?? new Map(sets.cities.map((city) => [city.slug, city])),
    };
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

export function getApartmentCatalogCity(slug: string): ApartmentCatalogCity | undefined {
    return requireStore().citiesBySlug.get(slug);
}

/** «в Лиде», «в Гродно» — предложный падеж с предлогом для заголовков каталога. */
export function formatCityPrepositionalLocation(namePrepositional: string): string {
    const trimmed = namePrepositional.trim();
    if (trimmed === '') {
        return trimmed;
    }

    if (trimmed.startsWith('в ') || trimmed.startsWith('во ')) {
        return trimmed;
    }

    return `в ${trimmed}`;
}

export function resolveCatalogApartmentLocation(citySlug: string): string | null {
    const city = getApartmentCatalogCity(citySlug);
    if (city?.namePrepositional) {
        return formatCityPrepositionalLocation(city.namePrepositional);
    }

    return null;
}

export function resolveCatalogCityNominativeFromStore(citySlug: string): string | null {
    const city = getApartmentCatalogCity(citySlug);
    return city?.name ?? null;
}

export function resolveCatalogCityGenitiveFromStore(citySlug: string): string | null {
    const city = getApartmentCatalogCity(citySlug);
    return city?.nameGenitive ?? null;
}
