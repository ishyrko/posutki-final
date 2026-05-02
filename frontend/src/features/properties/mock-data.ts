import { Property, PropertyFilters, PropertyListResponse } from "./types";

export const mockProperties: Property[] = [
  {
    id: 1,
    title: "Просторная 3-комнатная квартира с ремонтом",
    description: "Светлая квартира в кирпичном доме с современным ремонтом. Развитая инфраструктура, рядом метро «Немига». Встроенная кухня, кондиционер во всех комнатах, тёплые полы в санузлах.\n\nДва санузла — совмещённый и раздельный. Закрытый двор с видеонаблюдением, наземная и подземная парковка. В пешей доступности парк, школы, торговые центры.",
    type: "apartment",
    dealType: "sale",
    status: "published",
    price: { amount: 185000, currency: "BYN" },
    address: {
      cityId: 1,
      cityName: "Минск",
      citySlug: "minsk",
      streetName: "ул. Немига",
      building: "12",
    },
    coordinates: { latitude: 53.902, longitude: 27.561 },
    specifications: { area: 85, rooms: 3, bathrooms: 1, floor: 7, totalFloors: 16 },
    images: [
      { id: 1, url: "https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1200&q=80" },
      { id: 2, url: "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1200&q=80" },
      { id: 3, url: "https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=1200&q=80" },
      { id: 4, url: "https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=1200&q=80" },
      { id: 5, url: "https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=1200&q=80" },
    ],
    createdAt: "2026-02-10T12:00:00Z",
  },
  {
    id: 2,
    title: "Загородный дом с участком 15 соток",
    description: "Двухэтажный дом с гаражом, баней и ландшафтным дизайном. Тихий коттеджный посёлок в Ратомке. Просторная кухня-гостиная, 5 спален, 3 санузла. Участок полностью огорожен, есть беседка и зона барбекю.\n\nДо центра Минска — 15 минут на машине. Рядом лесной массив и водоём.",
    type: "house",
    dealType: "sale",
    status: "published",
    price: { amount: 320000, currency: "BYN" },
    address: {
      cityId: 1,
      cityName: "Минский район",
      citySlug: "minsk-region",
      streetName: "Ратомка",
      building: "",
    },
    coordinates: { latitude: 53.955, longitude: 27.378 },
    specifications: { area: 220, rooms: 5, bathrooms: 3, floor: 1, totalFloors: 2 },
    images: [
      { id: 1, url: "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1200&q=80" },
      { id: 2, url: "https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1200&q=80" },
      { id: 3, url: "https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?w=1200&q=80" },
    ],
    createdAt: "2026-02-08T10:30:00Z",
  },
  {
    id: 3,
    title: "Стильная студия в новостройке «Минск Мир»",
    description: "Компактная студия с отделкой под ключ. Закрытый двор, подземный паркинг. Современная планировка, панорамное остекление, высокие потолки 3 м.\n\nВ шаговой доступности — метро «Восток», магазины, рестораны. Идеально для молодой пары или инвестиции в аренду.",
    type: "studio",
    dealType: "sale",
    status: "published",
    price: { amount: 95000, currency: "BYN" },
    address: {
      cityId: 1,
      cityName: "Минск",
      citySlug: "minsk",
      streetName: "Партизанский проспект",
      building: "2к3",
    },
    coordinates: { latitude: 53.876, longitude: 27.634 },
    specifications: { area: 32, rooms: 1, bathrooms: 1, floor: 12, totalFloors: 25 },
    images: [
      { id: 1, url: "https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=1200&q=80" },
      { id: 2, url: "https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=1200&q=80" },
    ],
    createdAt: "2026-02-12T15:00:00Z",
  },
  {
    id: 4,
    title: "4-комнатная квартира с видом на Свислочь",
    description: "Элитная квартира в историческом центре с панорамным видом на реку и Верхний город. Дизайнерский ремонт, натуральные материалы. Два санузла, гардеробная, лоджия 12 м².\n\nКонсьерж, видеонаблюдение. Рядом Троицкое предместье, парки, рестораны, театры.",
    type: "apartment",
    dealType: "sale",
    status: "published",
    price: { amount: 230000, currency: "BYN" },
    address: {
      cityId: 1,
      cityName: "Минск",
      citySlug: "minsk",
      streetName: "Троицкое предместье",
      building: "3",
    },
    coordinates: { latitude: 53.908, longitude: 27.555 },
    specifications: { area: 120, rooms: 4, bathrooms: 2, floor: 5, totalFloors: 9 },
    images: [
      { id: 1, url: "https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?w=1200&q=80" },
      { id: 2, url: "https://images.unsplash.com/photo-1600585154526-990dced4db0d?w=1200&q=80" },
      { id: 3, url: "https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1200&q=80" },
      { id: 4, url: "https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=1200&q=80" },
    ],
    createdAt: "2026-02-05T09:00:00Z",
  },
  {
    id: 5,
    title: "2-комнатная квартира в центре Бреста",
    description: "Уютная квартира в пешей доступности от Брестской крепости. Свежий косметический ремонт, новая сантехника. Тихий зелёный двор.\n\nРазвитая инфраструктура: школы, детские сады, поликлиника, супермаркеты — всё в 5 минутах ходьбы.",
    type: "apartment",
    dealType: "sale",
    status: "published",
    price: { amount: 78000, currency: "BYN" },
    address: {
      cityId: 30211,
      cityName: "Брест",
      citySlug: "brest",
      streetName: "ул. Советская",
      building: "48",
    },
    coordinates: { latitude: 52.098, longitude: 23.685 },
    specifications: { area: 54, rooms: 2, bathrooms: 1, floor: 3, totalFloors: 5 },
    images: [
      { id: 1, url: "https://images.unsplash.com/photo-1600573472592-401b489a3cdc?w=1200&q=80" },
      { id: 2, url: "https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?w=1200&q=80" },
    ],
    createdAt: "2026-02-03T14:00:00Z",
  },
  {
    id: 6,
    title: "Пентхаус с террасой в «Маяк Минска»",
    description: "Двухуровневый пентхаус с террасой 40 м² и видом на Минское море. Дизайнерский ремонт с натуральными материалами — дуб, мрамор, стекло.\n\nПанорамное остекление, камин, система «умный дом». Два машиноместа в подземном паркинге. Консьерж-сервис 24/7.",
    type: "penthouse",
    dealType: "sale",
    status: "published",
    price: { amount: 450000, currency: "BYN" },
    address: {
      cityId: 1,
      cityName: "Минск",
      citySlug: "minsk",
      streetName: "пр-т Победителей",
      building: "1",
    },
    coordinates: { latitude: 53.916, longitude: 27.548 },
    specifications: { area: 180, rooms: 3, bathrooms: 2, floor: 24, totalFloors: 25 },
    images: [
      { id: 1, url: "https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?w=1200&q=80" },
      { id: 2, url: "https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=1200&q=80" },
      { id: 3, url: "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1200&q=80" },
      { id: 4, url: "https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=1200&q=80" },
      { id: 5, url: "https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1200&q=80" },
      { id: 6, url: "https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=1200&q=80" },
    ],
    createdAt: "2026-02-14T11:00:00Z",
  },
  {
    id: 7,
    title: "Таунхаус в Гродно",
    description: "Современный таунхаус с собственным участком и парковкой на 2 машины. Рядом школа и детский сад. Два этажа: на первом — кухня-гостиная, кабинет и санузел; на втором — 3 спальни и ванная.\n\nМикрорайон Ольшанка — новый, с развитой инфраструктурой.",
    type: "townhouse",
    dealType: "sale",
    status: "published",
    price: { amount: 115000, currency: "BYN" },
    address: {
      cityId: 30216,
      cityName: "Гродно",
      citySlug: "grodno",
      streetName: "мкр. Ольшанка",
      building: "17",
    },
    coordinates: { latitude: 53.677, longitude: 23.830 },
    specifications: { area: 140, rooms: 3, bathrooms: 2, floor: 1, totalFloors: 2 },
    images: [
      { id: 1, url: "https://images.unsplash.com/photo-1600585154526-990dced4db0d?w=1200&q=80" },
      { id: 2, url: "https://images.unsplash.com/photo-1600573472592-401b489a3cdc?w=1200&q=80" },
    ],
    createdAt: "2026-02-01T08:00:00Z",
  },
  {
    id: 8,
    title: "1-комнатная у метро «Каменная горка»",
    description: "Квартира с ремонтом в 3 минутах от метро. Отличная транспортная доступность — до центра 20 минут. Новая мебель, бытовая техника включена.\n\nРядом ТЦ «Экспобел», парк, фитнес-клубы. Подходит для проживания или сдачи в аренду.",
    type: "apartment",
    dealType: "sale",
    status: "published",
    price: { amount: 62000, currency: "BYN" },
    address: {
      cityId: 1,
      cityName: "Минск",
      citySlug: "minsk",
      streetName: "ул. Притыцкого",
      building: "97",
    },
    coordinates: { latitude: 53.908, longitude: 27.432 },
    specifications: { area: 38, rooms: 1, bathrooms: 1, floor: 9, totalFloors: 12 },
    images: [
      { id: 1, url: "https://images.unsplash.com/photo-1600607687644-c7171b42498f?w=1200&q=80" },
      { id: 2, url: "https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?w=1200&q=80" },
    ],
    createdAt: "2026-01-28T16:30:00Z",
  },
  {
    id: 9,
    title: "Дом с участком в Витебске",
    description: "Просторный дом с садом и беседкой. Тихий район с хорошей экологией. 4 спальни, 2 санузла, большая кухня-гостиная с камином.\n\nУчасток 12 соток, ухоженный сад, зона барбекю. До центра Витебска — 10 минут на машине.",
    type: "house",
    dealType: "sale",
    status: "published",
    price: { amount: 260000, currency: "BYN" },
    address: {
      cityId: 30213,
      cityName: "Витебск",
      citySlug: "vitebsk",
      streetName: "ул. Чкалова",
      building: "15а",
    },
    coordinates: { latitude: 55.184, longitude: 30.202 },
    specifications: { area: 200, rooms: 4, bathrooms: 2, floor: 1, totalFloors: 2 },
    images: [
      { id: 1, url: "https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?w=1200&q=80" },
      { id: 2, url: "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1200&q=80" },
      { id: 3, url: "https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?w=1200&q=80" },
    ],
    createdAt: "2026-02-07T13:00:00Z",
  },
  {
    id: 10,
    title: "2-комнатная квартира в центре Гомеля",
    description: "Квартира после капремонта в самом центре города. Вся инфраструктура в шаговой доступности. Новые окна, двери, электропроводка.\n\nРядом парк, театр, кафе и рестораны. Идеальный вариант для семьи.",
    type: "apartment",
    dealType: "sale",
    status: "published",
    price: { amount: 89000, currency: "BYN" },
    address: {
      cityId: 30215,
      cityName: "Гомель",
      citySlug: "gomel",
      streetName: "ул. Советская",
      building: "15",
    },
    coordinates: { latitude: 52.424, longitude: 30.988 },
    specifications: { area: 62, rooms: 2, bathrooms: 1, floor: 4, totalFloors: 9 },
    images: [
      { id: 1, url: "https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?w=1200&q=80" },
      { id: 2, url: "https://images.unsplash.com/photo-1600573472592-401b489a3cdc?w=1200&q=80" },
    ],
    createdAt: "2026-01-25T10:00:00Z",
  },
  {
    id: 11,
    title: "Студия с балконом в Могилёве",
    description: "Стильная студия с панорамным остеклением. Идеальна для молодой пары. Высокий этаж, красивый вид на город. Современный ремонт в стиле лофт.\n\nНовостройка 2023 года. Есть лифт, видеонаблюдение, домофон.",
    type: "studio",
    dealType: "sale",
    status: "published",
    price: { amount: 48000, currency: "BYN" },
    address: {
      cityId: 30219,
      cityName: "Могилёв",
      citySlug: "mogilev",
      streetName: "бул. Непокорённых",
      building: "7",
    },
    coordinates: { latitude: 53.894, longitude: 30.332 },
    specifications: { area: 28, rooms: 1, bathrooms: 1, floor: 15, totalFloors: 17 },
    images: [
      { id: 1, url: "https://images.unsplash.com/photo-1600573472592-401b489a3cdc?w=1200&q=80" },
      { id: 2, url: "https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=1200&q=80" },
    ],
    createdAt: "2026-02-15T09:30:00Z",
  },
  {
    id: 12,
    title: "3-комнатная квартира в «Маяк Минска»",
    description: "Большая семейная квартира с двумя санузлами и кладовой. Консьерж, охраняемая территория. Современный ремонт, встроенная техника Bosch.\n\nПанорамные окна с видом на город. Подземная парковка. Рядом набережная Свислочи.",
    type: "apartment",
    dealType: "rent",
    status: "published",
    price: { amount: 3900, currency: "BYN" },
    address: {
      cityId: 1,
      cityName: "Минск",
      citySlug: "minsk",
      streetName: "пр-т Победителей",
      building: "1/2",
    },
    coordinates: { latitude: 53.915, longitude: 27.540 },
    specifications: { area: 110, rooms: 3, bathrooms: 2, floor: 18, totalFloors: 25 },
    images: [
      { id: 1, url: "https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1200&q=80" },
      { id: 2, url: "https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=1200&q=80" },
      { id: 3, url: "https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=1200&q=80" },
    ],
    createdAt: "2026-02-16T18:00:00Z",
  },
];

