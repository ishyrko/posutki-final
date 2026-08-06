export interface LandmarkListItem {
  id: number;
  name: string;
  slug: string;
  category?: string | null;
  catalogLocationPhrase?: string | null;
}

export interface Landmark extends LandmarkListItem {
  shortDescription?: string | null;
  description?: string | null;
  imageUrl?: string | null;
  metaTitle?: string | null;
  metaDescription?: string | null;
  latitude: number;
  longitude: number;
  radiusKm: number;
}
