export const renovationOptions = [
    'Без ремонта',
    'Требует ремонта',
    'Косметический',
    'Хороший',
    'Евроремонт',
    'Дизайнерский',
];

/** Только посуточно — без варианта «без ремонта». */
export const renovationOptionsForDeal = (dealType: string): string[] => {
    void dealType;
    return renovationOptions.filter((o) => o !== 'Без ремонта');
};

export const balconyOptions = ['Нет', 'Балкон', 'Лоджия', 'Балкон и лоджия'];

export const dealConditionOptions = (dealType: string, propertyType?: string): string[] => {
    void dealType;
    void propertyType;
    return [];
};

export const sanitizeDealConditionsForPropertyType = (
    propertyType: string,
    dealConditions: string[],
): string[] => {
    void propertyType;
    return dealConditions;
};

export const showDealConditions = (dealType: string): boolean => {
    void dealType;
    return false;
};

export const showRooms = (type: string): boolean => ['apartment', 'house'].includes(type);

export const showRoomsCatalogFilter = (propertyType: string | undefined): boolean =>
    propertyType != null && ['apartment', 'house'].includes(propertyType);

export const showBathrooms = (type: string): boolean => ['apartment', 'house'].includes(type);

export const showFloor = (type: string): boolean => type === 'apartment';

export const showTotalFloors = (type: string): boolean => type === 'apartment';

export const showYearBuilt = (type: string): boolean => type === 'apartment';

export const showRenovation = (type: string): boolean => type === 'apartment';

export const showBalcony = (type: string): boolean => type === 'apartment';

export const showLivingArea = (type: string): boolean => type === 'apartment';

export const showKitchenArea = (type: string): boolean => type === 'apartment';

export const roomsRequired = (type: string): boolean => type === 'apartment';

export const bathroomsRequired = (type: string): boolean => type === 'apartment';

export const showRoomDealFields = (type: string, dealType: string): boolean => {
    void type;
    void dealType;
    return false;
};
