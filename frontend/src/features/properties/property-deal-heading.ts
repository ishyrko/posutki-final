/** Именительный падеж для карточек и подзаголовков («Квартира», «Усадьба»). */
export const PROPERTY_TYPE_NOMINATIVE_DAILY: Record<string, string> = {
  apartment: 'Квартира',
  house: 'Усадьба',
};

/**
 * Подзаголовок карточки: только посуточная аренда квартир и усадеб.
 */
export function formatPropertyDealHeading(dealType: string, propertyType: string): string {
  if (dealType === 'daily') {
    const phrase = PROPERTY_TYPE_NOMINATIVE_DAILY[propertyType];
    if (phrase) return `${phrase} на сутки`;
  }
  return 'Посуточная аренда';
}
