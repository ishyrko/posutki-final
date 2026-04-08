import api from "@/lib/api";
import type { MetroStation } from "./types";

export const getMetroStations = async (cityId = 1): Promise<MetroStation[]> => {
  const response = await api.get<{ data: MetroStation[] }>(`/metro/stations?cityId=${cityId}`);
  return response.data.data;
};
