'use client';

import { useQuery, useMutation } from '@tanstack/react-query';
import { getCities, searchCities, searchStreets, createProperty, uploadFile } from './api';
import { CreatePropertyPayload } from './types';

export const useCities = () => {
    return useQuery({
        queryKey: ['cities'],
        queryFn: getCities,
        staleTime: 1000 * 60 * 30,
    });
};

export const useSearchCities = (query: string) => {
    return useQuery({
        queryKey: ['cities-search', query],
        queryFn: () => searchCities(query),
        enabled: query.length >= 2,
        staleTime: 60_000,
    });
};

export const useSearchStreets = (cityId: number | null, query: string) => {
    return useQuery({
        queryKey: ['streets', cityId, query],
        queryFn: () => searchStreets(cityId!, query),
        enabled: !!cityId && query.length >= 1,
        staleTime: 60_000,
    });
};

export const useCreateProperty = () => {
    return useMutation({
        mutationFn: (payload: CreatePropertyPayload) => createProperty(payload),
    });
};

export const useUploadFile = () => {
    return useMutation({
        mutationFn: (file: File) => uploadFile(file),
    });
};
