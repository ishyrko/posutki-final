'use client';

import { useQuery } from "@tanstack/react-query";
import { fetchCityPlaces } from "./api";

export const useCityPlaces = (citySlug: string, enabled = true) => {
  return useQuery({
    queryKey: ["city-places", citySlug],
    queryFn: () => fetchCityPlaces(citySlug),
    enabled: enabled && citySlug.length > 0,
  });
};
