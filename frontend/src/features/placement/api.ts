import api from '@/lib/api';
import type { PlacementPurchase, PlacementSlot, PlacementTariffScope, StandardPlacementPrice } from './types';

export const getPlacementSlots = async (scope: PlacementTariffScope): Promise<PlacementSlot[]> => {
    const params =
        scope.propertyType === 'house'
            ? { propertyType: 'house', regionId: scope.regionId }
            : { cityId: scope.cityId };

    const response = await api.get<{ data: PlacementSlot[] }>('/placement-slots', { params });
    return response.data.data;
};

export const getStandardPlacementPrice = async (
    scope: PlacementTariffScope,
): Promise<StandardPlacementPrice | null> => {
    const params =
        scope.propertyType === 'house'
            ? { propertyType: 'house', regionId: scope.regionId }
            : { cityId: scope.cityId };

    const response = await api.get<{ data: StandardPlacementPrice | null }>(
        '/placement/standard-price',
        { params },
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

export const createPlacementPayment = async (
    purchaseId: number,
): Promise<{ redirectUrl: string; paymentId?: number }> => {
    const response = await api.post<{ data: { redirectUrl: string; paymentId?: number } }>(
        `/placement-purchases/${purchaseId}/payments`,
    );
    return response.data.data;
};
