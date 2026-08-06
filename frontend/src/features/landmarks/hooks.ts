'use client';

import { useQuery } from "@tanstack/react-query";
import { getCityLandmarks } from "./api";

export const useCityLandmarks = (citySlug: string, enabled = true) => {
  return useQuery({
    queryKey: ["city-landmarks", citySlug],
    queryFn: () => getCityLandmarks(citySlug),
    enabled: enabled && citySlug.length > 0,
  });
};
