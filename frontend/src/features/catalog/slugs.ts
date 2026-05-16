import {
  HEADER_REGION_MINSK_SLUG,
  regionNameToHeaderSlug,
  withRegionalCatalogHref,
} from "@/lib/region-header";

/** Regional path prefix (областные центры кроме Минска — в URL как первый сегмент). */
export const REGION_SLUGS: ReadonlySet<string> = new Set([
  'brest',
  'vitebsk',
  'gomel',
  'grodno',
  'mogilev',
] as const);

/** Только посуточная аренда — в URL сделка не кодируется, всегда daily. */
export const IMPLICIT_DEAL_TYPE = 'daily' as const;

/** Типы жилья в URL (посуточный каталог). */
export const PROPERTY_TYPE_SLUG_TO_VALUE: Record<string, string> = {
  kvartiry: 'apartment',
  doma: 'house',
};

export const PROPERTY_TYPE_VALUE_TO_SLUG = Object.fromEntries(
  Object.entries(PROPERTY_TYPE_SLUG_TO_VALUE).map(([slug, val]) => [val, slug]),
);

/** Slug города Минска в URL каталога и API (`citySlug`). */
export const MINSK_CITY_SLUG = 'minsk';

export const REGION_LABELS: Record<string, string> = {
  brest: 'Брест и область',
  vitebsk: 'Витебская область',
  gomel: 'Гомель и область',
  grodno: 'Гродно и область',
  mogilev: 'Могилев и область',
};

/** H1 / meta для страниц каталога по типу жилья. */
export const DAILY_DEAL_PAGE_TITLES: Record<string, string> = {
  apartment: 'Квартиры на сутки',
  house: 'Дома на сутки',
};

export const PROPERTY_TYPE_LABELS: Record<string, string> = {
  apartment: 'квартир',
  house: 'домов',
};

export interface ParsedSegments {
  regionSlug?: string;
  /** Всегда daily — в URL не передаётся. */
  dealType: typeof IMPLICIT_DEAL_TYPE;
  propertyType?: string;
  citySlug?: string;
  nearMetro?: boolean;
  metroStationSlug?: string;
}

export function parseSegments(segments: string[] = []): ParsedSegments {
  let i = 0;
  const regionSlug = REGION_SLUGS.has(segments[i] ?? '') ? segments[i++] : undefined;

  let propertyType: string | undefined;
  if (segments[i] != null && segments[i]! in PROPERTY_TYPE_SLUG_TO_VALUE) {
    propertyType = PROPERTY_TYPE_SLUG_TO_VALUE[segments[i]!];
    i++;
  }

  let citySlug: string | undefined;
  let nearMetro: boolean | undefined;
  let metroStationSlug: string | undefined;

  if (i < segments.length) {
    if (segments[i] === 'vozle-metro') {
      nearMetro = true;
      i++;
    } else if (segments[i] === 'metro') {
      nearMetro = true;
      i++;
      if (segments[i]) {
        metroStationSlug = segments[i]!;
        i++;
      }
    } else {
      citySlug = segments[i];
      i++;
    }
  }

  return {
    regionSlug,
    dealType: IMPLICIT_DEAL_TYPE,
    propertyType,
    citySlug,
    nearMetro,
    metroStationSlug,
  };
}

export function isCatalogRoute(parsed: ParsedSegments): boolean {
  return parsed.propertyType !== undefined || parsed.nearMetro === true;
}

/** Фильтр метро — только квартиры в Минске (не дома, не другие области/города). */
export function isMetroCatalogContext(parsed: ParsedSegments): boolean {
  if (parsed.propertyType === 'house') {
    return false;
  }
  if (parsed.regionSlug) {
    return false;
  }
  if (parsed.citySlug && parsed.citySlug !== MINSK_CITY_SLUG) {
    return false;
  }
  return parsed.propertyType === 'apartment' || parsed.nearMetro === true;
}

export interface BuildCatalogUrlParams {
  region?: string;
  propertyType?: string;
  city?: string;
  nearMetro?: boolean;
  metroStation?: string;
}

