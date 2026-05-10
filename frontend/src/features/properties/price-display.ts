import type { ExchangeRates } from "./api";
import type { Currency, Property } from "./types";

/** BYN per 1 unit of each currency (same convention as API `/exchange-rates`). */
export const DEFAULT_EXCHANGE_RATES_FALLBACK: ExchangeRates = {
  BYN: 1,
  USD: 3.27,
  RUB: 0.034,
};

export function normalizeCurrency(c: string): Currency {
  const u = c.trim().toUpperCase();
  if (u === "BYN" || u === "USD" || u === "RUB") {
    return u;
  }
  return "BYN";
}

export function convertPrice(
  amount: number,
  fromCurrency: string,
  toCurrency: Currency,
  rates: ExchangeRates,
): number {
  const from = normalizeCurrency(fromCurrency);
  const to = normalizeCurrency(toCurrency);
  if (from === to) return amount;
  const fromRate = rates[from] ?? 1;
  const toRate = rates[to] ?? 1;
  return Math.round((amount * fromRate) / toRate);
}

/** Plain-text price (e.g. map balloons, metadata); uses ISO code, not the graphic symbol. */
export function formatPrice(amount: number, currency: Currency): string {
  return amount.toLocaleString("ru-BY") + " " + currency;
}

export function formatSecondaryPrice(amount: number, currency: "USD" | "RUB"): string {
  const symbol = currency === "RUB" ? "₽" : "$";
  return "≈ " + amount.toLocaleString("ru-BY") + " " + symbol;
}

export function formatPropertyPrices(property: Property, rates: ExchangeRates): {
  /** BYN-equivalent amount for UI (graphic symbol via `PriceInByn`). */
  primaryAmount: number;
  /** Same amount as plain text for share, SEO, map strings. */
  primaryPlain: string;
  secondary: string;
} {
  const listingCurrency = normalizeCurrency(property.price.currency);
  const bynAmount =
    property.priceByn != null && property.priceByn > 0
      ? property.priceByn
      : convertPrice(property.price.amount, listingCurrency, "BYN", rates);

  let secondaryCurrency: "USD" | "RUB";
  let secondaryAmount: number;
  if (listingCurrency === "RUB") {
    secondaryCurrency = "RUB";
    secondaryAmount = property.price.amount;
  } else if (listingCurrency === "USD") {
    secondaryCurrency = "USD";
    secondaryAmount = property.price.amount;
  } else {
    // BYN listing — show ≈ USD as secondary
    secondaryCurrency = "USD";
    secondaryAmount = convertPrice(bynAmount, "BYN", "USD", rates);
  }

  return {
    primaryAmount: bynAmount,
    primaryPlain: formatPrice(bynAmount, "BYN"),
    secondary: formatSecondaryPrice(secondaryAmount, secondaryCurrency),
  };
}
