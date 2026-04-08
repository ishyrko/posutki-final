export const renovationOptions = [
    'Без ремонта',
    'Требует ремонта',
    'Косметический',
    'Хороший',
    'Евроремонт',
    'Дизайнерский',
];

/** Daily rent: do not offer the "no renovation" option */
export const renovationOptionsForDeal = (dealType: string): string[] =>
    dealType === 'daily'
        ? renovationOptions.filter((o) => o !== 'Без ремонта')
        : renovationOptions;

export const balconyOptions = ['Нет', 'Балкон', 'Лоджия', 'Балкон и лоджия'];

export const saleDealConditionOptions = [
    'Чистая продажа',
    'Подбираются варианты',
    'Обмен',
];

export const rentDealConditionOptions = [
    'Чистая аренда',
    'Подбираются варианты',
    'Предоплата 1 мес.',
    'Предоплата 3 мес.',
];

/** Garage and parking: do not offer the "still looking" deal condition */
const PROPERTY_TYPES_WITHOUT_PODBIRAJUTSYA: string[] = ['garage', 'parking'];

export const dealConditionOptions = (dealType: string, propertyType?: string): string[] => {
    let base: string[] = [];
    if (dealType === 'sale') base = [...saleDealConditionOptions];
    else if (dealType === 'rent') base = [...rentDealConditionOptions];
    else return [];
    if (propertyType && PROPERTY_TYPES_WITHOUT_PODBIRAJUTSYA.includes(propertyType)) {
        return base.filter((o) => o !== 'Подбираются варианты');
    }
    return base;
};

export const sanitizeDealConditionsForPropertyType = (
    propertyType: string,
    dealConditions: string[],
): string[] => {
    if (!PROPERTY_TYPES_WITHOUT_PODBIRAJUTSYA.includes(propertyType)) {
        return dealConditions;
    }
    return dealConditions.filter((c) => c !== 'Подбираются варианты');
};

export const showDealConditions = (dealType: string): boolean =>
    ['sale', 'rent'].includes(dealType);

export const showRooms = (type: string): boolean =>
    ['apartment', 'room', 'house', 'dacha'].includes(type);

/** Catalog rooms filter applies only to apartment, house, and dacha */
export const showRoomsCatalogFilter = (propertyType: string | undefined): boolean =>
    propertyType != null && ['apartment', 'house', 'dacha'].includes(propertyType);

export const showBathrooms = (type: string): boolean =>
    ['apartment', 'house'].includes(type);

export const showFloor = (type: string): boolean =>
    ['apartment', 'room', 'office', 'retail', 'warehouse', 'parking'].includes(type);

export const showTotalFloors = (type: string): boolean =>
    ['apartment', 'room', 'house', 'dacha', 'office', 'retail', 'warehouse'].includes(type);

export const showYearBuilt = (type: string): boolean =>
    ['apartment', 'room', 'house', 'dacha', 'office', 'retail', 'warehouse', 'garage'].includes(type);

export const showRenovation = (type: string): boolean =>
    ['apartment', 'room', 'house', 'dacha'].includes(type);

export const showBalcony = (type: string): boolean =>
    ['apartment', 'room'].includes(type);

export const showLivingArea = (type: string): boolean =>
    ['apartment', 'room', 'house', 'dacha'].includes(type);

export const showKitchenArea = (type: string): boolean =>
    ['apartment', 'house', 'dacha'].includes(type);

export const roomsRequired = (type: string): boolean =>
    type === 'apartment';

/** Room type on sale/rent: extra deal-specific fields */
export const showRoomDealFields = (type: string, dealType: string): boolean =>
    type === 'room' && ['sale', 'rent'].includes(dealType);
