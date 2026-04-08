export const REGION_SLUGS: ReadonlySet<string> = new Set([
  'brest',
  'vitebsk',
  'gomel',
  'grodno',
  'mogilev',
] as const);

export const DEAL_TYPE_SLUG_TO_VALUE: Record<string, string> = {
  arenda: 'rent',
  prodazha: 'sale',
  posutochno: 'daily',
};

export const PROPERTY_TYPE_SLUG_TO_VALUE: Record<string, string> = {
  kvartiry: 'apartment',
  doma: 'house',
  komnaty: 'room',
  uchastki: 'land',
  garazhi: 'garage',
  mashinomesta: 'parking',
  dachi: 'dacha',
  ofisy: 'office',
  torgovye: 'retail',
  sklady: 'warehouse',
};

export const DEAL_TYPE_VALUE_TO_SLUG = Object.fromEntries(
  Object.entries(DEAL_TYPE_SLUG_TO_VALUE).map(([slug, val]) => [val, slug]),
);

export const PROPERTY_TYPE_VALUE_TO_SLUG = Object.fromEntries(
  Object.entries(PROPERTY_TYPE_SLUG_TO_VALUE).map(([slug, val]) => [val, slug]),
);

export const REGION_LABELS: Record<string, string> = {
  brest: 'Брест и область',
  vitebsk: 'Витебская область',
  gomel: 'Гомель и область',
  grodno: 'Гродно и область',
  mogilev: 'Могилев и область',
};

export const DEAL_TYPE_LABELS: Record<string, string> = {
  rent: 'Аренда',
  sale: 'Продажа',
  daily: 'Посуточно',
};

/** H1 / meta for daily deal + property type (replaces generic «Посуточно» + genitive plural). */
export const DAILY_DEAL_PAGE_TITLES: Record<string, string> = {
  apartment: 'Квартиры на сутки',
  house: 'Дома на сутки',
  dacha: 'Дачи на сутки',
};

export const PROPERTY_TYPE_LABELS: Record<string, string> = {
  apartment: 'квартир',
  house: 'домов',
  room: 'комнат',
  land: 'участков',
  garage: 'гаражей',
  parking: 'машиномест',
  dacha: 'дач',
  office: 'офисов',
  retail: 'торговых помещений',
  warehouse: 'складов',
};

export interface ParsedSegments {
  regionSlug?: string;
  dealType?: string;
  propertyType?: string;
  citySlug?: string;
  nearMetro?: boolean;
  metroStationSlug?: string;
}

export function parseSegments(segments: string[] = []): ParsedSegments {
  let i = 0;
  const regionSlug = REGION_SLUGS.has(segments[i]) ? segments[i++] : undefined;
  const dealType =
    segments[i] in DEAL_TYPE_SLUG_TO_VALUE
      ? DEAL_TYPE_SLUG_TO_VALUE[segments[i++]]
      : undefined;
  const propertyType =
    segments[i] in PROPERTY_TYPE_SLUG_TO_VALUE
      ? PROPERTY_TYPE_SLUG_TO_VALUE[segments[i++]]
      : undefined;

  let citySlug: string | undefined;
  let nearMetro: boolean | undefined;
  let metroStationSlug: string | undefined;

  if (segments[i] === 'vozle-metro') {
    nearMetro = true;
    i++;
  } else if (segments[i] === 'metro') {
    nearMetro = true;
    i++;
    if (segments[i]) {
      metroStationSlug = segments[i++];
    }
  } else {
    citySlug = segments[i] ?? undefined;
  }

  return { regionSlug, dealType, propertyType, citySlug, nearMetro, metroStationSlug };
}

export function isCatalogRoute(parsed: ParsedSegments): boolean {
  return parsed.dealType !== undefined;
}

interface BuildUrlParams {
  region?: string;
  dealType?: string;
  propertyType?: string;
  city?: string;
  nearMetro?: boolean;
  metroStation?: string;
}

export function buildCatalogUrl(params: BuildUrlParams = {}): string {
  const parts: string[] = [];

  if (params.region && REGION_SLUGS.has(params.region)) {
    parts.push(params.region);
  }

  if (params.dealType && params.dealType in DEAL_TYPE_VALUE_TO_SLUG) {
    parts.push(DEAL_TYPE_VALUE_TO_SLUG[params.dealType]);
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

/** True when pathname is a property listing URL (…/deal/type/id). */
export function isPropertyDetailPath(pathname: string): boolean {
  const segments = pathname.split('/').filter(Boolean);
  if (segments.length < 3) return false;
  const last = segments[segments.length - 1];
  if (!isPropertyId(last)) return false;
  const catalogSegments = segments.slice(0, -1);
  const parsed = parseSegments(catalogSegments);
  return parsed.dealType !== undefined && parsed.propertyType !== undefined;
}

export function buildPropertyUrl(
  dealType: string | undefined,
  propertyType: string | undefined,
  id: number,
): string {
  const dealSlug = dealType ? DEAL_TYPE_VALUE_TO_SLUG[dealType] : undefined;
  const propertySlug = propertyType ? PROPERTY_TYPE_VALUE_TO_SLUG[propertyType] : undefined;

  if (!dealSlug || !propertySlug) {
    return `/${id}/`;
  }

  return `/${dealSlug}/${propertySlug}/${id}/`;
}

export function buildPageTitle(
  parsed: ParsedSegments,
  cityName?: string,
  metroStationName?: string
): string {
  const parts: string[] = [];

  if (
    parsed.dealType === 'daily' &&
    parsed.propertyType &&
    parsed.propertyType in DAILY_DEAL_PAGE_TITLES
  ) {
    parts.push(DAILY_DEAL_PAGE_TITLES[parsed.propertyType]);
  } else {
    if (parsed.dealType) {
      parts.push(DEAL_TYPE_LABELS[parsed.dealType] ?? parsed.dealType);
    }

    if (parsed.propertyType) {
      parts.push(PROPERTY_TYPE_LABELS[parsed.propertyType] ?? parsed.propertyType);
    }
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

  return parts.length > 0 ? parts.join(' ') : 'Недвижимость в Беларуси';
}
