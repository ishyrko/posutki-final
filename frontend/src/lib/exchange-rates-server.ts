import { fetchPublicApi } from "@/lib/server-api";
import type { ExchangeRates } from "@/features/properties/api";

/** Same payload as `GET /exchange-rates`, cached for an hour so SSR prices match the client. */
export async function fetchExchangeRates(): Promise<ExchangeRates | null> {
  try {
    const data = await fetchPublicApi<Partial<ExchangeRates>>("/exchange-rates", {
      next: { revalidate: 3600 },
    });
    if (typeof data.USD !== "number" || typeof data.RUB !== "number") {
      return null;
    }
    return { BYN: data.BYN ?? 1, USD: data.USD, RUB: data.RUB };
  } catch {
    return null;
  }
}
