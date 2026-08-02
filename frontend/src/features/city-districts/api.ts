import api from "@/lib/api";
import type { CityDistrict } from "./types";

export const getCityDistricts = async (citySlug: string): Promise<CityDistrict[]> => {
  const response = await api.get<{ data: CityDistrict[] }>(
    `/cities/${encodeURIComponent(citySlug)}/districts`,
  );
  return response.data.data;
};
