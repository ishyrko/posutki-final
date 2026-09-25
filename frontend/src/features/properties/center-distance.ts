export interface CenterDistance {
  cityName: string;
  citySlug: string;
  cityNameGenitive?: string | null;
  distanceKm: number;
}

export interface LocationDistances {
  nearestCity?: CenterDistance | null;
  regionCenter?: CenterDistance | null;
}

function formatCenterDistanceKm(distanceKm: number): string {
  if (distanceKm < 1) {
    return `${Math.round(distanceKm * 1000)} м`;
  }

  if (distanceKm < 10) {
    return `${distanceKm.toFixed(1)} км`;
  }

  return `${Math.round(distanceKm)} км`;
}

export function formatCenterDistance(entry: CenterDistance): string {
  const distance = formatCenterDistanceKm(entry.distanceKm);
  const cityLabel = entry.cityNameGenitive?.trim() || entry.cityName;

  return `${distance} от ${cityLabel}`;
}

export function formatLocationDistancesLabel(
  distances?: LocationDistances | null,
  options?: { includeRegionCenter?: boolean },
): string | null {
  if (!distances) {
    return null;
  }

  const parts: string[] = [];
  if (distances.nearestCity) {
    parts.push(formatCenterDistance(distances.nearestCity));
  }

  if (options?.includeRegionCenter !== false && distances.regionCenter) {
    parts.push(formatCenterDistance(distances.regionCenter));
  }

  return parts.length > 0 ? parts.join(" · ") : null;
}

export function formatNearestCityDistanceLabel(
  distances?: LocationDistances | null,
): string | null {
  if (!distances?.nearestCity) {
    return null;
  }

  return formatCenterDistance(distances.nearestCity);
}
