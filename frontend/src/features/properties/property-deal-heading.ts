/**
 * Род. падеж после «Продажа / Аренда» (напр. «Аренда офиса»).
 */
const PROPERTY_TYPE_GENITIVE: Record<string, string> = {
  apartment: "квартиры",
  house: "дома",
  room: "комнаты",
  land: "участка",
  garage: "гаража",
  parking: "машиноместа",
  dacha: "дачи",
  office: "офиса",
  retail: "торгового помещения",
  warehouse: "склада",
  business: "готового бизнеса",
};

/** Именительный падеж для посуточных заголовков: «Квартира на сутки», «Дом на сутки». */
const PROPERTY_TYPE_NOMINATIVE_DAILY: Record<string, string> = {
  apartment: "Квартира",
  house: "Дом",
  room: "Комната",
  land: "Участок",
  garage: "Гараж",
  parking: "Машиноместо",
  dacha: "Дача",
  office: "Офис",
  retail: "Торговое помещение",
  warehouse: "Склад",
  business: "Готовый бизнес",
};

/**
 * Строка над заголовком карточки объявления: «Аренда офиса», «Продажа квартиры»,
 * посуточно — «Квартира на сутки», «Дом на сутки», «Дача на сутки» и т.д.
 */
export function formatPropertyDealHeading(
  dealType: "sale" | "rent" | "daily",
  propertyType: string,
): string {
  if (dealType === "daily") {
    const phrase = PROPERTY_TYPE_NOMINATIVE_DAILY[propertyType];
    if (phrase) return `${phrase} на сутки`;
    return `Посуточная аренда ${PROPERTY_TYPE_GENITIVE[propertyType] ?? propertyType}`;
  }

  const noun = PROPERTY_TYPE_GENITIVE[propertyType] ?? propertyType;
  if (dealType === "sale") return `Продажа ${noun}`;
  return `Аренда ${noun}`;
}
