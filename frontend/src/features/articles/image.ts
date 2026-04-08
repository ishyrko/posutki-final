export function resolveArticleThumbnailUrl(imageUrl: string | null): string | null {
    if (!imageUrl) {
        return null;
    }

    const normalizedUrl = imageUrl.startsWith('/uploads/')
        ? imageUrl
        : (!imageUrl.includes('://') && !imageUrl.startsWith('/'))
            ? `/uploads/${imageUrl}`
            : null;

    if (!normalizedUrl) {
        return null;
    }

    if (normalizedUrl.includes('/thumbs/')) {
        return normalizedUrl;
    }

    const relativePath = normalizedUrl.replace(/^\/uploads\//, '');
    if (!relativePath || relativePath === normalizedUrl) {
        return normalizedUrl;
    }

    const segments = relativePath.split('/');
    const baseName = segments.pop();
    if (!baseName) {
        return normalizedUrl;
    }

    if (segments.length === 0) {
        // Backward compatibility for old files in /uploads/<file>
        return `/uploads/thumbs/${baseName}`;
    }

    return `/uploads/${segments.join('/')}/thumbs/${baseName}`;
}

