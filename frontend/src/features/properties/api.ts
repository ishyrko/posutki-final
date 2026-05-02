import axios from 'axios';
import api from '@/lib/api';
import { Property, PropertyFilters, PropertyListResponse, PropertyStats, type Currency } from './types';
import { getMockPropertiesResponse, getMockProperty } from './mock-data';

export type ExchangeRates = Record<Currency, number>;

export const getExchangeRates = async (): Promise<ExchangeRates> => {
    try {
        const response = await api.get<{ data: ExchangeRates }>('/exchange-rates');
        return response.data.data;
    } catch {
        return { BYN: 1, USD: 3.27, EUR: 3.49 };
    }
};

export const getProperties = async (filters: PropertyFilters = {}): Promise<PropertyListResponse> => {
    try {
        const params = new URLSearchParams();

        if (filters.page) params.append('page', filters.page.toString());
        if (filters.limit) params.append('limit', filters.limit.toString());
        if (filters.types?.length) {
            params.append('types', filters.types.join(','));
        } else if (filters.type) {
            params.append('type', filters.type);
        }
        if (filters.dealType) params.append('dealType', filters.dealType);
        if (filters.regionSlug) params.append('regionSlug', filters.regionSlug);
        if (filters.citySlug) params.append('citySlug', filters.citySlug);
        if (filters.cityId) params.append('cityId', filters.cityId.toString());
        if (filters.minPrice) params.append('minPrice', filters.minPrice.toString());
        if (filters.maxPrice) params.append('maxPrice', filters.maxPrice.toString());
        if (filters.priceType && filters.priceType !== 'total') params.append('priceType', filters.priceType);
        if (filters.currency) params.append('currency', filters.currency);
        if (filters.rooms) params.append('rooms', filters.rooms.toString());
        if (filters.metroStationId) params.append('metroStationId', filters.metroStationId.toString());
        if (filters.nearMetro) params.append('nearMetro', '1');
        if (filters.sortBy) params.append('sortBy', filters.sortBy);
        if (filters.sortOrder) params.append('sortOrder', filters.sortOrder);

        const response = await api.get<{ data: Property[] }>(`/properties?${params.toString()}`);
        const properties = response.data.data;
        return {
            data: properties,
            meta: { total: properties.length, page: filters.page || 1, limit: filters.limit || 20 },
        };
    } catch {
        return getMockPropertiesResponse(filters);
    }
};

export const getProperty = async (id: number): Promise<Property> => {
    try {
        const response = await api.get<{ data: Property }>(`/properties/${id}`);
        return response.data.data;
    } catch (error: unknown) {
        if (axios.isAxiosError(error) && error.response?.status === 404) {
            throw new Error('Property not found');
        }
        const mock = getMockProperty(id);
        if (mock) return mock;
        throw new Error('Property not found');
    }
};

export interface UpdatePropertyPayload {
    type?: string;
    dealType?: string;
    title?: string;
    description?: string;
    price?: { amount: number; currency: string };
    area?: number;
    landArea?: number;
    rooms?: number;
    floor?: number;
    totalFloors?: number;
    bathrooms?: number;
    yearBuilt?: number;
    renovation?: string;
    balcony?: string;
    livingArea?: number;
    kitchenArea?: number;
    roomsInDeal?: number;
    roomsArea?: number;
    dealConditions?: string[];
    maxDailyGuests?: number;
    dailySingleBeds?: number;
    dailyDoubleBeds?: number;
    checkInTime?: string;
    checkOutTime?: string;
    building?: string;
    block?: string;
    cityId?: number;
    streetId?: number | null;
    coordinates?: { latitude: number; longitude: number };
    images?: string[];
    amenities?: string[];
}

export const updateProperty = async (id: number, data: UpdatePropertyPayload): Promise<void> => {
    await api.patch(`/properties/${id}`, data);
};

export const getFavoriteIds = async (): Promise<number[]> => {
    const response = await api.get<{ data: number[] }>('/favorites/ids');
    return response.data.data;
};

export const addFavorite = async (propertyId: number): Promise<void> => {
    await api.post(`/favorites/${propertyId}`);
};

export const removeFavorite = async (propertyId: number): Promise<void> => {
    await api.delete(`/favorites/${propertyId}`);
};

export const getFavorites = async (page = 1, limit = 20): Promise<PropertyListResponse> => {
    try {
        const response = await api.get<{ data: Property[] }>(`/favorites?page=${page}&limit=${limit}`);
        const properties = response.data.data;
        return {
            data: properties,
            meta: { total: properties.length, page, limit },
        };
    } catch {
        return { data: [], meta: { total: 0, page, limit } };
    }
};

export const getMyProperties = async (page = 1, limit = 20): Promise<PropertyListResponse> => {
    try {
        const response = await api.get<{ data: Property[] }>(`/properties/my?page=${page}&limit=${limit}`);
        const properties = response.data.data;
        return {
            data: properties,
            meta: { total: properties.length, page, limit },
        };
    } catch {
        return getMockPropertiesResponse({ page, limit });
    }
};

export const trackPhoneView = async (id: number): Promise<void> => {
    await api.post(`/properties/${id}/phone-view`);
};

export const getPropertyStats = async (id: number, period: 7 | 30 | 90): Promise<PropertyStats> => {
    const response = await api.get<{ data: PropertyStats }>(`/properties/${id}/stats?period=${period}`);
    return response.data.data;
};

export const boostProperty = async (id: number): Promise<{ boostedAt: string }> => {
    const response = await api.post<{ data: { boostedAt: string } }>(`/properties/${id}/boost`);
    return response.data.data;
};
