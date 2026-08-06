export interface LandmarkFact {
  label: string;
  value: string;
}

export interface LandmarkNearestMetro {
  name: string;
  slug: string;
  distanceKm: number;
}

export interface LandmarkListItem {
  id: number;
  name: string;
  nameGenitive?: string;
  slug: string;
  category?: string | null;
}

export interface Landmark extends LandmarkListItem {
  nameGenitive: string;
  shortDescription?: string | null;
  description?: string | null;
  imageUrl?: string | null;
  address?: string | null;
  facts?: LandmarkFact[] | null;
  guestTips?: string[] | null;
  nearestMetro?: LandmarkNearestMetro | null;
  latitude?: number | null;
  longitude?: number | null;
  radiusKm: number;
}
