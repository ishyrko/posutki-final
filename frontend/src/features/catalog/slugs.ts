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

/** Города с префиксом в URL (только квартиры): /pinsk/kvartiry/, /pinsk/kvartiry/62/ — по алфавиту названий. */
export const CITY_PREFIX_SLUG_LIST = [
  'baranovichi',
  'bobruysk',
  'volkovysk',
  'zhlobin',
  'zhodino',
  'krichev',
  'logoysk',
  'molodechno',
  'nesvizh',
  'novolukoml',
  'novopolotsk',
  'orsha',
  'pinsk',
  'svetlogorsk',
  'smorgon',
] as const;

export const CITY_PREFIX_SLUGS: ReadonlySet<string> = new Set(CITY_PREFIX_SLUG_LIST);

/** Города с внутригородскими районами (зеркало backend CitiesWithDistricts::SLUGS). */
export const CITIES_WITH_DISTRICTS_SLUGS: ReadonlySet<string> = new Set([
  'minsk',
  'brest',
  'vitebsk',
  'gomel',
  'grodno',
  'mogilev',
  'bobruysk',
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

/** SEO path-лендинги по комнатности — только 1–3 комнаты (4+ только через фильтр `?rooms=`). */
export const ROOM_BUCKET_VALUES = [1, 2, 3] as const;
export type RoomBucket = (typeof ROOM_BUCKET_VALUES)[number];

export const FOUR_PLUS_ROOM_BUCKET = 4 as const;
export const DEPRECATED_ROOM_CATALOG_SLUG = '4-komnatnye';

export const ROOM_BUCKET_TO_SLUG: Record<RoomBucket, string> = {
  1: '1-komnatnye',
  2: '2-komnatnye',
  3: '3-komnatnye',
};

/** Slug → bucket для parse/validate (включая устаревший `/4-komnatnye/` → редирект). */
export const ROOM_PATH_SLUG_TO_BUCKET: Record<string, RoomBucket | typeof FOUR_PLUS_ROOM_BUCKET> = {
  ...Object.fromEntries(
    Object.entries(ROOM_BUCKET_TO_SLUG).map(([bucket, slug]) => [slug, Number(bucket) as RoomBucket]),
  ),
  [DEPRECATED_ROOM_CATALOG_SLUG]: FOUR_PLUS_ROOM_BUCKET,
};

export const ROOM_BUCKET_SLUGS: ReadonlySet<string> = new Set(Object.keys(ROOM_PATH_SLUG_TO_BUCKET));

export function isRoomSeoBucket(bucket: number | undefined): bucket is RoomBucket {
  return bucket === 1 || bucket === 2 || bucket === 3;
}

export function isDeprecatedFourPlusRoomCatalogPage(parsed: ParsedSegments): boolean {
  return parsed.roomsBucket === FOUR_PLUS_ROOM_BUCKET;
}

/** Города с посуточным каталогом квартир (зеркало backend CatalogApartmentCitySlugs). */
export const CATALOG_APARTMENT_CITY_SLUGS: readonly string[] = [
  MINSK_CITY_SLUG,
  ...REGION_SLUGS,
  ...CITY_PREFIX_SLUG_LIST,
] as const;

export const CATALOG_APARTMENT_CITY_SLUG_SET: ReadonlySet<string> = new Set(CATALOG_APARTMENT_CITY_SLUGS);

const ROOM_PAGE_TITLE: Record<RoomBucket, string> = {
  1: 'Однокомнатные квартиры на сутки',
  2: 'Двухкомнатные квартиры на сутки',
  3: 'Трёхкомнатные квартиры на сутки',
};

const ROOM_META_TITLE_ROOM: Record<RoomBucket, string> = {
  1: 'однокомнатную квартиру',
  2: 'двухкомнатную квартиру',
  3: 'трёхкомнатную квартиру',
};

const ROOM_META_TITLE_ROOM_PLURAL: Record<RoomBucket, string> = {
  1: '1-комнатных квартир',
  2: '2-комнатных квартир',
  3: '3-комнатных квартир',
};

const ROOM_SEO_HEADING_ROOM: Record<RoomBucket, string> = {
  1: 'однокомнатную квартиру',
  2: 'двухкомнатную квартиру',
  3: 'трёхкомнатную квартиру',
};

const ROOM_FAQ_HEADING_ROOM: Record<RoomBucket, string> = {
  1: 'однокомнатных квартир',
  2: 'двухкомнатных квартир',
  3: 'трёхкомнатных квартир',
};

const ROOM_BREADCRUMB_LABEL: Record<RoomBucket, string> = {
  1: 'Однокомнатные',
  2: 'Двухкомнатные',
  3: 'Трёхкомнатные',
};

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
  zhodino: 'в Жодино',
  nesvizh: 'в Несвиже',
  logoysk: 'в Логойске',
  baranovichi: 'в Барановичах',
  pinsk: 'в Пинске',
  novopolotsk: 'в Новополоцке',
  bobruysk: 'в Бобруйске',
  zhlobin: 'в Жлобине',
  volkovysk: 'в Волковыске',
  novolukoml: 'в Новолукомле',
  krichev: 'в Кричеве',
};

/** Именительный падеж города (баннер достопримечательности, карточки перелинковки). */
const CATALOG_CITY_NOMINATIVE: Record<string, string> = {
  minsk: 'Минск',
  brest: 'Брест',
  vitebsk: 'Витебск',
  gomel: 'Гомел',
  grodno: 'Гродно',
  mogilev: 'Могилёв',
  'minsk-region': 'Минская область',
  orsha: 'Орша',
  svetlogorsk: 'Светлогорск',
  smorgon: 'Сморгонь',
  molodechno: 'Молодечно',
  zhodino: 'Жодино',
  nesvizh: 'Несвиж',
  logoysk: 'Логойск',
  baranovichi: 'Барановичи',
  pinsk: 'Пинск',
  novopolotsk: 'Новополоцк',
  bobruysk: 'Бобруйск',
  zhlobin: 'Жлобин',
  volkovysk: 'Волковыск',
  novolukoml: 'Новолукомль',
  krichev: 'Кричев',
};

/** Родительный падеж города для фраз «в … районе Минска» (города с адм. районами). */
const CATALOG_CITY_GENITIVE: Record<string, string> = {
  minsk: 'Минска',
  brest: 'Бреста',
  vitebsk: 'Витебска',
  gomel: 'Гомеля',
  grodno: 'Гродно',
  mogilev: 'Могилёва',
  bobruysk: 'Бобруйска',
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

/** Именительный падеж города для подписи в баннере достопримечательности. */
export function resolveCatalogCityNominative(citySlug: string): string {
  return CATALOG_CITY_NOMINATIVE[citySlug] ?? CATALOG_CITY_NOMINATIVE[MINSK_CITY_SLUG];
}

export interface ParsedSegments {
  regionSlug?: string;
  /** Всегда daily — в URL не передаётся. */
  dealType: typeof IMPLICIT_DEAL_TYPE;
  propertyType?: string;
  citySlug?: string;
  nearMetro?: boolean;
  metroStationSlug?: string;
  cityDistrictSlug?: string;
  microdistrictSlug?: string;
  residentialComplexSlug?: string;
  landmarkSlug?: string;
  /** Path-фасет комнатности: 1–3 точное число, 4 — «четыре и более». */
  roomsBucket?: RoomBucket | typeof FOUR_PLUS_ROOM_BUCKET;
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
  let cityDistrictSlug: string | undefined;
  let microdistrictSlug: string | undefined;
  let residentialComplexSlug: string | undefined;
  let landmarkSlug: string | undefined;
  let roomsBucket: RoomBucket | undefined;

  if (i < segments.length) {
    const roomSlug = segments[i];
    if (roomSlug != null && ROOM_PATH_SLUG_TO_BUCKET[roomSlug] != null) {
      roomsBucket = ROOM_PATH_SLUG_TO_BUCKET[roomSlug];
      i++;
    } else if (segments[i] === 'vozle-metro') {
      nearMetro = true;
      i++;
    } else if (segments[i] === 'metro') {
      nearMetro = true;
      i++;
      if (segments[i]) {
        metroStationSlug = segments[i]!;
        i++;
      }
    } else if (segments[i] === 'raion') {
      i++;
      if (segments[i]) {
        cityDistrictSlug = segments[i]!;
        i++;
      }
    } else if (segments[i] === 'mikroraion') {
      i++;
      if (segments[i]) {
        microdistrictSlug = segments[i]!;
        i++;
      }
    } else if (segments[i] === 'zhiloy-kompleks') {
      i++;
      if (segments[i]) {
        residentialComplexSlug = segments[i]!;
        i++;
      }
    } else if (segments[i] === 'vozle') {
      i++;
      if (segments[i]) {
        landmarkSlug = segments[i]!;
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
    cityDistrictSlug,
    microdistrictSlug,
    residentialComplexSlug,
    landmarkSlug,
    roomsBucket,
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
  if (parsed.roomsBucket) {
    return false;
  }
  if (parsed.cityDistrictSlug) {
    return false;
  }
  if (parsed.microdistrictSlug || parsed.residentialComplexSlug) {
    return false;
  }
  if (parsed.landmarkSlug) {
    return false;
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

/** Район в URL — только квартиры в городах с административными районами. */
export function isValidDistrictCatalogSegments(parsed: ParsedSegments): boolean {
  if (!parsed.cityDistrictSlug) {
    return true;
  }
  if (parsed.roomsBucket) {
    return false;
  }
  if (parsed.nearMetro || parsed.metroStationSlug || parsed.landmarkSlug) {
    return false;
  }
  if (parsed.microdistrictSlug || parsed.residentialComplexSlug) {
    return false;
  }
  if (parsed.propertyType !== 'apartment') {
    return false;
  }

  return CITIES_WITH_DISTRICTS_SLUGS.has(resolveCatalogCitySlug(parsed));
}

/** Микрорайон в URL — только квартиры, без метро, района, ЖК и достопримечательности. */
export function isValidMicrodistrictCatalogSegments(parsed: ParsedSegments): boolean {
  if (!parsed.microdistrictSlug) {
    return true;
  }
  if (parsed.roomsBucket) {
    return false;
  }
  if (
    parsed.nearMetro ||
    parsed.metroStationSlug ||
    parsed.cityDistrictSlug ||
    parsed.residentialComplexSlug ||
    parsed.landmarkSlug
  ) {
    return false;
  }
  if (parsed.propertyType !== 'apartment') {
    return false;
  }

  return true;
}

/** Жилой комплекс в URL — только квартиры, без метро, района, микрорайона и достопримечательности. */
export function isValidResidentialComplexCatalogSegments(parsed: ParsedSegments): boolean {
  if (!parsed.residentialComplexSlug) {
    return true;
  }
  if (parsed.roomsBucket) {
    return false;
  }
  if (
    parsed.nearMetro ||
    parsed.metroStationSlug ||
    parsed.cityDistrictSlug ||
    parsed.microdistrictSlug ||
    parsed.landmarkSlug
  ) {
    return false;
  }
  if (parsed.propertyType !== 'apartment') {
    return false;
  }

  return true;
}

/** Комнатность в URL — только квартиры, без других геофасетов. */
export function isValidRoomsCatalogSegments(parsed: ParsedSegments): boolean {
  if (!parsed.roomsBucket) {
    return true;
  }
  if (
    parsed.nearMetro ||
    parsed.metroStationSlug ||
    parsed.cityDistrictSlug ||
    parsed.microdistrictSlug ||
    parsed.residentialComplexSlug ||
    parsed.landmarkSlug
  ) {
    return false;
  }
  if (parsed.propertyType !== 'apartment') {
    return false;
  }

  if (parsed.roomsBucket === FOUR_PLUS_ROOM_BUCKET) {
    return false;
  }
  if (!isRoomSeoBucket(parsed.roomsBucket)) {
    return true;
  }

  return CATALOG_APARTMENT_CITY_SLUG_SET.has(resolveCatalogCitySlug(parsed));
}

/** Достопримечательность в URL — только квартиры, без метро и района. */
export function isValidLandmarkCatalogSegments(parsed: ParsedSegments): boolean {
  if (!parsed.landmarkSlug) {
    return true;
  }
  if (
    parsed.nearMetro ||
    parsed.metroStationSlug ||
    parsed.cityDistrictSlug ||
    parsed.microdistrictSlug ||
    parsed.residentialComplexSlug ||
    parsed.roomsBucket
  ) {
    return false;
  }
  if (parsed.propertyType !== 'apartment') {
    return false;
  }

  return true;
}

export function resolveCatalogCitySlug(parsed: ParsedSegments): string {
  return parsed.citySlug ?? parsed.regionSlug ?? MINSK_CITY_SLUG;
}

/** Базовая страница каталога квартир города (без метро и районов). */
export function isBaseCityApartmentCatalogPage(parsed: ParsedSegments): boolean {
  return (
    parsed.propertyType === 'apartment' &&
    !parsed.nearMetro &&
    !parsed.metroStationSlug &&
    !parsed.cityDistrictSlug &&
    !parsed.microdistrictSlug &&
    !parsed.residentialComplexSlug &&
    !parsed.landmarkSlug &&
    !parsed.roomsBucket
  );
}

/** Страница каталога квартир по числу комнат (path-лендинг, только 1–3). */
export function isRoomCatalogPage(parsed: ParsedSegments): boolean {
  return parsed.propertyType === 'apartment' && isRoomSeoBucket(parsed.roomsBucket);
}

export function buildRoomCatalogUrl(citySlug: string, roomsBucket: RoomBucket): string {
  if (citySlug === MINSK_CITY_SLUG) {
    return buildCatalogUrl({ propertyType: 'apartment', rooms: roomsBucket });
  }
  if (REGION_SLUGS.has(citySlug)) {
    return buildCatalogUrl({ region: citySlug, propertyType: 'apartment', rooms: roomsBucket });
  }
  if (CITY_PREFIX_SLUGS.has(citySlug)) {
    return buildCatalogUrl({ city: citySlug, propertyType: 'apartment', rooms: roomsBucket });
  }
  return buildCatalogUrl({ propertyType: 'apartment', city: citySlug, rooms: roomsBucket });
}

export function buildCatalogRoomSeoHeading(citySlug: string, roomsBucket: RoomBucket): string {
  const location =
    CATALOG_APARTMENT_LOCATION[citySlug] ?? CATALOG_APARTMENT_LOCATION[MINSK_CITY_SLUG];
  return `Снять ${ROOM_SEO_HEADING_ROOM[roomsBucket]} ${location} посуточно`;
}

export function buildCatalogRoomFaqHeading(citySlug: string, roomsBucket: RoomBucket): string {
  const location =
    CATALOG_APARTMENT_LOCATION[citySlug] ?? CATALOG_APARTMENT_LOCATION[MINSK_CITY_SLUG];
  return `Частые вопросы о посуточной аренде ${ROOM_FAQ_HEADING_ROOM[roomsBucket]} ${location}`;
}

export function buildRoomBreadcrumbLabel(roomsBucket: RoomBucket): string {
  return ROOM_BREADCRUMB_LABEL[roomsBucket];
}

export function resolveCatalogUrlParamsFromCitySlug(citySlug: string): Pick<
  BuildCatalogUrlParams,
  'region' | 'city'
> {
  if (citySlug === MINSK_CITY_SLUG) {
    return {};
  }
  if (REGION_SLUGS.has(citySlug)) {
    return { region: citySlug };
  }
  if (CITY_PREFIX_SLUGS.has(citySlug)) {
    return { city: citySlug };
  }
  return { city: citySlug };
}

/** Заголовок SEO-блока под каталогом квартир города. */
export function buildCatalogCitySeoHeading(citySlug: string): string {
  const location =
    CATALOG_APARTMENT_LOCATION[citySlug] ?? CATALOG_APARTMENT_LOCATION[MINSK_CITY_SLUG];
  return `Снять квартиру ${location} посуточно`;
}

/** Заголовок FAQ под каталогом квартир (город, район, микрорайон, ЖК). */
export function buildCatalogApartmentFaqHeading(location: string): string {
  return `Частые вопросы о посуточной аренде квартир ${location}`;
}

/** Заголовок FAQ под базовым каталогом квартир города. */
export function buildCatalogCityFaqHeading(citySlug: string): string {
  const location =
    CATALOG_APARTMENT_LOCATION[citySlug] ?? CATALOG_APARTMENT_LOCATION[MINSK_CITY_SLUG];
  return buildCatalogApartmentFaqHeading(location);
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
    const roomSlug = segments[i];
    if (roomSlug != null && ROOM_PATH_SLUG_TO_BUCKET[roomSlug] != null) {
      i++;
    } else if (segments[i] === 'vozle-metro') {
      i++;
    } else if (segments[i] === 'metro') {
      i++;
      if (segments[i]) i++;
      else return false;
    } else if (segments[i] === 'raion') {
      i++;
      if (segments[i]) i++;
      else return false;
    } else if (segments[i] === 'mikroraion') {
      i++;
      if (segments[i]) i++;
      else return false;
    } else if (segments[i] === 'zhiloy-kompleks') {
      i++;
      if (segments[i]) i++;
      else return false;
    } else if (segments[i] === 'vozle') {
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
    return (
      parsed.propertyType !== undefined &&
      isValidMetroCatalogSegments(parsed) &&
      isValidDistrictCatalogSegments(parsed) &&
      isValidMicrodistrictCatalogSegments(parsed) &&
      isValidResidentialComplexCatalogSegments(parsed) &&
      isValidLandmarkCatalogSegments(parsed) &&
      isValidRoomsCatalogSegments(parsed)
    );
  }

  if (!validateCatalogSegmentsStructure(segments)) return false;

  const parsed = parseSegments(segments);
  return (
    isCatalogRoute(parsed) &&
    isValidMetroCatalogSegments(parsed) &&
    isValidDistrictCatalogSegments(parsed) &&
    isValidMicrodistrictCatalogSegments(parsed) &&
    isValidResidentialComplexCatalogSegments(parsed) &&
    isValidLandmarkCatalogSegments(parsed) &&
    isValidRoomsCatalogSegments(parsed)
  );
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

/** Район в каталоге — только квартиры в городах с административными районами (не на страницах метро). */
export function isDistrictCatalogContext(parsed: ParsedSegments): boolean {
  if (parsed.propertyType !== 'apartment') {
    return false;
  }
  if (parsed.nearMetro || parsed.metroStationSlug || parsed.landmarkSlug) {
    return false;
  }
  if (parsed.microdistrictSlug || parsed.residentialComplexSlug) {
    return false;
  }

  return CITIES_WITH_DISTRICTS_SLUGS.has(resolveCatalogCitySlug(parsed));
}

/** Микрорайон в каталоге — только квартиры, без других геофильтров в URL. */
export function isMicrodistrictCatalogContext(parsed: ParsedSegments): boolean {
  if (parsed.propertyType !== 'apartment') {
    return false;
  }
  if (
    parsed.nearMetro ||
    parsed.metroStationSlug ||
    parsed.cityDistrictSlug ||
    parsed.residentialComplexSlug ||
    parsed.landmarkSlug
  ) {
    return false;
  }

  return true;
}

/** Жилой комплекс в каталоге — только квартиры, без других геофильтров в URL. */
export function isResidentialComplexCatalogContext(parsed: ParsedSegments): boolean {
  if (parsed.propertyType !== 'apartment') {
    return false;
  }
  if (
    parsed.nearMetro ||
    parsed.metroStationSlug ||
    parsed.cityDistrictSlug ||
    parsed.microdistrictSlug ||
    parsed.landmarkSlug
  ) {
    return false;
  }

  return true;
}

/** Достопримечательность в каталоге — только квартиры, без метро и района. */
export function isLandmarkCatalogContext(parsed: ParsedSegments): boolean {
  if (parsed.propertyType !== 'apartment') {
    return false;
  }
  if (
    parsed.nearMetro ||
    parsed.metroStationSlug ||
    parsed.cityDistrictSlug ||
    parsed.microdistrictSlug ||
    parsed.residentialComplexSlug
  ) {
    return false;
  }

  return true;
}

export interface BuildCatalogUrlParams {
  region?: string;
  propertyType?: string;
  city?: string;
  nearMetro?: boolean;
  metroStation?: string;
  cityDistrict?: string;
  microdistrict?: string;
  residentialComplex?: string;
  landmark?: string;
  rooms?: RoomBucket;
}

/** Путь каталога без query (для canonical). */
export function buildCatalogCanonicalPath(parsed: ParsedSegments): string {
  return buildCatalogUrl({
    region: parsed.regionSlug,
    propertyType: parsed.propertyType,
    city: parsed.citySlug,
    nearMetro: parsed.nearMetro,
    metroStation: parsed.metroStationSlug,
    cityDistrict: parsed.cityDistrictSlug,
    microdistrict: parsed.microdistrictSlug,
    residentialComplex: parsed.residentialComplexSlug,
    landmark: parsed.landmarkSlug,
    rooms: parsed.roomsBucket,
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

  if (params.rooms != null && params.rooms in ROOM_BUCKET_TO_SLUG) {
    parts.push(ROOM_BUCKET_TO_SLUG[params.rooms]);
  } else if (params.cityDistrict) {
    parts.push('raion');
    parts.push(params.cityDistrict);
  } else if (params.microdistrict) {
    parts.push('mikroraion');
    parts.push(params.microdistrict);
  } else if (params.residentialComplex) {
    parts.push('zhiloy-kompleks');
    parts.push(params.residentialComplex);
  } else if (params.landmark) {
    parts.push('vozle');
    parts.push(params.landmark);
  } else if (params.nearMetro) {
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

/** Каталог квартир возле достопримечательности (как в sitemap). */
export function buildLandmarkCatalogUrl(citySlug: string, landmarkSlug: string): string {
  const isCityPrefix = CITY_PREFIX_SLUGS.has(citySlug);
  const isRegion = REGION_SLUGS.has(citySlug);

  return buildCatalogUrl({
    region: !isCityPrefix && isRegion ? citySlug : undefined,
    city: isCityPrefix ? citySlug : undefined,
    propertyType: "apartment",
    landmark: landmarkSlug,
  });
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

/** Каталог квартир по району из адреса объявления; только для 7 городов с районами. */
export function buildDistrictCatalogUrlFromAddress(
  regionName?: string,
  citySlug?: string,
  cityDistrictSlug?: string,
): string | undefined {
  if (!cityDistrictSlug || !citySlug || !CITIES_WITH_DISTRICTS_SLUGS.has(citySlug)) {
    return undefined;
  }

  if (CITY_PREFIX_SLUGS.has(citySlug)) {
    return buildCatalogUrl({
      city: citySlug,
      propertyType: 'apartment',
      cityDistrict: cityDistrictSlug,
    });
  }

  const region = propertyUrlRegionSlug(regionName, citySlug, 'apartment');
  if (region && REGION_SLUGS.has(region)) {
    return buildCatalogUrl({
      region,
      propertyType: 'apartment',
      cityDistrict: cityDistrictSlug,
    });
  }

  if (citySlug === MINSK_CITY_SLUG) {
    return buildCatalogUrl({
      propertyType: 'apartment',
      cityDistrict: cityDistrictSlug,
    });
  }

  return undefined;
}

function buildPlaceCatalogUrlFromAddress(
  regionName: string | undefined,
  citySlug: string | undefined,
  placeSlug: string | undefined,
  placeKind: 'microdistrict' | 'residentialComplex',
): string | undefined {
  if (!placeSlug || !citySlug) {
    return undefined;
  }

  const placeParam =
    placeKind === 'microdistrict'
      ? { microdistrict: placeSlug }
      : { residentialComplex: placeSlug };

  if (CITY_PREFIX_SLUGS.has(citySlug)) {
    return buildCatalogUrl({
      city: citySlug,
      propertyType: 'apartment',
      ...placeParam,
    });
  }

  const region = propertyUrlRegionSlug(regionName, citySlug, 'apartment');
  if (region && REGION_SLUGS.has(region)) {
    return buildCatalogUrl({
      region,
      propertyType: 'apartment',
      ...placeParam,
    });
  }

  if (citySlug === MINSK_CITY_SLUG) {
    return buildCatalogUrl({
      propertyType: 'apartment',
      ...placeParam,
    });
  }

  return buildCatalogUrl({
    propertyType: 'apartment',
    city: citySlug,
    ...placeParam,
  });
}

/** Каталог квартир по микрорайону из адреса объявления. */
export function buildMicrodistrictCatalogUrlFromAddress(
  regionName?: string,
  citySlug?: string,
  microdistrictSlug?: string,
): string | undefined {
  return buildPlaceCatalogUrlFromAddress(regionName, citySlug, microdistrictSlug, 'microdistrict');
}

/** Каталог квартир по жилому комплексу из адреса объявления. */
export function buildResidentialComplexCatalogUrlFromAddress(
  regionName?: string,
  citySlug?: string,
  residentialComplexSlug?: string,
): string | undefined {
  return buildPlaceCatalogUrlFromAddress(
    regionName,
    citySlug,
    residentialComplexSlug,
    'residentialComplex',
  );
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

/** Убрать административный суффикс «район» (и склонения) — как backend CityDistrictSlugGenerator. */
export function stripDistrictSuffix(name: string): string {
  // В JS `\b` не считает кириллицу word-char — имитируем Unicode-границу слова.
  const stripped = name.replace(
    /(^|[^\p{L}\p{N}_])район(?:а|е|ом|у)?(?=$|[^\p{L}\p{N}_])/giu,
    '$1',
  );
  return stripped.replace(/\s+/gu, ' ').trim();
}

/** Муж. прилагательное → предложный падеж (Фрунзенский → Фрунзенском, Заводской → Заводском). */
export function toPrepositionalMasculineAdjective(stem: string): string {
  if (/ский$/iu.test(stem)) return stem.replace(/ский$/iu, 'ском');
  if (/цкий$/iu.test(stem)) return stem.replace(/цкий$/iu, 'цком');
  if (/ный$/iu.test(stem)) return stem.replace(/ный$/iu, 'ном');
  if (/ной$/iu.test(stem)) return stem.replace(/ной$/iu, 'ном');
  if (/овый$/iu.test(stem)) return stem.replace(/овый$/iu, 'овом');
  if (/евый$/iu.test(stem)) return stem.replace(/евый$/iu, 'евом');
  if (/ой$/iu.test(stem)) return stem.replace(/ой$/iu, 'ом');
  if (/ий$/iu.test(stem)) return stem.replace(/ий$/iu, 'ем');
  if (/ый$/iu.test(stem)) return stem.replace(/ый$/iu, 'ом');
  return stem;
}

/** «в» / «во» перед прилагательным района (во Фрунзенском, в Центральном). */
function districtLocationPreposition(adjective: string): 'в' | 'во' {
  return /^[вф][^аеёиоуыэюя]/iu.test(adjective) ? 'во' : 'в';
}

/**
 * Локация каталога по району: «во Фрунзенском районе Минска».
 * Имя из справочника может быть «Фрунзенский район» или уже без суффикса.
 */
export function formatCityDistrictCatalogLocation(
  cityDistrictName: string,
  citySlug: string,
): string {
  const stem = stripDistrictSuffix(cityDistrictName) || cityDistrictName.trim();
  const adjective = toPrepositionalMasculineAdjective(stem);
  const prep = districtLocationPreposition(adjective);
  const cityGenitive =
    CATALOG_CITY_GENITIVE[citySlug] ?? CATALOG_CITY_GENITIVE[MINSK_CITY_SLUG];
  return `${prep} ${adjective} районе ${cityGenitive}`;
}

/** «в мкр-не Уручье» — name в именительном падеже из API, без города. */
export function formatMicrodistrictCatalogPlace(name: string): string {
  const microdistrict = name.trim();
  return microdistrict ? `в мкр-не ${microdistrict}` : microdistrict;
}

/** «в мкр-не Уручье в Минске» — name в именительном падеже из API. */
export function formatMicrodistrictCatalogLocation(
  name: string,
  citySlug: string,
): string {
  const place = formatMicrodistrictCatalogPlace(name);
  if (!place) {
    return place;
  }
  const cityLocation =
    CATALOG_APARTMENT_LOCATION[citySlug] ?? CATALOG_APARTMENT_LOCATION[MINSK_CITY_SLUG];
  return `${place} ${cityLocation}`;
}

/** «в Минск-Мире» — namePrepositional из API без предлога и без города. */
export function formatResidentialComplexCatalogPlace(namePrepositional: string): string {
  const place = namePrepositional.trim();
  if (!place) {
    return place;
  }
  const prep = districtLocationPreposition(place);
  return `${prep} ${place}`;
}

/** «в Минск-Мире в Минске» — namePrepositional из API без предлога. */
export function formatResidentialComplexCatalogLocation(
  namePrepositional: string,
  citySlug: string,
): string {
  const place = formatResidentialComplexCatalogPlace(namePrepositional);
  if (!place) {
    return place;
  }
  const cityLocation =
    CATALOG_APARTMENT_LOCATION[citySlug] ?? CATALOG_APARTMENT_LOCATION[MINSK_CITY_SLUG];
  return `${place} ${cityLocation}`;
}

function resolveCatalogLocation(
  parsed: ParsedSegments,
  cityName?: string,
  metroStationName?: string,
  cityDistrictName?: string,
  landmarkPhrase?: string,
  microdistrictName?: string,
  residentialComplexNamePrepositional?: string,
): string {
  if (landmarkPhrase) {
    return landmarkPhrase.startsWith('возле ') || landmarkPhrase.startsWith('рядом ')
      ? landmarkPhrase
      : `возле ${landmarkPhrase}`;
  }
  if (metroStationName) {
    return `у метро ${metroStationName} в Минске`;
  }
  if (parsed.metroStationSlug) {
    return 'у метро в Минске';
  }
  if (parsed.nearMetro) {
    return 'возле метро в Минске';
  }

  const key = catalogLocationKey(parsed);

  if (residentialComplexNamePrepositional) {
    return formatResidentialComplexCatalogLocation(residentialComplexNamePrepositional, key);
  }
  if (microdistrictName) {
    return formatMicrodistrictCatalogLocation(microdistrictName, key);
  }

  if (cityDistrictName) {
    return formatCityDistrictCatalogLocation(cityDistrictName, key);
  }

  const map =
    parsed.propertyType === 'house' ? CATALOG_HOUSE_LOCATION : CATALOG_APARTMENT_LOCATION;
  return cityName
    ? (cityName.startsWith('в ') ? cityName : `в ${cityName}`)
    : (map[key] ?? map[MINSK_CITY_SLUG]);
}

export function buildPageTitle(
  parsed: ParsedSegments,
  cityName?: string,
  metroStationName?: string,
  cityDistrictName?: string,
  landmarkPhrase?: string,
  microdistrictName?: string,
  residentialComplexNamePrepositional?: string,
): string {
  if (isRoomSeoBucket(parsed.roomsBucket) && parsed.propertyType === 'apartment') {
    const location = resolveCatalogLocation(
      parsed,
      cityName,
      metroStationName,
      cityDistrictName,
      landmarkPhrase,
      microdistrictName,
      residentialComplexNamePrepositional,
    );
    const typePart = ROOM_PAGE_TITLE[parsed.roomsBucket];
    return location ? `${typePart} ${location}` : typePart;
  }

  const typePart =
    parsed.propertyType && parsed.propertyType in DAILY_DEAL_PAGE_TITLES
      ? DAILY_DEAL_PAGE_TITLES[parsed.propertyType]
      : 'Посуточная аренда';

  const location = resolveCatalogLocation(
    parsed,
    cityName,
    metroStationName,
    cityDistrictName,
    landmarkPhrase,
    microdistrictName,
    residentialComplexNamePrepositional,
  );
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
  cityDistrictName?: string,
  landmarkPhrase?: string,
  microdistrictName?: string,
  residentialComplexNamePrepositional?: string,
): string | null {
  if (parsed.propertyType !== 'apartment') {
    return null;
  }
  if (parsed.nearMetro || parsed.metroStationSlug || metroStationName) {
    return null;
  }

  if (landmarkPhrase) {
    return landmarkPhrase.startsWith('возле ') || landmarkPhrase.startsWith('рядом ')
      ? landmarkPhrase
      : `возле ${landmarkPhrase}`;
  }

  const key = catalogLocationKey(parsed);

  if (residentialComplexNamePrepositional) {
    return formatResidentialComplexCatalogLocation(residentialComplexNamePrepositional, key);
  }
  if (microdistrictName) {
    return formatMicrodistrictCatalogLocation(microdistrictName, key);
  }

  if (cityDistrictName) {
    return formatCityDistrictCatalogLocation(cityDistrictName, key);
  }

  return (
    CATALOG_APARTMENT_LOCATION[key] ?? CATALOG_APARTMENT_LOCATION[MINSK_CITY_SLUG]
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
  cityDistrictName?: string,
  landmarkPhrase?: string,
  microdistrictName?: string,
  residentialComplexNamePrepositional?: string,
): string | null {
  if (isNearMetroLandingPage(parsed)) {
    return 'Снять квартиру на сутки возле метро в Минске недорого. Посуточная аренда у метро в Минске на Посутки.by.';
  }

  if (isRoomSeoBucket(parsed.roomsBucket) && parsed.propertyType === 'apartment') {
    const location =
      CATALOG_APARTMENT_LOCATION[catalogLocationKey(parsed)] ??
      CATALOG_APARTMENT_LOCATION[MINSK_CITY_SLUG];
    const bucket = parsed.roomsBucket;
    return `Снять ${ROOM_META_TITLE_ROOM[bucket]} на сутки ${location} недорого. Посуточная аренда ${ROOM_META_TITLE_ROOM_PLURAL[bucket]} ${location}.`;
  }

  const apartmentLocation = resolveApartmentCatalogMetaLocation(
    parsed,
    metroStationName,
    cityDistrictName,
    landmarkPhrase,
    microdistrictName,
    residentialComplexNamePrepositional,
  );
  if (apartmentLocation) {
    if (landmarkPhrase) {
      const cityLocation =
        CATALOG_APARTMENT_LOCATION[catalogLocationKey(parsed)] ??
        CATALOG_APARTMENT_LOCATION[MINSK_CITY_SLUG];
      return `Снять квартиру на сутки ${apartmentLocation} ${cityLocation}. Посуточная аренда ${apartmentLocation}.`;
    }

    const priceHint =
      parsed.microdistrictSlug || parsed.residentialComplexSlug ? '' : ' недорого';
    return `Снять квартиру на сутки ${apartmentLocation}${priceHint}. Посуточная аренда ${apartmentLocation}.`;
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
  cityDistrictName?: string,
  landmarkPhrase?: string,
  microdistrictName?: string,
  residentialComplexNamePrepositional?: string,
): string | null {
  if (isNearMetroLandingPage(parsed)) {
    return 'Квартиры на сутки возле метро в Минске. Посуточная аренда квартир у станций минского метро на Posutki.by без посредников.';
  }

  if (isRoomSeoBucket(parsed.roomsBucket) && parsed.propertyType === 'apartment') {
    const location =
      CATALOG_APARTMENT_LOCATION[catalogLocationKey(parsed)] ??
      CATALOG_APARTMENT_LOCATION[MINSK_CITY_SLUG];
    const bucket = parsed.roomsBucket;
    return `${ROOM_PAGE_TITLE[bucket]} ${location}. Посуточная аренда ${ROOM_META_TITLE_ROOM_PLURAL[bucket]} ${location} на Posutki.by без посредников.`;
  }

  const apartmentLocation = resolveApartmentCatalogMetaLocation(
    parsed,
    metroStationName,
    cityDistrictName,
    landmarkPhrase,
    microdistrictName,
    residentialComplexNamePrepositional,
  );
  if (apartmentLocation) {
    const cityLocation =
      CATALOG_APARTMENT_LOCATION[catalogLocationKey(parsed)] ??
      CATALOG_APARTMENT_LOCATION[MINSK_CITY_SLUG];

    if (landmarkPhrase) {
      return `Квартиры на сутки ${apartmentLocation} ${cityLocation}. Посуточная аренда квартир ${apartmentLocation} на Posutki.by без посредников.`;
    }

    if (parsed.microdistrictSlug || parsed.residentialComplexSlug) {
      const placeLocation = residentialComplexNamePrepositional
        ? formatResidentialComplexCatalogPlace(residentialComplexNamePrepositional)
        : microdistrictName
          ? formatMicrodistrictCatalogPlace(microdistrictName)
          : null;
      if (placeLocation) {
        return `Квартиры на сутки ${placeLocation} ${cityLocation}. Посуточная аренда квартир ${placeLocation} ${cityLocation} на Posutki.by без посредников.`;
      }
    }

    return `Квартиры на сутки ${apartmentLocation}. Посуточная аренда квартир ${apartmentLocation} на Posutki.by без посредников.`;
  }

  const houseLocation = resolveHouseCatalogMetaLocation(parsed);
  if (houseLocation) {
    return `Снять дом на сутки ${houseLocation}. Посуточная аренда домов и коттеджей без посредников ${houseLocation} с ценами, описанием и фото на Posutki.by`;
  }

  return null;
}
