import api from '@/lib/api';

export interface ApartmentCatalogCity {
    id: number;
    name: string;
    slug: string;
    shortName: string;
    namePrepositional?: string | null;
    nameGenitive?: string | null;
    isMain: boolean;
    regionSlug?: string | null;
    regionName?: string | null;
}

interface ApartmentCatalogApiResponse {
    cities: ApartmentCatalogCity[];
    prefixSlugs?: string[];
    catalogSlugs?: string[];
}

export async function fetchApartmentCatalogCities(): Promise<ApartmentCatalogCity[]> {
    try {
        const response = await api.get<{ data: ApartmentCatalogApiResponse }>('/address/cities/apartment-catalog');
        return response.data?.data?.cities ?? [];
    } catch {
        return [];
    }
}

export interface ApartmentCatalogSnapshot {
    cities: ApartmentCatalogCity[];
    prefixSlugs: string[];
}

/** Cities plus URL prefix slugs, so catalog links can be built without the module slug store. */
export async function fetchApartmentCatalogSnapshot(): Promise<ApartmentCatalogSnapshot> {
    try {
        const response = await api.get<{ data: ApartmentCatalogApiResponse }>('/address/cities/apartment-catalog');
        return {
            cities: response.data?.data?.cities ?? [],
            prefixSlugs: response.data?.data?.prefixSlugs ?? [],
        };
    } catch {
        return { cities: [], prefixSlugs: [] };
    }
}
