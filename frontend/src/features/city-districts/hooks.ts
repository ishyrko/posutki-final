'use client';

import { useQuery } from "@tanstack/react-query";
import { getCityDistricts } from "./api";

export const useCityDistricts = (citySlug: string, enabled = true) => {
  return useQuery({
    queryKey: ["city-districts", citySlug],
    queryFn: () => getCityDistricts(citySlug),
    enabled: enabled && citySlug.length > 0,
  });
};
