export const YANDEX_METRIKA_COUNTER_ID = 109330583;

type YmParams = Record<string, string | number | boolean | undefined>;

declare global {
    interface Window {
        ym?: (counterId: number, method: string, ...args: unknown[]) => void;
    }
}

function cleanParams(params?: YmParams): Record<string, string | number | boolean> | undefined {
    if (!params) return undefined;
    const cleaned: Record<string, string | number | boolean> = {};
    for (const [key, value] of Object.entries(params)) {
        if (value !== undefined) {
            cleaned[key] = value;
        }
    }
    return Object.keys(cleaned).length > 0 ? cleaned : undefined;
}

export function trackYmGoal(goalName: string, params?: YmParams): void {
    if (typeof window === 'undefined' || typeof window.ym !== 'function') {
        return;
    }

    const cleaned = cleanParams(params);
    if (cleaned) {
        window.ym(YANDEX_METRIKA_COUNTER_ID, 'reachGoal', goalName, cleaned);
    } else {
        window.ym(YANDEX_METRIKA_COUNTER_ID, 'reachGoal', goalName);
    }
}
