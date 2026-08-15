const LANDMARK_CATEGORY_LABELS: Record<string, string> = {
  sight: "Достопримечательность",
  station: "Вокзал / транспорт",
  stadium: "Стадион / арена",
  park: "Парк",
  mall: "Торговый центр",
  aquapark: "Аквапарк",
};

export function resolveLandmarkCategoryLabel(category?: string | null): string | null {
  if (!category) {
    return null;
  }

  return LANDMARK_CATEGORY_LABELS[category] ?? null;
}
