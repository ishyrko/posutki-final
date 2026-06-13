import type { CitySearchResult } from './types';
import { CITY_SEARCH_MIN_LENGTH } from './validation';

const normalizeCityQuery = (query: string): string =>
    query.trim().toLowerCase().replace(/ё/g, 'е');

const cityMatchesQuery = (city: CitySearchResult, normalizedQuery: string): boolean => {
    if (!normalizedQuery) {
        return true;
    }
    const name = city.name.toLowerCase().replace(/ё/g, 'е');
    return name.includes(normalizedQuery);
};

/** Сначала города с главной, затем остальные результаты поиска API (только для квартир). */
export function resolveCityAutocompleteResults(
    query: string,
    homePageCities: CitySearchResult[],
    searchResults: CitySearchResult[],
    includeHomePageCities = true,
): CitySearchResult[] {
    const normalizedQuery = normalizeCityQuery(query);

    if (!includeHomePageCities) {
        if (normalizedQuery.length < CITY_SEARCH_MIN_LENGTH) {
            return [];
        }
        return searchResults;
    }

    const homeMatches = homePageCities.filter((city) => cityMatchesQuery(city, normalizedQuery));
    const homeIds = new Set(homeMatches.map((city) => city.id));

    if (normalizedQuery.length < CITY_SEARCH_MIN_LENGTH) {
        return homeMatches;
    }

    const rest = searchResults.filter((city) => !homeIds.has(city.id));
    return [...homeMatches, ...rest];
}
