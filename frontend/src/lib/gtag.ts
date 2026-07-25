type GtagCommand = 'config' | 'set' | 'event' | 'js';

type GtagParams = Record<string, string | number | boolean | undefined>;

declare global {
    interface Window {
        gtag?: (command: GtagCommand, targetId: string | Date, params?: GtagParams) => void;
        dataLayer?: unknown[];
    }
}

function cleanParams(params?: GtagParams): GtagParams | undefined {
    if (!params) return undefined;
    const cleaned: GtagParams = {};
    for (const [key, value] of Object.entries(params)) {
        if (value !== undefined) {
            cleaned[key] = value;
        }
    }
    return Object.keys(cleaned).length > 0 ? cleaned : undefined;
}

export function trackGaEvent(eventName: string, params?: GtagParams): void {
    if (typeof window === 'undefined' || typeof window.gtag !== 'function') {
        return;
    }

    window.gtag('event', eventName, cleanParams(params));
}

export type PropertyContactEvent =
    | 'show_phone'
    | 'click_phone'
    | 'click_whatsapp'
    | 'click_viber'
    | 'click_telegram';

type PropertyContactContext = {
    id: number;
    type: string;
    address?: { cityName?: string };
};

export function trackPropertyContactEvent(
    eventName: PropertyContactEvent,
    property: PropertyContactContext,
): void {
    trackGaEvent(eventName, {
        property_id: property.id,
        city: property.address?.cityName,
        property_type: property.type,
    });
}
