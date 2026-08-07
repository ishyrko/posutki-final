import type { Article } from "@/features/articles/types";
import { ARTICLE_FALLBACK_IMAGE } from "@/features/articles/articleCardDisplay";
import { resolveArticleImageUrl } from "@/features/articles/image";
import { getSiteOrigin } from "@/lib/site-url";

function toAbsoluteUrl(path: string): string {
  const origin = getSiteOrigin();
  if (path.startsWith("http")) return path;
  return `${origin}${path.startsWith("/") ? path : `/${path}`}`;
}

export function buildArticleJsonLd(article: Article): Record<string, unknown> {
  const origin = getSiteOrigin();
  const articlePath = `/stati/${article.categorySlug}/${article.slug}/`;
  const url = toAbsoluteUrl(articlePath);
  const imagePath = resolveArticleImageUrl(article.coverImage) ?? ARTICLE_FALLBACK_IMAGE;
  const image = toAbsoluteUrl(imagePath);

  return {
    "@context": "https://schema.org",
    "@type": "Article",
    "@id": url,
    headline: article.title,
    description: article.excerpt || `Статья о недвижимости: ${article.title}`,
    url,
    image,
    datePublished: article.publishedAt ?? article.createdAt,
    dateModified: article.updatedAt,
    inLanguage: "ru-BY",
    author: {
      "@type": "Organization",
      name: "Posutki.by",
      url: `${origin}/`,
    },
    publisher: {
      "@type": "Organization",
      name: "Posutki.by",
      logo: {
        "@type": "ImageObject",
        url: `${origin}/brand/logo.png`,
      },
    },
    mainEntityOfPage: {
      "@type": "WebPage",
      "@id": url,
    },
  };
}
