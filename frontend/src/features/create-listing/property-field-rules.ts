export const renovationOptions = [
    'Без ремонта',
    'Требует ремонта',
    'Косметический',
    'Хороший',
    'Евроремонт',
    'Дизайнерский',
];

/** Только посуточно — без варианта «без ремонта». */
export const renovationOptionsForDeal = (_dealType: string): string[] =>
    renovationOptions.filter((o) => o !== 'Без ремонта');

export const balconyOptions = ['Нет', 'Балкон', 'Лоджия', 'Балкон и лоджия'];

export const dealConditionOptions = (_dealType: string, _propertyType?: string): string[] => [];

export const sanitizeDealConditionsForPropertyType = (
    _propertyType: string,
    dealConditions: string[],
): string[] => dealConditions;

export const showDealConditions = (_dealType: string): boolean => false;

export const showRooms = (type: string): boolean => ['apartment', 'house'].includes(type);

export const showRoomsCatalogFilter = (propertyType: string | undefined): boolean =>
    propertyType != null && ['apartment', 'house'].includes(propertyType);

export const showBathrooms = (type: string): boolean => ['apartment', 'house'].includes(type);

export const showFloor = (type: string): boolean => type === 'apartment';

export const showTotalFloors = (type: string): boolean => ['apartment', 'house'].includes(type);

export const showYearBuilt = (type: string): boolean => ['apartment', 'house'].includes(type);

export const showRenovation = (type: string): boolean => ['apartment', 'house'].includes(type);

export const showBalcony = (type: string): boolean => type === 'apartment';

export const showLivingArea = (type: string): boolean => ['apartment', 'house'].includes(type);

export const showKitchenArea = (type: string): boolean => ['apartment', 'house'].includes(type);

export const roomsRequired = (type: string): boolean => type === 'apartment';

export const showRoomDealFields = (_type: string, _dealType: string): boolean => false;
