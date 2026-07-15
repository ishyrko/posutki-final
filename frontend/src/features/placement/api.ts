import api from '@/lib/api';
import type { PlacementPurchase, PlacementSlot, StandardPlacementPrice } from './types';

export const getPlacementSlots = async (cityId: number): Promise<PlacementSlot[]> => {
    const response = await api.get<{ data: PlacementSlot[] }>('/placement-slots', {
        params: { cityId },
    });
    return response.data.data;
};

export const getStandardPlacementPrice = async (
    cityId: number,
): Promise<StandardPlacementPrice | null> => {
    const response = await api.get<{ data: StandardPlacementPrice | null }>(
        '/placement/standard-price',
        { params: { cityId } },
    );
    return response.data.data;
};

export const createPlacementPurchase = async (input: {
    propertyId: number;
    type: 'special' | 'standard';
    durationMonths: number;
    slotId?: number | null;
}): Promise<PlacementPurchase> => {
    const response = await api.post<{ data: PlacementPurchase }>(
        `/properties/${input.propertyId}/placement-purchases`,
        {
            type: input.type,
            durationMonths: input.durationMonths,
            slotId: input.slotId ?? undefined,
        },
    );
    return response.data.data;
};

export const getPlacementPurchase = async (id: number): Promise<PlacementPurchase> => {
    const response = await api.get<{ data: PlacementPurchase }>(`/placement-purchases/${id}`);
    return response.data.data;
};

export const getPropertyPlacementPurchases = async (
    propertyId: number,
): Promise<PlacementPurchase[]> => {
    const response = await api.get<{ data: PlacementPurchase[] }>(
        `/properties/${propertyId}/placement-purchases`,
    );
    return response.data.data;
};
