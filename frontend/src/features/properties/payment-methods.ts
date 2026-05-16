export const PAYMENT_METHOD_OPTIONS = [
    { id: 'cash', label: 'Наличными' },
    { id: 'card', label: 'Банковской картой' },
    { id: 'bank_transfer', label: 'Безналичным платежом' },
] as const;

export type PaymentMethodId = (typeof PAYMENT_METHOD_OPTIONS)[number]['id'];

export const PAYMENT_METHOD_LABELS: Record<PaymentMethodId, string> = Object.fromEntries(
    PAYMENT_METHOD_OPTIONS.map((o) => [o.id, o.label]),
) as Record<PaymentMethodId, string>;
