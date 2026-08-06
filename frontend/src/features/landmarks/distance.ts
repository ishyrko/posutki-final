export function formatLandmarkDistance(distanceKm: number): string {
  if (distanceKm < 1) {
    return `${Math.round(distanceKm * 1000)} м`;
  }

  return `${distanceKm.toFixed(1)} км`;
}

export const LANDMARK_DISTANCE_FILTER_OPTIONS = [
  { label: "Любое", value: 0 },
  { label: "до 1 км", value: 1 },
  { label: "до 3 км", value: 3 },
  { label: "до 5 км", value: 5 },
] as const;

export type LandmarkDistanceFilterValue =
  (typeof LANDMARK_DISTANCE_FILTER_OPTIONS)[number]["value"];
