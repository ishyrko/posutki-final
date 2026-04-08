import type { Article } from "./types";
import { estimateArticleReadMinutes } from "./articleHtmlUtils";
import { resolveArticleThumbnailUrl } from "./image";

export const ARTICLE_FALLBACK_IMAGE = "/rnb-logo.png";

export function formatArticleDate(dateStr: string): string {
  const date = new Date(dateStr);
  const months = [
    "янв",
    "фев",
    "мар",
    "апр",
    "мая",
    "июн",
    "июл",
    "авг",
    "сен",
    "окт",
    "ноя",
    "дек",
  ];
  return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
}

export function estimateArticleReadTime(content: string): string {
  return `${estimateArticleReadMinutes(content)} мин`;
}

export type ArticleCardData = {
  slug?: string;
  categorySlug?: string;
  image: string;
  category: string;
  title: string;
  excerpt: string;
  date: string;
  readTime: string;
};

export function articleToCardData(article: Article): ArticleCardData {
  return {
    slug: article.slug,
    categorySlug: article.categorySlug || undefined,
    image: resolveArticleThumbnailUrl(article.coverImage) || ARTICLE_FALLBACK_IMAGE,
    category: article.categoryName || "Без категории",
    title: article.title,
    excerpt: article.excerpt,
    date: formatArticleDate(article.publishedAt || article.createdAt),
    readTime: estimateArticleReadTime(article.content),
  };
}
