'use client';

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { getProperties, getProperty, getMyProperties, updateProperty, UpdatePropertyPayload, getFavoriteIds, addFavorite, removeFavorite, getFavorites, getExchangeRates, getPropertyStats, boostProperty } from './api';
import { Property, PropertyFilters, PropertyListResponse } from './types';
import { isAuthenticated } from '@/lib/auth';

type UsePropertiesOptions = {
    /** SSR / dehydrated list — avoids treating data as stale at t=0 (immediate background refetch + flicker). */
    initialData?: PropertyListResponse;
    /** Set once per mount (e.g. useRef(Date.now())) — do not pass a new timestamp every render. */
    initialDataUpdatedAt?: number;
};

export const useProperties = (filters: PropertyFilters = {}, options?: UsePropertiesOptions) => {
    const hasInitial = options?.initialData !== undefined;
    return useQuery({
        queryKey: ['properties', filters],
        queryFn: () => getProperties(filters),
        placeholderData: (previousData) => previousData, // Keep previous data while fetching new
        ...(hasInitial
            ? {
                  initialData: options.initialData,
                  ...(options?.initialDataUpdatedAt !== undefined
                      ? { initialDataUpdatedAt: options.initialDataUpdatedAt }
                      : {}),
              }
            : {}),
    });
};

type UsePropertyOptions = {
    initialData?: Property;
};

export const useProperty = (id: number, options: UsePropertyOptions = {}) => {
    return useQuery({
        queryKey: ['property', id],
        queryFn: () => getProperty(id),
        enabled: id > 0,
        initialData: options.initialData,
    });
};

export const useMyProperties = (page = 1, limit = 20) => {
    return useQuery({
        queryKey: ['my-properties', page, limit],
        queryFn: () => getMyProperties(page, limit),
        enabled: isAuthenticated(),
    });
};

export const useUpdateProperty = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: ({ id, data }: { id: number; data: UpdatePropertyPayload }) => updateProperty(id, data),
        onSuccess: (_result, variables) => {
            queryClient.invalidateQueries({ queryKey: ['property', variables.id] });
            queryClient.invalidateQueries({ queryKey: ['my-properties'] });
        },
    });
};

export const useFavoriteIds = () => {
    return useQuery({
        queryKey: ['favorite-ids'],
        queryFn: getFavoriteIds,
        enabled: isAuthenticated(),
        retry: false,
        staleTime: 30_000,
    });
};

export const useFavorites = (page = 1, limit = 20) => {
    return useQuery({
        queryKey: ['favorites', page, limit],
        queryFn: () => getFavorites(page, limit),
        enabled: isAuthenticated(),
    });
};

export const useExchangeRates = () => {
    return useQuery({
        queryKey: ['exchange-rates'],
        queryFn: getExchangeRates,
        staleTime: 60 * 60 * 1000, // 1 hour
    });
};

export const useToggleFavorite = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async ({ propertyId, isFavorited }: { propertyId: number; isFavorited: boolean }) => {
            if (isFavorited) {
                await removeFavorite(propertyId);
            } else {
                await addFavorite(propertyId);
            }
        },
        onMutate: async ({ propertyId, isFavorited }) => {
            await queryClient.cancelQueries({ queryKey: ['favorite-ids'] });
            const previous = queryClient.getQueryData<number[]>(['favorite-ids']);
            queryClient.setQueryData<number[]>(['favorite-ids'], (old = []) =>
                isFavorited ? old.filter((id) => id !== propertyId) : [...old, propertyId]
            );
            return { previous };
        },
        onError: (_err, _vars, context) => {
            if (context?.previous) {
                queryClient.setQueryData(['favorite-ids'], context.previous);
            }
        },
        onSettled: () => {
            queryClient.invalidateQueries({ queryKey: ['favorite-ids'] });
            queryClient.invalidateQueries({ queryKey: ['favorites'] });
        },
    });
};

export const usePropertyStats = (id: number, period: 7 | 30 | 90) => {
    return useQuery({
        queryKey: ['property-stats', id, period],
        queryFn: () => getPropertyStats(id, period),
        enabled: id > 0 && isAuthenticated(),
    });
};

export const useBoostProperty = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (id: number) => boostProperty(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['my-properties'] });
            queryClient.invalidateQueries({ queryKey: ['properties'] });
        },
    });
};
