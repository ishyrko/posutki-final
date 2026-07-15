'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
    createPlacementPurchase,
    getPlacementPurchase,
    getPlacementSlots,
    getPropertyPlacementPurchases,
    getStandardPlacementPrice,
} from './api';

export function usePlacementSlots(cityId: number | null | undefined) {
    return useQuery({
        queryKey: ['placement-slots', cityId],
        queryFn: () => getPlacementSlots(cityId!),
        enabled: !!cityId && cityId > 0,
    });
}

export function useStandardPlacementPrice(cityId: number | null | undefined) {
    return useQuery({
        queryKey: ['placement-standard-price', cityId],
        queryFn: () => getStandardPlacementPrice(cityId!),
        enabled: !!cityId && cityId > 0,
    });
}

export function usePlacementPurchase(id: number | null | undefined) {
    return useQuery({
        queryKey: ['placement-purchase', id],
        queryFn: () => getPlacementPurchase(id!),
        enabled: !!id && id > 0,
        refetchInterval: (query) =>
            query.state.data?.status === 'pending_payment' ? 15000 : false,
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
