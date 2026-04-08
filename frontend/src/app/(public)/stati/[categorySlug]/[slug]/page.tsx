import type { Metadata } from "next";
import { notFound, redirect } from "next/navigation";
import { cache } from "react";
import { Article } from "@/features/articles/types";
import { resolveArticleThumbnailUrl } from "@/features/articles/image";
import { fetchPublicApi, fetchPublicApiNullable } from "@/lib/server-api";
import { sanitizeArticleHtml } from "@/features/articles/sanitizeArticleHtml";
import ArticleContentClient from "./ArticleContentClient";

/** Fully static; cache invalidated via on-demand revalidation from admin. */
export const revalidate = false;

/** Paths not in the build still render on first request (on-demand ISR). */
export const dynamicParams = true;

type ArticlePageParams = {
  categorySlug: string;
  slug: string;
};

const FALLBACK_IMAGE = "/rnb-logo.png";

const getArticleBySlug = cache(async (slug: string): Promise<Article | null> => {
  return fetchPublicApiNullable<Article>(`/articles/${encodeURIComponent(slug)}`, {
    cache: "force-cache",
    next: {
      tags: ["articles", `article-${slug}`],
    },
  });
});

export async function generateStaticParams() {
  try {
    const articles = await fetchPublicApi<Article[]>("/articles?limit=1000", {
      cache: "force-cache",
      next: { tags: ["articles"] },
    });

    return articles
      .filter((article) => Boolean(article.slug && article.categorySlug))
      .map((article) => ({
        categorySlug: article.categorySlug as string,
        slug: article.slug,
      }));
  } catch {
    return [];
  }
}

function categorySlugMatchesUrl(
  apiSlug: string | null | undefined,
  urlSegment: string,
): boolean {
  if (apiSlug == null || apiSlug === "") {
    return false;
  }
  try {
    return decodeURIComponent(apiSlug.trim()) === decodeURIComponent(urlSegment.trim());
  } catch {
    return apiSlug.trim() === urlSegment.trim();
  }
}

export async function generateMetadata({
  params,
}: {
  params: Promise<ArticlePageParams>;
}): Promise<Metadata> {
  const { slug, categorySlug } = await params;
  const article = await getArticleBySlug(slug);

  if (!article || !categorySlugMatchesUrl(article.categorySlug, categorySlug)) {
    notFound();
  }

  return {
    title: `${article.title} | RNB.by`,
    description: article.excerpt || `Статья о недвижимости: ${article.title}`,
    openGraph: {
      title: article.title,
      description: article.excerpt || `Статья о недвижимости: ${article.title}`,
      images: [{ url: resolveArticleThumbnailUrl(article.coverImage) || FALLBACK_IMAGE }],
      type: "article",
    },
  };
}

export default async function ArticlePage({
  params,
}: {
  params: Promise<ArticlePageParams>;
}) {
  const { slug, categorySlug } = await params;
  const article = await getArticleBySlug(slug);

  if (!article || !categorySlugMatchesUrl(article.categorySlug, categorySlug)) {
    notFound();
  }

  const content = typeof article.content === "string" ? article.content : "";
  const sanitizedHtml = sanitizeArticleHtml(content);

  return <ArticleContentClient article={article} sanitizedHtml={sanitizedHtml} />;
}
