import type { FaqItem } from "@/lib/json-ld/faq";

export type CityPlaceType = "microdistrict" | "residential_complex";

export interface CityPlace {
  id: number;
  type: CityPlaceType;
  name: string;
  slug: string;
  namePrepositional?: string | null;
}

export interface CatalogPlaceDetail {
  id: number;
  name: string;
  slug: string;
  officialName?: string | null;
  namePrepositional?: string | null;
  catalogSeoVisible?: boolean;
  catalogSeoText?: string | null;
  faq?: FaqItem[];
}
