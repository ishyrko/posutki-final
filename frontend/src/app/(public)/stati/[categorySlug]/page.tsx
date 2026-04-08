import Link from "next/link";
import type { Metadata } from "next";
import { Suspense } from "react";
import { Button } from "@/components/ui/button";
import { Article, ArticleCategory } from "@/features/articles/types";
import { fetchPublicApi } from "@/lib/server-api";
import CategoryPageClient from "../category/[slug]/CategoryPageClient";

export const revalidate = false;

function CategoryArticlesPageFallback() {
  return (
    <div className="min-h-screen bg-background pt-24 pb-16 flex items-center justify-center">
      <div
        className="h-9 w-9 animate-spin rounded-full border-2 border-primary border-t-transparent"
        role="status"
        aria-label="Загрузка"
      />
    </div>
  );
}

type CategoryPageParams = {
  categorySlug: string;
};

export async function generateMetadata({
  params,
}: {
  params: Promise<CategoryPageParams>;
}): Promise<Metadata> {
  try {
    const { categorySlug } = await params;
    const category = await fetchPublicApi<ArticleCategory>(`/article-categories/${categorySlug}`, {
      cache: "force-cache",
      next: { tags: ["articles"] },
    });
    return {
      title: `${category.name} - Статьи | RNB.by`,
      description: `Статьи по теме "${category.name}" на RNB.by.`,
    };
  } catch {
    return {
      title: "Категория статей | RNB.by",
      description: "Категория статей на RNB.by.",
    };
  }
}

export default async function CategoryPage({
  params,
}: {
  params: Promise<CategoryPageParams>;
}) {
  const resolvedParams = await params;
  const categorySlug = resolvedParams.categorySlug;

  const [categoryResult, articlesResult, categoriesResult] = await Promise.allSettled([
    fetchPublicApi<ArticleCategory>(`/article-categories/${categorySlug}`, {
      cache: "force-cache",
      next: { tags: ["articles"] },
    }),
    fetchPublicApi<Article[]>(`/articles?categorySlug=${encodeURIComponent(categorySlug)}&limit=100`, {
      cache: "force-cache",
      next: { tags: ["articles"] },
    }),
    fetchPublicApi<ArticleCategory[]>("/article-categories", {
      cache: "force-cache",
      next: { tags: ["articles"] },
    }),
  ]);

  if (categoryResult.status !== "fulfilled") {
    return (
      <div className="min-h-screen bg-background pt-24 pb-16">
        <div className="container mx-auto px-4 text-center py-16">
          <h1 className="text-3xl font-bold text-foreground mb-4">Категория не найдена</h1>
          <p className="text-muted-foreground mb-6">Такой категории статей не существует</p>
          <Button asChild>
            <Link href="/stati/">Все статьи</Link>
          </Button>
        </div>
      </div>
    );
  }

  const currentCategory = categoryResult.value;
  const articles = articlesResult.status === "fulfilled" ? articlesResult.value : [];
  const allCategories = categoriesResult.status === "fulfilled" ? categoriesResult.value : [];

  return (
    <Suspense fallback={<CategoryArticlesPageFallback />}>
      <CategoryPageClient
        slug={categorySlug}
        currentCategory={currentCategory}
        allCategories={allCategories}
        articles={articles}
      />
    </Suspense>
  );
}
