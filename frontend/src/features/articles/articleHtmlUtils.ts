/** Raw tags like `<p>`, `<div>`. */
export function hasRawHtmlTags(s: string): boolean {
  return /<\/?[a-z][\s\S]*>/i.test(s.trim());
}

/** Escaped tags stored as text, e.g. `&lt;h2&gt;` inside a `<p>`. */
export function hasEntityEncodedTags(s: string): boolean {
  return /&lt;[a-z][\s\S]*?&gt;/i.test(s) || /&lt;\/[a-z]/i.test(s);
}

/**
 * Decodes common named entities iteratively (handles `&amp;lt;` → `&lt;` → `<`).
 */
export function decodeHtmlEntitiesDeep(s: string): string {
  let prev = "";
  let out = s;
  while (out !== prev) {
    prev = out;
    out = out
      .replace(/&#(\d+);/g, (_, code) => String.fromCharCode(Number(code)))
      .replace(/&#x([0-9a-f]+);/gi, (_, hex) => String.fromCharCode(parseInt(hex, 16)))
      .replace(/&lt;/gi, "<")
      .replace(/&gt;/gi, ">")
      .replace(/&quot;/g, '"')
      .replace(/&#39;/g, "'")
      .replace(/&amp;/g, "&");
  }
  return out;
}

/** Content that should be rendered as HTML (raw or entity-encoded markup). */
export function normalizeArticleHtmlForDetection(content: string): string {
  const t = content.trim();
  if (!t) {
    return t;
  }
  if (hasEntityEncodedTags(t)) {
    return decodeHtmlEntitiesDeep(t);
  }
  return t;
}

function stripHtmlForWordCount(s: string): string {
  return s.replace(/<[^>]+>/g, " ").replace(/\s+/g, " ").trim();
}

/** ~200 words/min; strips tags when content is HTML so minified markup still counts correctly. */
export function estimateArticleReadMinutes(content: string): number {
  const normalized = normalizeArticleHtmlForDetection(content);
  const plain = hasRawHtmlTags(normalized)
    ? stripHtmlForWordCount(normalized)
    : content;
  const words = plain.split(/\s+/).filter(Boolean).length;
  return Math.max(1, Math.round(words / 200));
}
