export interface MetroStation {
  id: number;
  cityId: number;
  name: string;
  slug: string;
  line: number;
  order: number;
  latitude: number | null;
  longitude: number | null;
}

export interface NearbyMetroStation {
  id: number;
  name: string;
  slug: string;
  line: number;
  distanceKm: number;
}
