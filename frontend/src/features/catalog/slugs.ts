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

/** Города с префиксом в URL (только квартиры): /pinsk/kvartiry/, /pinsk/kvartiry/62/ */
export const CITY_PREFIX_SLUG_LIST = [
  'orsha',
  'svetlogorsk',
  'smorgon',
  'molodechno',
  'baranovichi',
  'pinsk',
  'novopolotsk',
  'bobruysk',
  'zhlobin',
  'volkovysk',
] as const;

export const CITY_PREFIX_SLUGS: ReadonlySet<string> = new Set(CITY_PREFIX_SLUG_LIST);

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

/** H1 / meta: локация в предложном падеже (с предлогом «в»). */
const CATALOG_APARTMENT_LOCATION: Record<string, string> = {
  minsk: 'в Минске',
  brest: 'в Бресте',
  vitebsk: 'в Витебске',
  gomel: 'в Гомеле',
  grodno: 'в Гродно',
  mogilev: 'в Могилёве',
  'minsk-region': 'в Минской области',
  orsha: 'в Орше',
  svetlogorsk: 'в Светлогорске',
  smorgon: 'в Сморгони',
  molodechno: 'в Молодечно',
  baranovichi: 'в Барановичах',
  pinsk: 'в Пинске',
  novopolotsk: 'в Новополоцке',
  bobruysk: 'в Бобруйске',
  zhlobin: 'в Жлобине',
  volkovysk: 'в Волковыске',
};

const CATALOG_HOUSE_LOCATION: Record<string, string> = {
  minsk: 'в Минской области',
  brest: 'в Брестской области',
  vitebsk: 'в Витебской области',
  gomel: 'в Гомельской области',
  grodno: 'в Гродненской области',
  mogilev: 'в Могилёвской области',
};

