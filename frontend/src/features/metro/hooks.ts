'use client';

import { useQuery } from "@tanstack/react-query";
import { getMetroStations } from "./api";

export const useMetroStations = (cityId = 1) => {
  return useQuery({
    queryKey: ["metro-stations", cityId],
    queryFn: () => getMetroStations(cityId),
  });
};
