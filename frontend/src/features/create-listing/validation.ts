export const TITLE_MIN_LENGTH = 10;
export const TITLE_MAX_LENGTH = 200;
export const DESCRIPTION_MIN_LENGTH = 50;
export const DESCRIPTION_MAX_LENGTH = 5000;
export const AREA_MIN = 1;
export const AREA_MAX = 10000;
export const ROOMS_MIN = 1;
export const ROOMS_MAX = 50;
export const DAILY_BEDS_MAX = 50;
/** Максимум гостей для посуточной аренды (главная, подача, редактирование, API). */
export const MAX_DAILY_GUESTS = 20;
export const BATHROOMS_MIN = 0;
export const BATHROOMS_MAX = 10;
export const FLOOR_MIN = -5;
export const FLOOR_MAX = 200;
export const TOTAL_FLOORS_MIN = 1;
export const TOTAL_FLOORS_MAX = 200;
export const YEAR_BUILT_MIN = 1000;
export const YEAR_BUILT_MAX = 2050;
/** Минимум фотографий при подаче и редактировании (согласовано с API). */
export const MIN_PHOTOS = 3;
export const MAX_PHOTOS = 20;
export const ACCEPTED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'] as const;
export const MAX_FILE_SIZE_MB = 20;
export const MAX_FILE_SIZE = MAX_FILE_SIZE_MB * 1024 * 1024;
export const E164_PHONE_REGEX = /^\+?[1-9]\d{1,14}$/;

export const normalizePhoneForApi = (value: string): string => {
    const trimmed = value.trim();
    if (!trimmed) return '';
    if (trimmed.startsWith('+')) {
        return `+${trimmed.slice(1).replace(/\D/g, '')}`;
    }
    return trimmed.replace(/\D/g, '');
};

export const isE164Phone = (value: string): boolean =>
    E164_PHONE_REGEX.test(normalizePhoneForApi(value));

export const isNumberInRange = (value: number, min: number, max: number): boolean =>
    Number.isFinite(value) && value >= min && value <= max;

/** When both floor and total floors are set, floor must not exceed totalFloors. */
export const isFloorNotAboveTotalFloors = (floor: number, totalFloors: number): boolean =>
    Number.isFinite(floor) && Number.isFinite(totalFloors) && floor <= totalFloors;

/** Validates listing title by trimmed length; used on blur and step validation. */
export const getTitleFieldError = (value: string): string | undefined => {
    const t = value.trim();
    if (!t) return 'Укажите заголовок';
    if (t.length < TITLE_MIN_LENGTH) return `Минимум ${TITLE_MIN_LENGTH} символов`;
    if (t.length > TITLE_MAX_LENGTH) return `Максимум ${TITLE_MAX_LENGTH} символов`;
    return undefined;
};

/** Validates listing description by trimmed length; used on blur and step validation. */
export const getDescriptionFieldError = (value: string): string | undefined => {
    const t = value.trim();
    if (!t) return 'Добавьте описание';
    if (t.length < DESCRIPTION_MIN_LENGTH) return `Минимум ${DESCRIPTION_MIN_LENGTH} символов`;
    if (t.length > DESCRIPTION_MAX_LENGTH) return `Максимум ${DESCRIPTION_MAX_LENGTH} символов`;
    return undefined;
};

/** Минимум символов в поле города перед поиском подсказок. */
export const CITY_SEARCH_MIN_LENGTH = 2;

export const requiresApartmentAddress = (propertyType: string): boolean => propertyType === 'apartment';

/** Пользователь ввёл текст, но не выбрал город из подсказок. */
export const isCitySelectionPending = (cityQuery: string, cityId: number | null): boolean =>
    cityQuery.trim().length >= CITY_SEARCH_MIN_LENGTH && cityId === null;

export const getCityFieldError = (cityId: number | null): string | undefined => {
    if (cityId !== null) {
        return undefined;
    }
    return 'Выберите город из списка подсказок';
};

type CityQueryAddressSlice = {
    cityId: number | null;
    cityName: string;
    streetName: string;
    streetId: number | null;
    citySlug?: string;
};

/** Сбрасывает выбранный город при очистке поля или правке текста после выбора. */
export const getAddressAfterCityQueryChange = <T extends CityQueryAddressSlice>(
    prev: T,
    query: string,
): { next: T; clearStreet: boolean } => {
    const cleared = {
        cityId: null,
        cityName: '',
        streetName: '',
        streetId: null,
        ...(prev.citySlug !== undefined ? { citySlug: '' } : {}),
    } as Partial<T>;

    if (!query.trim()) {
        return { next: { ...prev, ...cleared }, clearStreet: true };
    }
    if (prev.cityId !== null && query.trim() !== prev.cityName.trim()) {
        return { next: { ...prev, ...cleared }, clearStreet: true };
    }
    return { next: prev, clearStreet: false };
};

export const getApartmentStreetFieldError = (
    propertyType: string,
    streetName: string,
    streetId: number | null,
): string | undefined => {
    if (!requiresApartmentAddress(propertyType)) {
        return undefined;
    }
    if (streetId !== null || streetName.trim() !== '') {
        return undefined;
    }
    return 'Укажите улицу';
};

export const getApartmentBuildingFieldError = (
    propertyType: string,
    building: string,
): string | undefined => {
    if (!requiresApartmentAddress(propertyType)) {
        return undefined;
    }
    if (building.trim() !== '') {
        return undefined;
    }
    return 'Укажите номер дома';
};
