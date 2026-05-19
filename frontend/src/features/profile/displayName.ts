export type UserNameFields = {
    firstName?: string | null;
    lastName?: string | null;
};

/** Отображаемое имя из полей пользователя (firstName + lastName в API). */
export function formatUserDisplayName(user?: UserNameFields | null): string {
    if (!user) return '';
    return [user.firstName, user.lastName]
        .map((part) => part?.trim())
        .filter((part): part is string => Boolean(part))
        .join(' ');
}

/** Инициалы для аватара: до 3 букв по словам ФИО, для одного слова — первые 2 буквы. */
export function getUserInitials(user?: UserNameFields | null): string {
    const displayName = formatUserDisplayName(user);
    const parts = displayName.split(/\s+/).filter(Boolean);
    if (parts.length === 0) {
        return '';
    }
    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }
    return parts
        .slice(0, 3)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
}
