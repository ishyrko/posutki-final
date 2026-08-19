import { HEADER_CITY_SLUGS } from '@/lib/region-header';
import type { CitySearchResult } from './types';
import { CITY_SEARCH_MIN_LENGTH } from './validation';

const MINSK_SLUG = 'minsk';

const REGIONAL_CITY_SLUGS = new Set(
    HEADER_CITY_SLUGS.filter((slug) => slug !== MINSK_SLUG),
);

const compareCityNamesRu = (a: CitySearchResult, b: CitySearchResult): number =>
    a.name.localeCompare(b.name, 'ru', { sensitivity: 'base' });

/** Минск → областные центры по алфавиту → остальные по алфавиту. */
export function sortListingFormCities(cities: CitySearchResult[]): CitySearchResult[] {
    const minsk = cities.find((city) => city.slug === MINSK_SLUG);
    const regional = cities
        .filter((city) => REGIONAL_CITY_SLUGS.has(city.slug))
        .sort(compareCityNamesRu);
    const others = cities
        .filter((city) => city.slug !== MINSK_SLUG && !REGIONAL_CITY_SLUGS.has(city.slug))
        .sort(compareCityNamesRu);

    return [...(minsk ? [minsk] : []), ...regional, ...others];
}

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

    const homeMatches = sortListingFormCities(
        homePageCities.filter((city) => cityMatchesQuery(city, normalizedQuery)),
    );
    const homeIds = new Set(homeMatches.map((city) => city.id));

    if (normalizedQuery.length < CITY_SEARCH_MIN_LENGTH) {
        return homeMatches;
    }

    const rest = searchResults
        .filter((city) => !homeIds.has(city.id))
        .sort(compareCityNamesRu);
    return [...homeMatches, ...rest];
}
