import api from "@/lib/api";
import type { Landmark, LandmarkListItem } from "./types";

export const getCityLandmarks = async (citySlug: string): Promise<LandmarkListItem[]> => {
  const response = await api.get<{ data: LandmarkListItem[] }>(
    `/cities/${encodeURIComponent(citySlug)}/landmarks`,
  );
  return response.data.data;
};

export const getCityLandmark = async (
  citySlug: string,
  slug: string,
): Promise<Landmark | null> => {
  try {
    const response = await api.get<{ data: Landmark }>(
      `/cities/${encodeURIComponent(citySlug)}/landmarks/${encodeURIComponent(slug)}`,
    );
    return response.data.data;
  } catch {
    return null;
  }
};
