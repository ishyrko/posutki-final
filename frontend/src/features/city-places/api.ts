import api from "@/lib/api";
import type { CatalogPlaceDetail, CityPlace } from "./types";

export const fetchCityPlaces = async (citySlug: string): Promise<CityPlace[]> => {
  const response = await api.get<{ data: CityPlace[] }>(
    `/cities/${encodeURIComponent(citySlug)}/places`,
  );
  return response.data.data;
};

export const fetchMicrodistrictCatalog = async (
  citySlug: string,
  slug: string,
): Promise<CatalogPlaceDetail> => {
  const response = await api.get<{ data: CatalogPlaceDetail }>(
    `/cities/${encodeURIComponent(citySlug)}/microdistricts/${encodeURIComponent(slug)}`,
  );
  return response.data.data;
};

export const fetchResidentialComplexCatalog = async (
  citySlug: string,
  slug: string,
): Promise<CatalogPlaceDetail> => {
  const response = await api.get<{ data: CatalogPlaceDetail }>(
    `/cities/${encodeURIComponent(citySlug)}/residential-complexes/${encodeURIComponent(slug)}`,
  );
  return response.data.data;
};
