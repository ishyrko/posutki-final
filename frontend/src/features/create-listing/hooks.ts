'use client';

import { useMemo } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { resolveCityAutocompleteResults } from './city-autocomplete';
import {
    getCities,
    getHomePageCities,
    searchCities,
    searchStreets,
    createProperty,
    uploadFile,
} from './api';
import { CreatePropertyPayload } from './types';
import { CITY_SEARCH_MIN_LENGTH } from './validation';

export const useCities = () => {
    return useQuery({
        queryKey: ['cities'],
        queryFn: getCities,
        staleTime: 1000 * 60 * 30,
    });
};

export const useHomePageCities = () => {
    return useQuery({
        queryKey: ['home-page-cities'],
        queryFn: getHomePageCities,
        staleTime: 1000 * 60 * 30,
    });
};

export const useSearchCities = (query: string) => {
    return useQuery({
        queryKey: ['cities-search', query],
        queryFn: () => searchCities(query),
        enabled: query.length >= CITY_SEARCH_MIN_LENGTH,
        staleTime: 60_000,
    });
};

export const useCityAutocompleteResults = (query: string) => {
    const { data: homePageCities = [], isLoading: homePageLoading } = useHomePageCities();
    const { data: searchResults = [], isFetching: searchFetching } = useSearchCities(query);

    const cityResults = useMemo(
        () => resolveCityAutocompleteResults(query, homePageCities, searchResults),
        [query, homePageCities, searchResults],
    );

    const citySearching = homePageLoading || searchFetching;
    const showCityNotFound =
        query.length >= CITY_SEARCH_MIN_LENGTH && !citySearching && cityResults.length === 0;

    return { cityResults, citySearching, showCityNotFound };
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
