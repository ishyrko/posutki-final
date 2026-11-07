import {
  Building2,
  Castle,
  Factory,
  Landmark,
  TreePine,
  Waves,
  type LucideIcon,
} from "lucide-react";

export interface HouseRegion {
  slug: string;
  /** Full label, e.g. «Минская область». */
  name: string;
  /** First line on the home region card, e.g. «Минская». */
  shortName: string;
  icon: LucideIcon;
}

/** Six oblasts for house catalogs. Minsk oblast catalog lives at `/usadby/`. */
export const HOUSE_REGIONS: readonly HouseRegion[] = [
  { slug: "minsk", name: "Минская область", shortName: "Минская", icon: Building2 },
  { slug: "brest", name: "Брестская область", shortName: "Брестская", icon: Landmark },
  { slug: "vitebsk", name: "Витебская область", shortName: "Витебская", icon: Waves },
  { slug: "grodno", name: "Гродненская область", shortName: "Гродненская", icon: Castle },
  { slug: "gomel", name: "Гомельская область", shortName: "Гомельская", icon: Factory },
  { slug: "mogilev", name: "Могилёвская область", shortName: "Могилёвская", icon: TreePine },
];

export const HOUSE_REGION_ICON_BY_SLUG: Record<string, LucideIcon> = Object.fromEntries(
  HOUSE_REGIONS.map((region) => [region.slug, region.icon]),
);

export const ALL_BELARUS_HOUSES_LABEL = "Вся Беларусь";
