/** Публичный origin сайта для canonical, Open Graph и sitemap. */
export function getSiteOrigin(): string {
  const raw = process.env.NEXT_PUBLIC_SITE_URL?.trim() || "https://posutki.by";
  return raw.replace(/\/$/, "");
}
