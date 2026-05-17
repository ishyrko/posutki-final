const GEOCODER_URL = "https://geocode-maps.yandex.ru/v1/";

export interface GeocodeCoords {
  latitude: number;
  longitude: number;
}

interface GeocoderResponse {
  response?: {
    GeoObjectCollection?: {
      featureMember?: Array<{
        GeoObject?: {
          Point?: { pos?: string };
        };
      }>;
    };
  };
  message?: string;
}

/** Forward geocoding via Yandex Geocoder API (отдельный apikey от JavaScript API карт). */
export async function geocodeAddress(query: string): Promise<GeocodeCoords | null> {
  const apikey = process.env.NEXT_PUBLIC_YANDEX_GEOCODER_API_KEY;
  if (!apikey) {
    throw new Error("NEXT_PUBLIC_YANDEX_GEOCODER_API_KEY is not configured");
  }

  const url = new URL(GEOCODER_URL);
  url.searchParams.set("apikey", apikey);
  url.searchParams.set("geocode", query);
  url.searchParams.set("lang", "ru_RU");
  url.searchParams.set("format", "json");
  url.searchParams.set("results", "1");

  const res = await fetch(url);
  if (!res.ok) {
    throw new Error(`Geocoder HTTP ${res.status}`);
  }

  const data = (await res.json()) as GeocoderResponse;
  const pos = data.response?.GeoObjectCollection?.featureMember?.[0]?.GeoObject?.Point?.pos;
  if (!pos) return null;

  const [longitude, latitude] = pos.split(/\s+/).map(Number);
  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return null;

  return { latitude, longitude };
}
