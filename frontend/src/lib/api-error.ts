type ApiErrorBody = {
    message?: unknown;
    error?: {
        message?: unknown;
        code?: unknown;
        violations?: Record<string, unknown>;
    };
};

const TECHNICAL_MESSAGE_PREFIXES = ['Внутренняя ошибка сервера'];

function isTechnicalMessage(message: string, status?: number): boolean {
    if (status !== undefined && status >= 500) {
        return true;
    }

    if (message === 'Некорректные данные запроса') {
        return true;
    }

    return TECHNICAL_MESSAGE_PREFIXES.some((prefix) => message.startsWith(prefix));
}

function collectViolationMessages(violations: Record<string, unknown>): string[] {
    const messages: string[] = [];

    for (const value of Object.values(violations)) {
        if (typeof value === 'string' && value.trim() !== '') {
            messages.push(value.trim());
        }
    }

    return [...new Set(messages)];
}

export function getApiValidationErrors(error: unknown): Record<string, string> | null {
    if (typeof error !== 'object' || error === null || !('response' in error)) {
        return null;
    }

    const response = (error as { response?: { data?: unknown } }).response;
    const data = response?.data;
    if (typeof data !== 'object' || data === null) {
        return null;
    }

    const violations = (data as ApiErrorBody).error?.violations;
    if (!violations || typeof violations !== 'object') {
        return null;
    }

    const result: Record<string, string> = {};
    for (const [field, value] of Object.entries(violations)) {
        if (typeof value === 'string' && value.trim() !== '') {
            result[field] = value.trim();
        }
    }

    return Object.keys(result).length > 0 ? result : null;
}

export function getApiErrorMessage(error: unknown, fallback: string): string {
    if (typeof error !== 'object' || error === null || !('response' in error)) {
        return fallback;
    }

    const response = (error as { response?: { status?: number; data?: unknown } }).response;
    const status = response?.status;
    const data = response?.data;
    if (typeof data !== 'object' || data === null) {
        return fallback;
    }

    const body = data as ApiErrorBody;
    const violations = body.error?.violations;
    if (violations && typeof violations === 'object') {
        const messages = collectViolationMessages(violations);
        if (messages.length > 0) {
            return messages.join('. ');
        }
    }

    const candidates = [
        typeof body.message === 'string' ? body.message : null,
        typeof body.error?.message === 'string' ? body.error.message : null,
    ].filter((message): message is string => message !== null && message.length > 0);

    for (const message of candidates) {
        if (message === 'Ошибка валидации') {
            continue;
        }
        if (isTechnicalMessage(message, status)) {
            continue;
        }
        return message;
    }

    return fallback;
}
