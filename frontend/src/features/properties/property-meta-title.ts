import type { ExchangeRates } from "./api";
import { DEFAULT_EXCHANGE_RATES_FALLBACK, formatPropertyPrices } from "./price-display";
import { formatAddress, type Property } from "./types";

const SITE_BRAND = "Посутки.by";

/** Краткая запись комнатности для SEO («1-к», «4-к»). */
export function formatApartmentRoomsShort(rooms: number): string {
  return `${rooms}-к`;
}

/** Цена в meta title: «от 914 р. / сутки» (BYN, без копеек). */
export function formatPropertyMetaPricePerDay(
  property: Property,
  rates: ExchangeRates = DEFAULT_EXCHANGE_RATES_FALLBACK,
): string {
  const { primaryAmount } = formatPropertyPrices(property, rates, "BYN");
  const amount = Math.round(primaryAmount);
  return `от ${amount.toLocaleString("ru-BY")} р. / сутки`;
}

/**
 * Meta title карточки квартиры.
 * Пример: «Снять 1-к квартиру 37 м² на сутки по адресу Связистов ул, 11, Минск, от 914 р. / сутки на Посутки.by»
 */
export function buildApartmentPropertyMetaTitle(
  property: Property,
  rates: ExchangeRates = DEFAULT_EXCHANGE_RATES_FALLBACK,
): string | null {
  if (property.type !== "apartment") {
    return null;
  }

  const rooms = property.specifications.rooms;
  const roomPart =
    rooms != null && rooms > 0 ? `${formatApartmentRoomsShort(rooms)} ` : "";
  const area = property.specifications.area;
  const address = formatAddress(property.address);
  const pricePart = formatPropertyMetaPricePerDay(property, rates);

  return `Снять ${roomPart}квартиру ${area} м² на сутки по адресу ${address}, ${pricePart} на ${SITE_BRAND}`;
}

/**
 * Meta description карточки квартиры.
 * Пример: «Сдается в посуточную аренду 1-к квартира 37 м² от 914 р. / сутки. по адресу Связистов ул, 11, Минск. Смотрите на Посутки.by»
 */
export function buildApartmentPropertyMetaDescription(
  property: Property,
  rates: ExchangeRates = DEFAULT_EXCHANGE_RATES_FALLBACK,
): string | null {
  if (property.type !== "apartment") {
    return null;
  }

  const rooms = property.specifications.rooms;
  const roomPart =
    rooms != null && rooms > 0 ? `${formatApartmentRoomsShort(rooms)} ` : "";
  const area = property.specifications.area;
  const address = formatAddress(property.address);
  const pricePart = formatPropertyMetaPricePerDay(property, rates);

  return `Сдается в посуточную аренду ${roomPart}квартира ${area} м² ${pricePart}. по адресу ${address}. Смотрите на ${SITE_BRAND}`;
}
