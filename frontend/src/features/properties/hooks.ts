'use client';

import { useMemo, useSyncExternalStore } from 'react';
import { useQuery, useQueries, useMutation, useQueryClient } from '@tanstack/react-query';
import { getProperties, getProperty, getMyProperties, updateProperty, UpdatePropertyPayload, getFavoriteIds, addFavorite, removeFavorite, trackAnonymousFavorite, removeAnonymousFavorite, getFavorites, getExchangeRates, getPropertyStats, archiveProperty, unarchiveProperty, deleteProperty, getPropertyCalendar, getOwnerListings, getOwnerCalendar, createAvailabilityBlock, deleteAvailabilityBlock } from './api';
import { Property, PropertyFilters, PropertyListResponse } from './types';
import { isAuthenticated } from '@/lib/auth';
import { useIsHydrated } from '@/hooks/useIsHydrated';
import { useOwnerFeaturesContext } from './OwnerFeaturesProvider';
import {
    addLocalFavoriteId,
    getLocalFavoriteIdsSnapshot,
    getLocalFavoriteIdsServerSnapshot,
    removeLocalFavoriteId,
    subscribeLocalFavorites,
} from '@/lib/favorites-storage';
import { getOrCreateVisitorId } from '@/lib/view-tracking';
import { trackPropertyEngagementEvent } from '@/lib/gtag';

type FavoriteAnalyticsContext = {
    type?: string;
    address?: { cityName?: string };
};

type ToggleFavoriteParams = {
    propertyId: number;
    isFavorited: boolean;
    property?: FavoriteAnalyticsContext;
};

type UsePropertiesOptions = {
    /** SSR / dehydrated list — avoids treating data as stale at t=0 (immediate background refetch + flicker). */
    initialData?: PropertyListResponse;
    /** Set once per mount (e.g. useRef(Date.now())) — do not pass a new timestamp every render. */
    initialDataUpdatedAt?: number;
};

/** Примитивный ключ: объект `filters` в queryKey менялся по ссылке при каждом рендере каталога. */
export function propertyFiltersQueryKey(filters: PropertyFilters = {}) {
    return [
        'properties',
        filters.page ?? 1,
        filters.limit ?? 20,
        filters.type ?? null,
        filters.types ? [...filters.types].sort().join(',') : null,
        filters.dealType ?? null,
        filters.regionSlug ?? null,
        filters.citySlug ?? null,
        filters.cityId ?? null,
        filters.cityDistrictSlug ?? null,
        filters.landmarkSlug ?? null,
        filters.minPrice ?? null,
        filters.maxPrice ?? null,
        filters.currency ?? null,
        filters.roomValues ? [...filters.roomValues].join(',') : null,
        filters.metroStationId ?? null,
        filters.nearMetro ?? null,
        filters.guests ?? null,
        filters.sortBy ?? null,
        filters.sortOrder ?? null,
    ] as const;
}

export const useProperties = (filters: PropertyFilters = {}, options?: UsePropertiesOptions) => {
    const hasInitial = options?.initialData !== undefined;
    return useQuery({
        queryKey: propertyFiltersQueryKey(filters),
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
    /** SSR payload — avoids loading state on first paint. */
    initialData?: Property;
    /** Set once per mount (e.g. useRef(Date.now())) — do not pass a new timestamp every render. */
    initialDataUpdatedAt?: number;
};

export const useProperty = (id: number, options: UsePropertyOptions = {}) => {
    const hasInitial = options.initialData !== undefined;
    return useQuery({
        queryKey: ['property', id],
        queryFn: () => getProperty(id),
        enabled: id > 0,
        ...(hasInitial
            ? {
                  initialData: options.initialData,
                  ...(options.initialDataUpdatedAt !== undefined
                      ? { initialDataUpdatedAt: options.initialDataUpdatedAt }
                      : {}),
              }
            : {}),
    });
};

export const useMyProperties = (page = 1, limit = 20) => {
    return useQuery({
        queryKey: ['my-properties', page, limit],
        queryFn: () => getMyProperties(page, limit),
        enabled: isAuthenticated(),
    });
};

/** True, если у пользователя есть хотя бы одно объявление (любой статус). */
export const useHasMyProperties = () => {
    const ownerFeatures = useOwnerFeaturesContext();
    const query = useMyProperties(1, 1);

    const hasMyProperties = query.isSuccess
        ? query.data.data.length > 0
        : (ownerFeatures?.initialHasMyProperties ?? false);

    return {
        hasMyProperties,
        isLoading: !query.isSuccess && query.isLoading,
    };
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
        mutationFn: async ({ propertyId, isFavorited, property }: ToggleFavoriteParams) => {
            if (!isFavorited) {
                trackPropertyEngagementEvent('add_to_favorites', {
                    id: propertyId,
                    type: property?.type ?? 'unknown',
                    address: property?.address,
                });
            }

            if (!isAuthenticated()) {
                const visitorId = getOrCreateVisitorId();
                if (isFavorited) {
                    removeLocalFavoriteId(propertyId);
                    if (visitorId) {
                        void removeAnonymousFavorite(propertyId, visitorId).catch(() => {});
                    }
                } else {
                    addLocalFavoriteId(propertyId);
                    if (visitorId) {
                        void trackAnonymousFavorite(propertyId, visitorId).catch(() => {});
                    }
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
