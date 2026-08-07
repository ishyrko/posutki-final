/** Landmark image stored as filename or /uploads/landmarks/... path. */
function normalizeLandmarkUploadUrl(imageUrl: string): string | null {
  if (imageUrl.includes("://") || imageUrl.startsWith("//")) {
    return imageUrl;
  }

  if (imageUrl.startsWith("/uploads/")) {
    return imageUrl;
  }

  if (!imageUrl.startsWith("/")) {
    const cleaned = imageUrl.replace(/^(?:uploads\/)?landmarks\//, "");
    return `/uploads/landmarks/${cleaned}`;
  }

  return imageUrl;
}

/** Full-size landmark image (hero / Open Graph). */
export function resolveLandmarkImageUrl(imageUrl: string | null | undefined): string | null {
  if (!imageUrl) {
    return null;
  }

  const normalizedUrl = normalizeLandmarkUploadUrl(imageUrl);
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

/** Thumbnail under /uploads/landmarks/thumbs/… for cards and nearby lists. */
export function resolveLandmarkThumbnailUrl(imageUrl: string | null | undefined): string | null {
  if (!imageUrl) {
    return null;
  }

  const normalizedUrl = normalizeLandmarkUploadUrl(imageUrl);
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
    return `/uploads/thumbs/${baseName}`;
  }

  return `/uploads/${segments.join("/")}/thumbs/${baseName}`;
}
