/** Segments that follow `/kvartiry/` and are not a city slug. */
const APARTMENT_CATALOG_TAIL = new Set([
  "1-komnatnye",
  "2-komnatnye",
  "3-komnatnye",
  "4-komnatnye",
  "vozle-metro",
  "metro",
  "raion",
  "mikroraion",
  "zhiloy-kompleks",
  "vozle",
]);

export const MAIN_APARTMENT_CITY_LABELS: Record<string, string> = {
  minsk: "Минск",
  brest: "Брест",
  vitebsk: "Витебск",
  gomel: "Гомель",
  grodno: "Гродно",
  mogilev: "Могилёв",
};

/**
 * City slug encoded in an apartment catalog path.
 * Returns null when the path is not an apartment catalog (home, listing, articles).
 */
export function apartmentCitySlugFromPathname(pathname: string): string | null {
  const segments = pathname.split("/").filter(Boolean);
  const index = segments.indexOf("kvartiry");
  if (index === -1) {
    return null;
  }

  const next = segments[index + 1];
  if (next && !APARTMENT_CATALOG_TAIL.has(next)) {
    return next;
  }

  const previous = segments[index - 1];
  if (previous) {
    return previous;
  }

  return "minsk";
}

export function isAllBelarusHousesPath(pathname: string): boolean {
  return pathname.split("/").filter(Boolean)[0] === "usadby-v-belarusi";
}
