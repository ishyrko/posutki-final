function normalizeArticleUploadUrl(imageUrl: string): string | null {
    if (imageUrl.includes("://") || imageUrl.startsWith("//")) {
        return imageUrl;
    }

    if (imageUrl.startsWith("/uploads/")) {
        return imageUrl;
    }

    if (!imageUrl.startsWith("/")) {
        return `/uploads/${imageUrl.replace(/^uploads\//, "")}`;
    }

    return null;
}

/** Full-size cover image for article detail pages. */
export function resolveArticleImageUrl(imageUrl: string | null): string | null {
    if (!imageUrl) {
        return null;
    }

    const normalizedUrl = normalizeArticleUploadUrl(imageUrl);
    if (!normalizedUrl) {
        return null;
    }

    if (normalizedUrl.includes("://") || normalizedUrl.startsWith("//")) {
        return normalizedUrl;
    }

    if (normalizedUrl.includes("/thumbs/")) {
        return normalizedUrl.replace("/thumbs/", "/");
    }

    return normalizedUrl;
}

export function resolveArticleThumbnailUrl(imageUrl: string | null): string | null {
    if (!imageUrl) {
        return null;
    }

    const normalizedUrl = normalizeArticleUploadUrl(imageUrl);
    if (!normalizedUrl) {
        return null;
    }

    if (normalizedUrl.includes("://") || normalizedUrl.startsWith("//")) {
        return normalizedUrl;
    }

    if (normalizedUrl.includes("/thumbs/")) {
        return normalizedUrl;
    }

    const relativePath = normalizedUrl.replace(/^\/uploads\//, "");
    if (!relativePath || relativePath === normalizedUrl) {
        return normalizedUrl;
    }

    const segments = relativePath.split("/");
    const baseName = segments.pop();
    if (!baseName) {
        return normalizedUrl;
    }

    if (segments.length === 0) {
        // Backward compatibility for old files in /uploads/<file>
        return `/uploads/thumbs/${baseName}`;
    }

    return `/uploads/${segments.join("/")}/thumbs/${baseName}`;
}
