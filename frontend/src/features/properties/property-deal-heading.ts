/** Именительный падеж: «Квартира на сутки», «Дом на сутки». */
const PROPERTY_TYPE_NOMINATIVE_DAILY: Record<string, string> = {
  apartment: 'Квартира',
  house: 'Дом',
};

/**
 * Подзаголовок карточки: только посуточная аренда квартир и домов.
 */
export function formatPropertyDealHeading(dealType: string, propertyType: string): string {
  if (dealType === 'daily') {
    const phrase = PROPERTY_TYPE_NOMINATIVE_DAILY[propertyType];
    if (phrase) return `${phrase} на сутки`;
  }
  return 'Посуточная аренда';
}
