import api from '@/lib/api';
import type {
    PlacementLevelsResponse,
    PlacementPurchase,
    PlacementPurchaseQuote,
    PlacementScopeSettings,
    PlacementTariffScope,
} from './types';

export const getPlacementLevels = async (
    scope: PlacementTariffScope,
): Promise<PlacementLevelsResponse> => {
    const params =
        scope.propertyType === 'house'
            ? { propertyType: 'house', regionId: scope.regionId }
            : { propertyType: 'apartment', cityId: scope.cityId };

    const response = await api.get<{ data: PlacementLevelsResponse }>('/placement/levels', {
        params,
    });
    return response.data.data;
};

export const getPlacementScope = async (
    scope: PlacementTariffScope,
): Promise<PlacementScopeSettings> => {
    const params =
        scope.propertyType === 'house'
            ? { propertyType: 'house', regionId: scope.regionId }
            : { propertyType: 'apartment', cityId: scope.cityId };

    const response = await api.get<{ data: PlacementScopeSettings }>('/placement/scope', { params });
    return response.data.data;
};

export const createPlacementPurchase = async (input: {
    propertyId: number;
    kind: 'level' | 'boost';
    level?: number | null;
    durationMonths?: number | null;
}): Promise<PlacementPurchase> => {
    const response = await api.post<{ data: PlacementPurchase }>(
        `/properties/${input.propertyId}/placement-purchases`,
        {
            kind: input.kind,
            level: input.level ?? undefined,
            durationMonths: input.durationMonths ?? undefined,
        },
    );
    return response.data.data;
};

export const getPlacementPurchase = async (id: number): Promise<PlacementPurchase> => {
    const response = await api.get<{ data: PlacementPurchase }>(`/placement-purchases/${id}`);
    return response.data.data;
};

export const getMyPlacementPurchases = async (): Promise<PlacementPurchase[]> => {
    const response = await api.get<{ data: PlacementPurchase[] }>('/placement-purchases');
    return response.data.data;
};

export const getPendingPlacementPaymentCount = async (): Promise<number> => {
    const response = await api.get<{ data: { pendingCount: number } }>(
        '/placement-purchases/pending-count',
    );
    return response.data.data.pendingCount;
};

export const getPropertyPlacementPurchases = async (
    propertyId: number,
): Promise<PlacementPurchase[]> => {
    const response = await api.get<{ data: PlacementPurchase[] }>(
        `/properties/${propertyId}/placement-purchases`,
    );
    return response.data.data;
};

export const getPlacementPurchaseQuote = async (
    propertyId: number,
    params: { level: number; durationMonths: number },
): Promise<PlacementPurchaseQuote> => {
    const response = await api.get<{ data: PlacementPurchaseQuote }>(
        `/properties/${propertyId}/placement-purchases/quote`,
        { params },
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

export const confirmPlacementPayment = async (
    purchaseId: number,
    token: string,
): Promise<PlacementPurchase> => {
    const response = await api.post<{ data: PlacementPurchase }>(
        `/placement-purchases/${purchaseId}/payments/confirm`,
        { token },
    );
    return response.data.data;
};
