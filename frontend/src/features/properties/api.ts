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
        return { BYN: 1, USD: 3.27, RUB: 0.034 };
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
        if (filters.roomValues && filters.roomValues.length > 0) {
            params.append('rooms', filters.roomValues.join(','));
        }
        if (filters.metroStationId) params.append('metroStationId', filters.metroStationId.toString());
        if (filters.nearMetro) params.append('nearMetro', '1');
        if (filters.guests) params.append('guests', filters.guests.toString());
        if (filters.sortBy) params.append('sortBy', filters.sortBy);
        if (filters.sortOrder) params.append('sortOrder', filters.sortOrder);

        const response = await api.get<{
            data: Property[];
            pagination?: { total: number; page: number; limit: number; pages: number };
        }>(`/properties?${params.toString()}`);
        const properties = response.data.data;
        const pagination = response.data.pagination;
        return {
            data: properties,
            meta: {
                total: pagination?.total ?? properties.length,
                page: pagination?.page ?? (filters.page || 1),
                limit: pagination?.limit ?? (filters.limit || 20),
            },
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
    paymentMethods?: string[];
    maxDailyGuests?: number;
    dailySingleBeds?: number;
    dailyDoubleBeds?: number;
    checkInTime?: string;
    checkOutTime?: string;
    building?: string;
    block?: string;
    cityId?: number;
    streetId?: number | null;
    streetName?: string;
    coordinates?: { latitude: number; longitude: number };
    images?: string[];
    amenities?: string[];
    weekendPriceNegotiable?: boolean;
    additionalServices?: Array<{ name: string; price: number }>;
    instagramUrl?: string;
    websiteUrl?: string;
    externalCalendarUrls?: string[];
}

export interface UpdatePropertyResult {
    message: string;
    requiresModeration: boolean;
}

export const updateProperty = async (id: number, data: UpdatePropertyPayload): Promise<UpdatePropertyResult> => {
    const response = await api.patch<{ data: UpdatePropertyResult }>(`/properties/${id}`, data);
    return response.data.data;
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

export const archiveProperty = async (id: number): Promise<{ archivedAt: string }> => {
    const response = await api.post<{ data: { archivedAt: string } }>(`/properties/${id}/archive`);
    return response.data.data;
};

export const unarchiveProperty = async (id: number): Promise<void> => {
    await api.post(`/properties/${id}/unarchive`);
};

export const deleteProperty = async (id: number): Promise<void> => {
    await api.delete(`/properties/${id}`);
};

export type BlockedDateRange = {
    start: string;
    end: string;
};

export type PropertyCalendarData = {
    blockedRanges: BlockedDateRange[];
    lastUpdatedAt: string | null;
};

export const getPropertyCalendar = async (id: number): Promise<PropertyCalendarData> => {
    const response = await api.get<{ data: PropertyCalendarData }>(`/properties/${id}/calendar`);
    return response.data.data;
};

export const getOwnerListings = async (propertyId: number, limit = 10): Promise<Property[]> => {
    const response = await api.get<{ data: Property[] }>(
        `/properties/${propertyId}/owner-listings?limit=${limit}`,
    );
    return response.data.data;
};

export type AvailabilityBlock = {
    id: string;
    start: string;
    end: string;
    note: string | null;
    createdAt: string;
};

export type OwnerCalendarData = {
    propertyId: string;
    propertyTitle: string;
    manualBlocks: AvailabilityBlock[];
    importedBlockedRanges: BlockedDateRange[];
    externalCalendarUrls: string[];
    externalCalendarSyncedAt: string | null;
    exportToken: string;
    exportUrl: string;
};

export const getOwnerCalendar = async (propertyId: number): Promise<OwnerCalendarData> => {
    const response = await api.get<{ data: OwnerCalendarData }>(`/properties/${propertyId}/availability`);
    return response.data.data;
};

export const createAvailabilityBlock = async (
    propertyId: number,
    payload: { startDate: string; endDate: string; note?: string },
): Promise<AvailabilityBlock> => {
    const response = await api.post<{ data: AvailabilityBlock }>(
        `/properties/${propertyId}/availability/block`,
        payload,
    );
    return response.data.data;
};

export const deleteAvailabilityBlock = async (propertyId: number, blockId: string): Promise<void> => {
    await api.delete(`/properties/${propertyId}/availability/block/${blockId}`);
};

export const regenerateCalendarExportToken = async (
    propertyId: number,
): Promise<{ exportToken: string; exportUrl: string }> => {
    const response = await api.post<{ data: { exportToken: string; exportUrl: string } }>(
        `/properties/${propertyId}/calendar/export-token`,
    );
    return response.data.data;
};
