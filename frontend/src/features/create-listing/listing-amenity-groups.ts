/** Удобства посуточного жилья — шаг мастера размещения (группы как в макете). id уходит в API как строка. */
export type ListingAmenityItem = {
    id: string;
    label: string;
    /** Если задано — пункт показывается только для указанных типов объекта */
    propertyTypes?: string[];
};

export type ListingAmenityGroup = {
    id: string;
    title: string;
    items: ListingAmenityItem[];
    /** Если задано — группа показывается только для указанных типов объекта */
    propertyTypes?: string[];
};

export const LISTING_AMENITY_GROUPS: ListingAmenityGroup[] = [
    {
        id: 'kitchen',
        title: 'На кухне',
        items: [
            { id: 'fridge', label: 'Холодильник' },
            { id: 'electric_stove', label: 'Электрическая плита' },
            { id: 'gas_stove', label: 'Газовая плита' },
            { id: 'induction_stove', label: 'Индукционная плита' },
            { id: 'oven', label: 'Духовка' },
            { id: 'microwave', label: 'Микроволновая печь' },
            { id: 'dishwasher', label: 'Посудомоечная машина' },
            { id: 'coffee_machine', label: 'Кофемашина' },
            { id: 'kettle', label: 'Чайник' },
            { id: 'blender', label: 'Блендер' },
            { id: 'dishes_utensils', label: 'Посуда и столовые приборы' },
        ],
    },
    {
        id: 'bathroom',
        title: 'В ванной',
        items: [
            { id: 'bathroom_separate', label: 'Раздельный санузел' },
            { id: 'bathroom_combined', label: 'Совмещённый санузел' },
            { id: 'jacuzzi', label: 'Джакузи' },
            { id: 'rain_shower', label: 'Тропический душ' },
            { id: 'towels', label: 'Полотенца' },
            { id: 'hairdryer', label: 'Фен' },
            { id: 'bathrobes', label: 'Халаты' },
            { id: 'toiletries', label: 'Туалетные принадлежности' },
        ],
    },
    {
        id: 'entertainment',
        title: 'Развлечения',
        items: [
            { id: 'smart_tv', label: 'Smart TV' },
            { id: 'tv', label: 'Телевизор' },
            { id: 'wifi', label: 'Wi-Fi' },
            { id: 'playstation', label: 'PlayStation' },
            { id: 'bluetooth_speaker', label: 'Bluetooth-колонка' },
            { id: 'projector', label: 'Проектор' },
            { id: 'cable_tv', label: 'Кабельное ТВ' },
        ],
    },
    {
        id: 'comfort',
        title: 'Комфорт',
        items: [
            { id: 'air_conditioner', label: 'Кондиционер' },
            { id: 'heated_floor', label: 'Тёплый пол' },
            { id: 'iron', label: 'Утюг' },
            { id: 'washing_machine', label: 'Стиральная машина' },
            { id: 'dryer', label: 'Сушильная машина' },
            { id: 'robot_vacuum', label: 'Робот-пылесос' },
            { id: 'crib', label: 'Детская кроватка' },
            { id: 'high_chair', label: 'Стульчик для кормления' },
        ],
    },
    {
        id: 'outdoor',
        title: 'На территории',
        items: [
            { id: 'parking_open', label: 'Открытая парковка' },
            { id: 'parking_covered', label: 'Закрытая парковка' },
            { id: 'cctv', label: 'Видеонаблюдение' },
            { id: 'gazebo', label: 'Беседка', propertyTypes: ['house'] },
            { id: 'pool', label: 'Бассейн', propertyTypes: ['house'] },
            { id: 'pond', label: 'Пруд', propertyTypes: ['house'] },
            { id: 'bbq', label: 'Гриль / мангал', propertyTypes: ['house'] },
            { id: 'sauna', label: 'Баня / сауна', propertyTypes: ['house'] },
            { id: 'playground', label: 'Детская площадка', propertyTypes: ['house'] },
            { id: 'garden', label: 'Сад / огород', propertyTypes: ['house'] },
        ],
    },
];
