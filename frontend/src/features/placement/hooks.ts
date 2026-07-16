'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
    createPlacementPurchase,
    createPlacementPayment,
    confirmPlacementPayment,
    getMyPlacementPurchases,
    getPendingPlacementPaymentCount,
    getPlacementPurchase,
    getPlacementLevels,
    getPlacementScope,
    getPropertyPlacementPurchases,
} from './api';
import type { PlacementTariffScope } from './types';

export function usePlacementLevels(scope: PlacementTariffScope | null | undefined) {
    return useQuery({
        queryKey: ['placement-levels', scope],
        queryFn: () => getPlacementLevels(scope!),
        enabled: !!scope,
    });
}

export function usePlacementScope(scope: PlacementTariffScope | null | undefined) {
    return useQuery({
        queryKey: ['placement-scope', scope],
        queryFn: () => getPlacementScope(scope!),
        enabled: !!scope,
    });
}

export function useMyPlacementPurchases() {
    return useQuery({
        queryKey: ['my-placement-purchases'],
        queryFn: getMyPlacementPurchases,
    });
}

export function usePendingPlacementPaymentCount() {
    return useQuery({
        queryKey: ['placement-purchases-pending-count'],
        queryFn: getPendingPlacementPaymentCount,
        refetchInterval: 60_000,
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
            queryClient.invalidateQueries({ queryKey: ['placement-levels'] });
            queryClient.invalidateQueries({ queryKey: ['placement-scope'] });
            queryClient.invalidateQueries({ queryKey: ['my-properties'] });
            queryClient.invalidateQueries({ queryKey: ['my-placement-purchases'] });
            queryClient.invalidateQueries({ queryKey: ['placement-purchases-pending-count'] });
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
            queryClient.invalidateQueries({ queryKey: ['my-placement-purchases'] });
            queryClient.invalidateQueries({ queryKey: ['placement-purchases-pending-count'] });
            queryClient.invalidateQueries({ queryKey: ['my-properties'] });
        },
    });
}
