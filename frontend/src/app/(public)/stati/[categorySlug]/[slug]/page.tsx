import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { cache } from "react";
import { Article } from "@/features/articles/types";
import { ARTICLE_FALLBACK_IMAGE } from "@/features/articles/articleCardDisplay";
import { resolveArticleImageUrl } from "@/features/articles/image";
import { buildOpenGraphMeta } from "@/lib/seo/open-graph";
import { fetchPublicApi, fetchPublicApiNullable } from "@/lib/server-api";
import { sanitizeArticleHtml } from "@/features/articles/sanitizeArticleHtml";
import { JsonLdScript } from "@/lib/json-ld/json-ld-script";
import { buildArticleJsonLd } from "@/lib/json-ld/article";
import ArticleContentClient from "./ArticleContentClient";
import { PageBreadcrumbs } from "@/components/PageBreadcrumbs";
import {
  buildArticleBreadcrumbTrail,
  buildBreadcrumbJsonLd,
} from "@/lib/breadcrumbs";

/** Fully static; cache invalidated via on-demand revalidation from admin. */
export const revalidate = false;

/** Paths not in the build still render on first request (on-demand ISR). */
export const dynamicParams = true;

type ArticlePageParams = {
  categorySlug: string;
  slug: string;
};

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

  const articleTitle = `${article.title} | Посутки.by`;
  const articleDescription = article.excerpt || `Статья о недвижимости: ${article.title}`;
  const articlePath = `/stati/${article.categorySlug}/${article.slug}/`;
  const coverImage = resolveArticleImageUrl(article.coverImage) || ARTICLE_FALLBACK_IMAGE;

  return {
    title: articleTitle,
    description: articleDescription,
    alternates: {
      canonical: articlePath,
    },
    ...buildOpenGraphMeta({
      title: article.title,
      description: articleDescription,
      path: articlePath,
      images: [{ url: coverImage }],
      type: "article",
    }),
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
  const breadcrumbs = buildArticleBreadcrumbTrail(article);

  return (
    <>
      <JsonLdScript data={buildArticleJsonLd(article)} />
      <JsonLdScript
        data={buildBreadcrumbJsonLd(
          breadcrumbs,
          `/stati/${article.categorySlug}/${article.slug}/`,
        )}
      />
      <ArticleContentClient article={article} sanitizedHtml={sanitizedHtml}>
        <PageBreadcrumbs items={breadcrumbs} />
      </ArticleContentClient>
    </>
  );
}
