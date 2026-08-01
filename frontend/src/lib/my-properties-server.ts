import { fetchApi } from "@/lib/server-api";
import type { Property } from "@/features/properties/types";

/** Есть ли у текущего пользователя хотя бы одно объявление (любой статус). */
export async function fetchHasMyProperties(): Promise<boolean> {
  try {
    const properties = await fetchApi<Property[]>("/properties/my?page=1&limit=1");
    return properties.length > 0;
  } catch {
    return false;
  }
}
