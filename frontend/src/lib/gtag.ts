import { trackYmGoal } from '@/lib/metrika';

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
    const cleaned = cleanParams(params);

    if (typeof window !== 'undefined' && typeof window.gtag === 'function') {
        window.gtag('event', eventName, cleaned);
    }

    trackYmGoal(eventName, cleaned);
}

export type PropertyContactEvent =
    | 'show_phone'
    | 'click_phone'
    | 'click_whatsapp'
    | 'click_viber'
    | 'click_telegram';

export type PropertyEngagementEvent =
    | 'send_owner_message'
    | 'submit_booking_inquiry';

type PropertyAnalyticsContext = {
    id: number;
    type: string;
    address?: { cityName?: string };
};

function trackPropertyEvent(eventName: string, property: PropertyAnalyticsContext): void {
    trackGaEvent(eventName, {
        property_id: property.id,
        city: property.address?.cityName,
        property_type: property.type,
    });
}

export function trackPropertyContactEvent(
    eventName: PropertyContactEvent,
    property: PropertyAnalyticsContext,
): void {
    trackPropertyEvent(eventName, property);
}

export function trackPropertyEngagementEvent(
    eventName: PropertyEngagementEvent,
    property: PropertyAnalyticsContext,
): void {
    trackPropertyEvent(eventName, property);
}
