/** Именительный падеж для карточек и подзаголовков («Квартира», «Усадьба»). */
export const PROPERTY_TYPE_NOMINATIVE_DAILY: Record<string, string> = {
  apartment: 'Квартира',
  house: 'Усадьба',
};

/**
 * Подзаголовок карточки: только посуточная аренда квартир и усадеб.
 */
export function formatPropertyDealHeading(_dealType: string, propertyType: string): string {
  const phrase = PROPERTY_TYPE_NOMINATIVE_DAILY[propertyType];
  if (phrase) return `${phrase} на сутки`;
  return 'Посуточная аренда';
}
