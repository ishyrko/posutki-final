/** Digits only for wa.me; normalizes Belarus 80… to 375…. */
export function phoneDigitsOnly(phone: string): string {
    let digits = phone.replace(/\D/g, '');
    if (digits.startsWith('80') && digits.length >= 11) {
        digits = `375${digits.slice(2)}`;
    }
    return digits;
}

export function viberChatHref(phone: string): string {
    const digits = phoneDigitsOnly(phone);
    return `viber://chat?number=+${digits}`;
}

export function whatsAppHref(phone: string): string {
    return `https://wa.me/${phoneDigitsOnly(phone)}`;
}

export function telegramHref(username: string): string {
    const handle = username.replace(/^@/, '').trim();
    const normalized = handle.match(/^(?:https?:\/\/)?(?:www\.)?(?:t\.me|telegram\.me)\/([a-zA-Z0-9_]+)/i)?.[1] ?? handle;
    return `https://t.me/${encodeURIComponent(normalized)}`;
}