/** Путь каталога без query (для canonical). */
export function buildCatalogCanonicalPath(parsed: ParsedSegments): string {
  return buildCatalogUrl({
    region: parsed.regionSlug,
    propertyType: parsed.propertyType,
    city: parsed.citySlug,
    nearMetro: parsed.nearMetro,
    metroStation: parsed.metroStationSlug,
  });
}

export function buildSegmentsCanonicalPath(segments: string[]): string {
  return `/${segments.join("/")}/`;
}

export function buildCatalogUrl(params: BuildCatalogUrlParams = {}): string {
  const parts: string[] = [];

  if (params.region && REGION_SLUGS.has(params.region)) {
    parts.push(params.region);
  }

  if (params.propertyType && params.propertyType in PROPERTY_TYPE_VALUE_TO_SLUG) {
    parts.push(PROPERTY_TYPE_VALUE_TO_SLUG[params.propertyType]);
  }

  if (params.nearMetro) {
    if (params.metroStation) {
      parts.push('metro');
      parts.push(params.metroStation);
    } else {
      parts.push('vozle-metro');
    }
  } else if (params.city) {
    parts.push(params.city);
  }

  return '/' + (parts.length > 0 ? parts.join('/') + '/' : '');
}

export function isPropertyId(segment?: string): boolean {
  if (!segment) return false;
  return /^\d+$/.test(segment);
}

/** Slug региона для URL объявления из адреса API. */
export function propertyUrlRegionSlug(regionName?: string, citySlug?: string): string | undefined {
  const fromRegion = regionNameToHeaderSlug(regionName);
  if (fromRegion === HEADER_REGION_MINSK_SLUG) {
    return undefined;
  }
  if (fromRegion) {
    return fromRegion;
  }
  if (citySlug && REGION_SLUGS.has(citySlug)) {
    return citySlug;
  }
  return undefined;
}

/** Страница объявления: …/kvartiry/123 или …/vitebsk/kvartiry/123 */
export function isPropertyDetailPath(pathname: string): boolean {
  const segments = pathname.split('/').filter(Boolean);
  if (segments.length < 2) return false;
  const last = segments[segments.length - 1];
  if (!isPropertyId(last)) return false;
  const catalogSegments = segments.slice(0, -1);
  const parsed = parseSegments(catalogSegments);
  return parsed.propertyType !== undefined;
}

export function buildPropertyUrl(
  propertyType: string | undefined,
  id: number,
  region?: string,
): string {
  const propertySlug = propertyType ? PROPERTY_TYPE_VALUE_TO_SLUG[propertyType] : undefined;
  const base = propertySlug ? `/${propertySlug}/${id}/` : `/${id}/`;
  if (!region || region === HEADER_REGION_MINSK_SLUG) {
    return base;
  }
  return withRegionalCatalogHref(base, region);
}

export function buildPropertyUrlFromRegionName(
  propertyType: string | undefined,
  id: number,
  regionName?: string,
  citySlug?: string,
): string {
  return buildPropertyUrl(propertyType, id, propertyUrlRegionSlug(regionName, citySlug));
}

export function buildPageTitle(
  parsed: ParsedSegments,
  cityName?: string,
  metroStationName?: string
): string {
  const parts: string[] = [];

  if (parsed.propertyType && parsed.propertyType in DAILY_DEAL_PAGE_TITLES) {
    parts.push(DAILY_DEAL_PAGE_TITLES[parsed.propertyType]);
  } else if (parsed.nearMetro) {
    parts.push('Посуточная аренда');
  } else {
    parts.push('Посуточная аренда');
  }

  const location = cityName
    ?? (metroStationName
      ? `метро ${metroStationName}`
      : parsed.metroStationSlug
      ? 'метро в Минске'
      : (parsed.nearMetro
        ? 'возле метро в Минске'
        : (parsed.regionSlug ? REGION_LABELS[parsed.regionSlug] : 'Минск и область')));

  if (location) {
    if (parsed.nearMetro) {
      parts.push(location);
    } else {
      parts.push(`— ${location}`);
    }
  }

  return parts.length > 0 ? parts.join(' ') : 'Посуточная аренда в Беларуси';
}
