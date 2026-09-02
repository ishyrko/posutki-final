import type { Metadata } from "next";
import { Suspense } from "react";
import { Article, ArticleCategory } from "@/features/articles/types";
import { fetchPublicApi } from "@/lib/server-api";
import ArticlesPageClient from "./ArticlesPageClient";
import { PageBreadcrumbs } from "@/components/PageBreadcrumbs";
import { buildArticlesIndexBreadcrumbTrail, buildBreadcrumbJsonLd } from "@/lib/breadcrumbs";
import { JsonLdScript } from "@/lib/json-ld/json-ld-script";
import { buildPageMetadata } from "@/lib/seo/open-graph";

export const revalidate = false;

function ArticlesPageFallback() {
  return (
    <div className="min-h-screen bg-background pt-10 pb-16 flex items-center justify-center">
      <div
        className="h-9 w-9 animate-spin rounded-full border-2 border-primary border-t-transparent"
        role="status"
        aria-label="Загрузка"
      />
    </div>
  );
}

export const metadata: Metadata = buildPageMetadata({
  title: "Статьи и аналитика | Посутки.by",
  description:
    "Экспертные материалы о рынке недвижимости, советы покупателям и продавцам на Posutki.by.",
  path: "/stati/",
});

export default async function ArticlesPage() {
  const [categoriesResult, articlesResult] = await Promise.allSettled([
    fetchPublicApi<ArticleCategory[]>("/article-categories", {
      cache: "force-cache",
      next: { tags: ["articles"] },
    }),
    fetchPublicApi<Article[]>("/articles?limit=100", {
      cache: "force-cache",
      next: { tags: ["articles"] },
    }),
  ]);

  const categories = categoriesResult.status === "fulfilled" ? categoriesResult.value : [];
  const articles = articlesResult.status === "fulfilled" ? articlesResult.value : [];
  const breadcrumbs = buildArticlesIndexBreadcrumbTrail();

  return (
    <Suspense fallback={<ArticlesPageFallback />}>
      <JsonLdScript data={buildBreadcrumbJsonLd(breadcrumbs, "/stati/")} />
      <ArticlesPageClient categories={categories} articles={articles}>
        <PageBreadcrumbs items={breadcrumbs} />
      </ArticlesPageClient>
    </Suspense>
  );
}
