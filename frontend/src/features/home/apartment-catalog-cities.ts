import api from '@/lib/api';

export interface ApartmentCatalogCity {
    id: number;
    name: string;
    slug: string;
    shortName: string;
    isMain: boolean;
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
