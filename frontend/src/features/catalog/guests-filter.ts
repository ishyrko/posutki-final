import { MAX_DAILY_GUESTS } from "@/features/create-listing/validation";

export const MIN_GUESTS = 1;
export const GUESTS_QUERY_PARAM = "guests";

export function clampGuests(n: number): number {
  return Math.min(MAX_DAILY_GUESTS, Math.max(MIN_GUESTS, n));
}

/** Число гостей из `?guests=` или null, если параметра нет или он некорректен. */
export function parseGuestsFromQuery(raw: string | null): number | null {
  if (!raw) return null;
  const n = Number.parseInt(raw, 10);
  if (!Number.isFinite(n) || n < MIN_GUESTS || n > MAX_DAILY_GUESTS) {
    return null;
  }
  return n;
}

export function guestsSearchParam(count: number | null): string | null {
  if (count === null || count < MIN_GUESTS) {
    return null;
  }
  return String(clampGuests(count));
}
