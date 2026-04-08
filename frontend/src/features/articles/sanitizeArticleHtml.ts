import sanitizeHtml from "sanitize-html";
import {
  decodeHtmlEntitiesDeep,
  hasEntityEncodedTags,
  hasRawHtmlTags,
} from "./articleHtmlUtils";

const articleSanitizeOptions: sanitizeHtml.IOptions = {
  allowedTags: [...sanitizeHtml.defaults.allowedTags, "img"],
  allowedAttributes: {
    ...sanitizeHtml.defaults.allowedAttributes,
    a: ["href", "name", "target", "rel"],
    div: ["class"],
    h2: ["class"],
    p: ["class"],
  },
};

/** Sanitize article HTML on the server; returns null when content should be rendered as plain text. */
export function sanitizeArticleHtml(content: string): string | null {
  const trimmed = content.trim();
  if (!trimmed) {
    return null;
  }

  let toSanitize = trimmed;
  if (hasEntityEncodedTags(trimmed)) {
    toSanitize = decodeHtmlEntitiesDeep(trimmed);
  }

  if (!hasRawHtmlTags(toSanitize)) {
    return null;
  }

  return sanitizeHtml(toSanitize, articleSanitizeOptions);
}
