const TELEGRAM_URL_RE = /^(?:https?:\/\/)?(?:www\.)?(?:t\.me|telegram\.me)\/([a-zA-Z0-9_]+)/i;

export function extractTelegramUsername(raw: string): string {
    const trimmed = raw.trim();
    if (trimmed === '') {
        return '';
    }

    const urlMatch = trimmed.match(TELEGRAM_URL_RE);
    if (urlMatch) {
        return urlMatch[1];
    }

    if (trimmed.startsWith('@')) {
        return trimmed.slice(1);
    }

    return trimmed;
}

export function getTelegramUsernameError(raw: string): string | null {
    const trimmed = raw.trim();
    if (trimmed === '') {
        return null;
    }

    const username = extractTelegramUsername(trimmed);
    if (username.length < 5 || username.length > 32) {
        return 'Ник Telegram: от 5 до 32 символов';
    }

    if (!/^[a-zA-Z][a-zA-Z0-9_]*$/.test(username)) {
        return 'Ник Telegram: только латиница, цифры и подчёркивание, начинается с буквы';
    }

    if (username.endsWith('_')) {
        return 'Ник Telegram не может заканчиваться на подчёркивание';
    }

    return null;
}

export function normalizeTelegramUsername(raw: string | undefined | null): string | null {
    if (raw === undefined || raw === null) {
        return null;
    }

    const trimmed = raw.trim();
    if (trimmed === '') {
        return null;
    }

    const error = getTelegramUsernameError(trimmed);
    if (error) {
        return null;
    }

    return extractTelegramUsername(trimmed);
}
