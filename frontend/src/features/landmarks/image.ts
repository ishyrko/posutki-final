/** Landmark image stored as filename or /uploads/landmarks/... path. */
export function resolveLandmarkImageUrl(imageUrl: string | null | undefined): string | null {
  if (!imageUrl) {
    return null;
  }

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
