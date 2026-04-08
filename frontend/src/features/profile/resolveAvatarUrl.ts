/**
 * Приводит значение avatar из API/БД к абсолютному URL для <img>.
 * В БД часто хранится путь относительно public/uploads без ведущего слэша
 * (EasyAdmin и др.) — без нормализации браузер резолвит его относительно текущего
 * маршрута (/kabinet/profil/...) и картинка не находится.
 */
export function resolveUserAvatarUrl(avatar: string | null | undefined): string | null {
    if (avatar == null) {
        return null;
    }
    const s = avatar.trim();
    if (s === '') {
        return null;
    }
    if (s.startsWith('http://') || s.startsWith('https://')) {
        return s;
    }
    if (s.startsWith('//')) {
        return s;
    }
    if (s.startsWith('/')) {
        return s;
    }
    const withoutDuplicateUploads = s.replace(/^uploads\//, '');
    return `/uploads/${withoutDuplicateUploads}`;
}