/** H1 / meta для страниц каталога по типу жилья. */
export const DAILY_DEAL_PAGE_TITLES: Record<string, string> = {
  apartment: 'Квартиры на сутки',
  house: 'Дома и коттеджи на сутки',
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

  let prefixCitySlug: string | undefined;
  if (!regionSlug && CITY_PREFIX_SLUGS.has(segments[i] ?? '')) {
    prefixCitySlug = segments[i++];
  }

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

  if (!citySlug && prefixCitySlug) {
    citySlug = prefixCitySlug;
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
  return parsed.propertyType !== undefined;
}

/** Метро в URL — только /kvartiry/vozle-metro/ и /kvartiry/metro/{slug}/ (Минск). */
export function isValidMetroCatalogSegments(parsed: ParsedSegments): boolean {
  if (!parsed.nearMetro && !parsed.metroStationSlug) {
    return true;
  }
  if (parsed.propertyType !== 'apartment') {
    return false;
  }
  if (parsed.regionSlug) {
    return false;
  }
  if (parsed.citySlug != null && parsed.citySlug !== MINSK_CITY_SLUG) {
    return false;
  }
  return true;
}

/** Структура URL каталога: регион → тип → город / метро (без проверки slug в API). */
export function validateCatalogSegmentsStructure(segments: string[] = []): boolean {
  if (segments.length === 0) {
    return true;
  }

  let i = 0;

  if (REGION_SLUGS.has(segments[i] ?? '')) {
    i++;
  } else if (CITY_PREFIX_SLUGS.has(segments[i] ?? '')) {
    i++;
  }

  if (segments[i] != null && segments[i]! in PROPERTY_TYPE_SLUG_TO_VALUE) {
    i++;
  }

  if (i < segments.length) {
    if (segments[i] === 'vozle-metro') {
      i++;
    } else if (segments[i] === 'metro') {
      i++;
      if (segments[i]) i++;
      else return false;
    } else {
      i++;
    }
  }

  return i === segments.length;
}

/**
 * Допустимый путь catch-all: главная, каталог или карточка объявления.
 * Любой другой путь (в т.ч. `/preload/`) — ложь.
 */
export function validatePublicSegmentsStructure(segments: string[] = []): boolean {
  if (segments.length === 0) return true;

  const lastSegment = segments[segments.length - 1];
  if (isPropertyId(lastSegment)) {
    const catalogSegments = segments.slice(0, -1);
    if (!validateCatalogSegmentsStructure(catalogSegments)) return false;

    const parsed = parseSegments(catalogSegments);
    return parsed.propertyType !== undefined && isValidMetroCatalogSegments(parsed);
  }

  if (!validateCatalogSegmentsStructure(segments)) return false;

  const parsed = parseSegments(segments);
  return isCatalogRoute(parsed) && isValidMetroCatalogSegments(parsed);
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
  const isCityPrefix =
    params.city != null && CITY_PREFIX_SLUGS.has(params.city) && params.region == null;

  if (isCityPrefix) {
    parts.push(params.city!);
  } else if (params.region && REGION_SLUGS.has(params.region)) {
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
  } else if (params.city && !isCityPrefix) {
    parts.push(params.city);
  }

  return '/' + (parts.length > 0 ? parts.join('/') + '/' : '');
}

export function isPropertyId(segment?: string): boolean {
  if (!segment) return false;
  return /^\d+$/.test(segment);
}

/** Slug региона или города-префикса для URL объявления из адреса API. */
export function propertyUrlRegionSlug(
  regionName?: string,
  citySlug?: string,
  propertyType?: string,
): string | undefined {
  if (propertyType === 'apartment' && citySlug && CITY_PREFIX_SLUGS.has(citySlug)) {
    return citySlug;
  }

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

/** Каталог по адресу объявления: /pinsk/kvartiry/, /vitebsk/kvartiry/, /kvartiry/ */
export function buildCatalogUrlFromAddress(
  regionName?: string,
  citySlug?: string,
  propertyType?: string,
): string {
  const slug = propertyUrlRegionSlug(regionName, citySlug, propertyType);
  if (slug && CITY_PREFIX_SLUGS.has(slug)) {
    return buildCatalogUrl({ city: slug, propertyType });
  }
  if (slug && REGION_SLUGS.has(slug)) {
    return buildCatalogUrl({ region: slug, propertyType });
  }
  return buildCatalogUrl({ propertyType });
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
  return buildPropertyUrl(propertyType, id, propertyUrlRegionSlug(regionName, citySlug, propertyType));
}

function catalogLocationKey(parsed: ParsedSegments): string {
  return parsed.citySlug ?? parsed.regionSlug ?? MINSK_CITY_SLUG;
}

function resolveCatalogLocation(
  parsed: ParsedSegments,
  cityName?: string,
  metroStationName?: string,
): string {
  if (metroStationName) {
    return `у метро ${metroStationName} в Минске`;
  }
  if (parsed.metroStationSlug) {
    return 'у метро в Минске';
  }
  if (parsed.nearMetro) {
    return 'возле метро в Минске';
  }

  if (cityName) {
    return cityName.startsWith('в ') ? cityName : `в ${cityName}`;
  }

  const key = catalogLocationKey(parsed);
  const map =
    parsed.propertyType === 'house' ? CATALOG_HOUSE_LOCATION : CATALOG_APARTMENT_LOCATION;

  return map[key] ?? map[MINSK_CITY_SLUG];
}

export function buildPageTitle(
  parsed: ParsedSegments,
  cityName?: string,
  metroStationName?: string
): string {
  const typePart =
    parsed.propertyType && parsed.propertyType in DAILY_DEAL_PAGE_TITLES
      ? DAILY_DEAL_PAGE_TITLES[parsed.propertyType]
      : 'Посуточная аренда';

  const location = resolveCatalogLocation(parsed, cityName, metroStationName);
  if (!location) {
    return typePart || 'Посуточная аренда в Беларуси';
  }

  return `${typePart} ${location}`;
}

/** SEO-лендинг «возле метро»: /kvartiry/vozle-metro/ (без конкретной станции). */
export function isNearMetroLandingPage(parsed: ParsedSegments): boolean {
  return parsed.propertyType === 'apartment' && parsed.nearMetro === true && !parsed.metroStationSlug;
}

export const NEAR_METRO_CATALOG_INTRO =
  'Снимайте квартиры на сутки рядом со станциями минского метро — удобно для поездок по делам и отдыха в центре города. Все объявления напрямую от владельцев.';

function resolveApartmentCatalogMetaLocation(
  parsed: ParsedSegments,
  metroStationName?: string,
): string | null {
  if (parsed.propertyType !== 'apartment') {
    return null;
  }
  if (parsed.nearMetro || parsed.metroStationSlug || metroStationName) {
    return null;
  }

  return (
    CATALOG_APARTMENT_LOCATION[catalogLocationKey(parsed)] ??
    CATALOG_APARTMENT_LOCATION[MINSK_CITY_SLUG]
  );
}

function resolveHouseCatalogMetaLocation(parsed: ParsedSegments): string | null {
  if (parsed.propertyType !== 'house') {
    return null;
  }

  return (
    CATALOG_HOUSE_LOCATION[catalogLocationKey(parsed)] ??
    CATALOG_HOUSE_LOCATION[MINSK_CITY_SLUG]
  );
}

/** Meta title каталога (H1 остаётся в {@link buildPageTitle}). */
export function buildCatalogMetaTitle(
  parsed: ParsedSegments,
  metroStationName?: string,
): string | null {
  if (isNearMetroLandingPage(parsed)) {
    return 'Снять квартиру возле метро в Минске недорого. Посуточная аренда у метро в Минске на Посутки.by.';
  }

  const apartmentLocation = resolveApartmentCatalogMetaLocation(parsed, metroStationName);
  if (apartmentLocation) {
    return `Снять квартиру ${apartmentLocation} недорого. Посуточная аренда ${apartmentLocation}.`;
  }

  const houseLocation = resolveHouseCatalogMetaLocation(parsed);
  if (houseLocation) {
    return `Дома и коттеджи на сутки ${houseLocation}. Посуточная аренда домов от владельцев.`;
  }

  return null;
}

/** Meta description каталога (H1 остаётся в {@link buildPageTitle}). */
export function buildCatalogMetaDescription(
  parsed: ParsedSegments,
  metroStationName?: string,
): string | null {
  if (isNearMetroLandingPage(parsed)) {
    return 'Квартиры на сутки возле метро в Минске. Посуточная аренда квартир у станций минского метро на Posutki.by без посредников.';
  }

  const apartmentLocation = resolveApartmentCatalogMetaLocation(parsed, metroStationName);
  if (apartmentLocation) {
    return `Квартиры на сутки ${apartmentLocation}. Посуточная аренда квартир ${apartmentLocation} на Posutki.by без посредников.`;
  }

  const houseLocation = resolveHouseCatalogMetaLocation(parsed);
  if (houseLocation) {
    return `Снять дом на сутки ${houseLocation}. Посуточная аренда домов и коттеджей без посредников ${houseLocation} с ценами, описанием и фото на Posutki.by`;
  }

  return null;
}
