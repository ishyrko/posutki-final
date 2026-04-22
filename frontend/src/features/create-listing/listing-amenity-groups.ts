/** Удобства посуточного жилья — шаг мастера размещения (группы как в макете). id уходит в API как строка. */
export type ListingAmenityItem = { id: string; label: string };

export type ListingAmenityGroup = {
    id: string;
    title: string;
    items: ListingAmenityItem[];
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
];