export function getMockPropertiesResponse(filters: PropertyFilters = {}): PropertyListResponse {
  let result = [...mockProperties];

  if (filters.types && Array.isArray(filters.types) && filters.types.length > 0) {
    const allowed = new Set(filters.types as string[]);
    result = result.filter((p) => allowed.has(p.type));
  } else if (filters.type && filters.type !== "all") {
    result = result.filter((p) => p.type === filters.type);
  }
  if (filters.dealType) {
    result = result.filter((p) => p.dealType === filters.dealType);
  }
  if (filters.rooms) {
    const r = Number(filters.rooms);
    const countable = ["apartment", "house", "dacha"] as const;
    const hadTypeFilter =
      (filters.types && Array.isArray(filters.types) && filters.types.length > 0) ||
      (filters.type && filters.type !== "all");
    result = result.filter((p) => {
      if (!countable.includes(p.type as (typeof countable)[number])) {
        return hadTypeFilter;
      }
      const rooms = p.specifications.rooms ?? 0;
      return r >= 4 ? rooms >= 4 : rooms === r;
    });
  }
  if (filters.minPrice) {
    result = result.filter((p) => p.price.amount >= Number(filters.minPrice));
  }
  if (filters.maxPrice) {
    result = result.filter((p) => p.price.amount <= Number(filters.maxPrice));
  }

  if (filters.sortBy === "price") {
    result.sort((a, b) =>
      filters.sortOrder === "ASC"
        ? a.price.amount - b.price.amount
        : b.price.amount - a.price.amount
    );
  } else if (filters.sortBy === "area") {
    result.sort((a, b) =>
      filters.sortOrder === "ASC"
        ? a.specifications.area - b.specifications.area
        : b.specifications.area - a.specifications.area
    );
  }

  const page = Number(filters.page) || 1;
  const limit = Number(filters.limit) || 12;
  const total = result.length;
  const start = (page - 1) * limit;
  const paged = result.slice(start, start + limit);

  return {
    data: paged,
    meta: { total, page, limit },
  };
}

export function getMockProperty(id: number): Property | undefined {
  return mockProperties.find((p) => p.id === id);
}
