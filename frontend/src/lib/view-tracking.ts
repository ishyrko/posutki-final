const VISITOR_ID_KEY = 'posutki_vuid';

export function getOrCreateVisitorId(): string {
    if (typeof window === 'undefined') {
        return '';
    }

    let visitorId = window.localStorage.getItem(VISITOR_ID_KEY);
    if (!visitorId) {
        visitorId = crypto.randomUUID();
        window.localStorage.setItem(VISITOR_ID_KEY, visitorId);
    }

    return visitorId;
}

export function hasViewedInSession(sessionKey: string): boolean {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.sessionStorage.getItem(`viewed:${sessionKey}`) === '1';
}

export function markViewedInSession(sessionKey: string): void {
    if (typeof window === 'undefined') {
        return;
    }

    window.sessionStorage.setItem(`viewed:${sessionKey}`, '1');
}

export type TrackViewResult = {
    views: number;
    counted: boolean;
};

export async function trackViewOnce(
    sessionKey: string,
    track: (visitorId: string) => Promise<TrackViewResult>,
): Promise<TrackViewResult | null> {
    if (hasViewedInSession(sessionKey)) {
        return null;
    }

    const result = await track(getOrCreateVisitorId());
    markViewedInSession(sessionKey);
    return result;
}
