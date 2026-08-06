export function formatLandmarkDistance(distanceKm: number): string {
  if (distanceKm < 1) {
    return `${Math.round(distanceKm * 1000)} м`;
  }

  return `${distanceKm.toFixed(1)} км`;
}

export const DEFAULT_LANDMARK_MAX_DISTANCE_KM = 2;

export const LANDMARK_DISTANCE_FILTER_OPTIONS = [
  { label: "до 0.5 км", value: 0.5 },
  { label: "до 1 км", value: 1 },
  { label: "до 1.5 км", value: 1.5 },
  { label: "до 2 км", value: 2 },
] as const;

export type LandmarkDistanceFilterValue =
  (typeof LANDMARK_DISTANCE_FILTER_OPTIONS)[number]["value"];
