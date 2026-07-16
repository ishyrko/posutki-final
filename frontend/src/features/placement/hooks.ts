'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
    createPlacementPurchase,
    createPlacementPayment,
    confirmPlacementPayment,
    getPlacementPurchase,
    getPlacementSlots,
    getPropertyPlacementPurchases,
    getStandardPlacementPrice,
} from './api';
import type { PlacementTariffScope } from './types';

export function usePlacementSlots(scope: PlacementTariffScope | null | undefined) {
    return useQuery({
        queryKey: ['placement-slots', scope],
        queryFn: () => getPlacementSlots(scope!),
        enabled: !!scope,
    });
}

export function useStandardPlacementPrice(scope: PlacementTariffScope | null | undefined) {
    return useQuery({
        queryKey: ['placement-standard-price', scope],
        queryFn: () => getStandardPlacementPrice(scope!),
        enabled: !!scope,
    });
}

export function usePlacementPurchase(
    id: number | null | undefined,
    options?: { pollWhilePending?: boolean },
) {
    return useQuery({
        queryKey: ['placement-purchase', id],
        queryFn: () => getPlacementPurchase(id!),
        enabled: !!id && id > 0,
        refetchInterval: (query) => {
            if (query.state.data?.status !== 'pending_payment') {
                return false;
            }
            return options?.pollWhilePending ? 3000 : 15000;
        },
    });
}

export function usePropertyPlacementPurchases(propertyId: number | null | undefined) {
    return useQuery({
        queryKey: ['property-placement-purchases', propertyId],
        queryFn: () => getPropertyPlacementPurchases(propertyId!),
        enabled: !!propertyId && propertyId > 0,
    });
}

export function useCreatePlacementPurchase() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: createPlacementPurchase,
        onSuccess: (data) => {
            queryClient.invalidateQueries({ queryKey: ['placement-slots'] });
            queryClient.invalidateQueries({ queryKey: ['my-properties'] });
            queryClient.invalidateQueries({
                queryKey: ['property-placement-purchases', data.propertyId],
            });
        },
    });
}

export function useCreatePlacementPayment() {
    return useMutation({
        mutationFn: createPlacementPayment,
    });
}

export function useConfirmPlacementPayment() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: ({ purchaseId, token }: { purchaseId: number; token: string }) =>
            confirmPlacementPayment(purchaseId, token),
        onSuccess: (data) => {
            queryClient.setQueryData(['placement-purchase', data.id], data);
            queryClient.invalidateQueries({ queryKey: ['property-placement-purchases', data.propertyId] });
            queryClient.invalidateQueries({ queryKey: ['my-properties'] });
        },
    });
}
