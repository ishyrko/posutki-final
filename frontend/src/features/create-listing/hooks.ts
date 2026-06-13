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
import { CITY_SEARCH_MIN_LENGTH, requiresApartmentAddress } from './validation';

export const useCities = () => {
    return useQuery({
        queryKey: ['cities'],
        queryFn: getCities,
        staleTime: 1000 * 60 * 30,
    });
};

export const useHomePageCities = (enabled = true) => {
    return useQuery({
        queryKey: ['home-page-cities'],
        queryFn: getHomePageCities,
        staleTime: 1000 * 60 * 30,
        enabled,
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

export const useCityAutocompleteResults = (query: string, propertyType = 'apartment') => {
    const includeHomePageCities = requiresApartmentAddress(propertyType);
    const { data: homePageCities = [], isLoading: homePageLoading } = useHomePageCities(includeHomePageCities);
    const { data: searchResults = [], isFetching: searchFetching } = useSearchCities(query);

    const cityResults = useMemo(
        () => resolveCityAutocompleteResults(query, homePageCities, searchResults, includeHomePageCities),
        [query, homePageCities, searchResults, includeHomePageCities],
    );

    const citySearching = (includeHomePageCities && homePageLoading) || searchFetching;
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
