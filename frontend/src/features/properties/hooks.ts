'use client';

import { useMemo, useSyncExternalStore } from 'react';
import { useQuery, useQueries, useMutation, useQueryClient } from '@tanstack/react-query';
import { getProperties, getProperty, getMyProperties, updateProperty, UpdatePropertyPayload, getFavoriteIds, addFavorite, removeFavorite, getFavorites, getExchangeRates, getPropertyStats, archiveProperty, unarchiveProperty, deleteProperty, getPropertyCalendar, getOwnerListings, getOwnerCalendar, createAvailabilityBlock, deleteAvailabilityBlock } from './api';
import { Property, PropertyFilters, PropertyListResponse } from './types';
import { isAuthenticated } from '@/lib/auth';
import { useIsHydrated } from '@/hooks/useIsHydrated';
import {
    addLocalFavoriteId,
    getLocalFavoriteIdsSnapshot,
    getLocalFavoriteIdsServerSnapshot,
    removeLocalFavoriteId,
    subscribeLocalFavorites,
} from '@/lib/favorites-storage';

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
    const isHydrated = useIsHydrated();
    const authenticated = isHydrated && isAuthenticated();
    const localIds = useSyncExternalStore(
        subscribeLocalFavorites,
        getLocalFavoriteIdsSnapshot,
        getLocalFavoriteIdsServerSnapshot,
    );
    const serverQuery = useQuery({
        queryKey: ['favorite-ids'],
        queryFn: getFavoriteIds,
        enabled: authenticated,
        retry: false,
        staleTime: 30_000,
    });

    if (authenticated) {
        return serverQuery;
    }

    return {
        ...serverQuery,
        data: isHydrated ? localIds : getLocalFavoriteIdsServerSnapshot(),
        isLoading: false,
        isFetching: false,
        isSuccess: true,
        isError: false,
        status: 'success' as const,
    };
};

export const useFavorites = (page = 1, limit = 20) => {
    return useQuery({
        queryKey: ['favorites', page, limit],
        queryFn: () => getFavorites(page, limit),
        enabled: isAuthenticated(),
    });
};

export const useLocalFavoriteProperties = () => {
    const isHydrated = useIsHydrated();
    const favoriteIds = useSyncExternalStore(
        subscribeLocalFavorites,
        getLocalFavoriteIdsSnapshot,
        getLocalFavoriteIdsServerSnapshot,
    );
    const effectiveIds = isHydrated ? favoriteIds : getLocalFavoriteIdsServerSnapshot();

    const propertyQueries = useQueries({
        queries: effectiveIds.map((id) => ({
            queryKey: ['property', id],
            queryFn: () => getProperty(id),
            staleTime: 60_000,
            enabled: isHydrated,
        })),
    });

    const properties = useMemo(
        () =>
            propertyQueries
                .map((query) => query.data)
                .filter((property): property is Property => property != null),
        [propertyQueries],
    );

    const isLoading =
        isHydrated &&
        effectiveIds.length > 0 &&
        propertyQueries.some((query) => query.isLoading || query.isFetching);

    return {
        properties,
        isLoading,
        total: effectiveIds.length,
    };
};

export const useFavoritesPage = (page = 1, limit = 20) => {
    const isHydrated = useIsHydrated();
    const authenticated = isHydrated && isAuthenticated();
    const serverFavorites = useQuery({
        queryKey: ['favorites', page, limit],
        queryFn: () => getFavorites(page, limit),
        enabled: authenticated,
    });
    const localFavorites = useLocalFavoriteProperties();

    if (!isHydrated) {
        return {
            properties: [] as Property[],
            isLoading: false,
            total: 0,
        };
    }

    if (authenticated) {
        return {
            properties: serverFavorites.data?.data ?? [],
            isLoading: serverFavorites.isLoading,
            total: serverFavorites.data?.data.length ?? 0,
        };
    }

    return {
        properties: localFavorites.properties,
        isLoading: localFavorites.isLoading,
        total: localFavorites.total,
    };
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
            if (!isAuthenticated()) {
                if (isFavorited) {
                    removeLocalFavoriteId(propertyId);
                } else {
                    addLocalFavoriteId(propertyId);
                }
                return;
            }

            if (isFavorited) {
                await removeFavorite(propertyId);
            } else {
                await addFavorite(propertyId);
            }
        },
        onMutate: async ({ propertyId, isFavorited }) => {
            if (!isAuthenticated()) {
                return {};
            }

            await queryClient.cancelQueries({ queryKey: ['favorite-ids'] });
            const previous = queryClient.getQueryData<number[]>(['favorite-ids']);
            queryClient.setQueryData<number[]>(['favorite-ids'], (old = []) =>
                isFavorited ? old.filter((id) => id !== propertyId) : [...old, propertyId]
            );
            return { previous };
        },
        onError: (_err, _vars, context) => {
            if (!isAuthenticated()) {
                return;
            }

            if (context?.previous) {
                queryClient.setQueryData(['favorite-ids'], context.previous);
            }
        },
        onSettled: () => {
            if (!isAuthenticated()) {
                return;
            }

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

export const useArchiveProperty = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (id: number) => archiveProperty(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['my-properties'] });
            queryClient.invalidateQueries({ queryKey: ['properties'] });
        },
    });
};

export const useUnarchiveProperty = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (id: number) => unarchiveProperty(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['my-properties'] });
            queryClient.invalidateQueries({ queryKey: ['properties'] });
        },
    });
};

export const useDeleteProperty = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (id: number) => deleteProperty(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['my-properties'] });
            queryClient.invalidateQueries({ queryKey: ['properties'] });
        },
    });
};

export const usePropertyCalendar = (id: number, enabled = true) => {
    return useQuery({
        queryKey: ['property-calendar', id],
        queryFn: () => getPropertyCalendar(id),
        enabled: id > 0 && enabled,
        staleTime: 15 * 60 * 1000,
    });
};

export const useOwnerListings = (propertyId: number, limit = 10) => {
    return useQuery({
        queryKey: ['owner-listings', propertyId, limit],
        queryFn: () => getOwnerListings(propertyId, limit),
        enabled: propertyId > 0,
        staleTime: 5 * 60 * 1000,
    });
};

export const useOwnerCalendar = (propertyId: number) => {
    return useQuery({
        queryKey: ['owner-calendar', propertyId],
        queryFn: () => getOwnerCalendar(propertyId),
        enabled: propertyId > 0 && isAuthenticated(),
    });
};

export const useCreateAvailabilityBlock = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: ({
            propertyId,
            startDate,
            endDate,
            note,
        }: {
            propertyId: number;
            startDate: string;
            endDate: string;
            note?: string;
        }) => createAvailabilityBlock(propertyId, { startDate, endDate, note }),
        onSuccess: (_data, variables) => {
            queryClient.invalidateQueries({ queryKey: ['owner-calendar', variables.propertyId] });
            queryClient.invalidateQueries({ queryKey: ['property-calendar', variables.propertyId] });
        },
    });
};

export const useDeleteAvailabilityBlock = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: ({ propertyId, blockId }: { propertyId: number; blockId: string }) =>
            deleteAvailabilityBlock(propertyId, blockId),
        onSuccess: (_data, variables) => {
            queryClient.invalidateQueries({ queryKey: ['owner-calendar', variables.propertyId] });
            queryClient.invalidateQueries({ queryKey: ['property-calendar', variables.propertyId] });
        },
    });
};
