"use client";

import { useCallback, useEffect, useState } from "react";
import { usePathname, useRouter, useSearchParams } from "next/navigation";
import { motion } from "framer-motion";
import { Search, Clock, ArrowUpRight, ChevronLeft, ChevronRight, ArrowLeft } from "lucide-react";
import Link from "next/link";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Article, ArticleCategory } from "@/features/articles/types";
import { estimateArticleReadMinutes } from "@/features/articles/articleHtmlUtils";
import { resolveArticleThumbnailUrl } from "@/features/articles/image";

const ARTICLES_PER_PAGE = 6;
const FALLBACK_IMAGE = "/rnb-logo.png";

type DisplayArticle = {
  slug: string;
  image: string;
  categoryName: string;
  categorySlug: string;
  title: string;
  excerpt: string;
  date: string;
  readTime: string;
};

function formatDate(dateStr: string): string {
  const date = new Date(dateStr);
  const months = ["янв", "фев", "мар", "апр", "мая", "июн", "июл", "авг", "сен", "окт", "ноя", "дек"];
  return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
}

function toDisplayArticle(article: Article): DisplayArticle {
  return {
    slug: article.slug,
    image: resolveArticleThumbnailUrl(article.coverImage) || FALLBACK_IMAGE,
    categoryName: article.categoryName || "Без категории",
    categorySlug: article.categorySlug || "",
    title: article.title,
    excerpt: article.excerpt,
    date: formatDate(article.publishedAt || article.createdAt),
    readTime: `${estimateArticleReadMinutes(article.content)} мин`,
  };
}

type CategoryPageClientProps = {
  slug: string;
  currentCategory: ArticleCategory;
  allCategories: ArticleCategory[];
  articles: Article[];
};

export default function CategoryPageClient({
  slug,
  currentCategory,
  allCategories,
  articles,
}: CategoryPageClientProps) {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const [search, setSearch] = useState("");
  const pageFromQuery = Number(searchParams.get("page") ?? "1");
  const validPageFromQuery = Number.isFinite(pageFromQuery) && pageFromQuery > 0 ? Math.floor(pageFromQuery) : 1;
  const [currentPage, setCurrentPage] = useState(validPageFromQuery);

  const displayArticles = articles.map(toDisplayArticle);
  const filtered = displayArticles.filter((article) => {
    if (!search) return true;
    return (
      article.title.toLowerCase().includes(search.toLowerCase()) ||
      article.excerpt.toLowerCase().includes(search.toLowerCase())
    );
  });

  useEffect(() => {
    setCurrentPage(validPageFromQuery);
  }, [validPageFromQuery]);

  const totalPages = Math.ceil(filtered.length / ARTICLES_PER_PAGE);
  const maxPage = Math.max(totalPages, 1);
  const safeCurrentPage = Math.min(currentPage, maxPage);

  const changePage = useCallback((nextPage: number, replace = false) => {
    const boundedPage = Math.min(Math.max(1, nextPage), maxPage);
    const params = new URLSearchParams(searchParams.toString());
    if (boundedPage <= 1) {
      params.delete("page");
    } else {
      params.set("page", String(boundedPage));
    }

    const query = params.toString();
    const url = query ? `${pathname}?${query}` : pathname;
    if (replace) {
      router.replace(url);
      return;
    }
    router.push(url);
  }, [maxPage, pathname, router, searchParams]);

  useEffect(() => {
    if (!search) return;
    changePage(1, true);
  }, [search, changePage]);

  useEffect(() => {
    if (currentPage > maxPage) {
      changePage(maxPage, true);
    }
  }, [currentPage, maxPage, changePage]);

  const paginatedArticles = filtered.slice(
    (safeCurrentPage - 1) * ARTICLES_PER_PAGE,
    safeCurrentPage * ARTICLES_PER_PAGE
  );

  return (
    <div className="min-h-screen bg-background pt-24 pb-16">
      <div className="container mx-auto px-4">
        <motion.div
          initial={{ opacity: 0, x: -10 }}
          animate={{ opacity: 1, x: 0 }}
          className="mb-8"
        >
          <Link
            href="/stati/"
            className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground transition-colors"
          >
            <ArrowLeft className="w-4 h-4" />
            Все статьи
          </Link>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="text-center mb-12"
        >
          <h1 className="text-4xl md:text-5xl font-bold font-display text-foreground mb-4">
            {currentCategory.name}
          </h1>
        </motion.div>

        {allCategories.length > 0 && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ delay: 0.1 }}
            className="flex flex-wrap justify-center gap-2 mb-10"
          >
            <Link
              href="/stati/"
              className="px-4 py-2 rounded-full text-sm font-medium transition-all bg-muted text-muted-foreground hover:text-foreground hover:bg-accent"
            >
              Все
            </Link>
            {allCategories.map((category) => (
              <Link
                key={category.slug}
                href={`/stati/${category.slug}/`}
                className={`px-4 py-2 rounded-full text-sm font-medium transition-all ${
                  category.slug === slug
                    ? "bg-primary text-primary-foreground"
                    : "bg-muted text-muted-foreground hover:text-foreground hover:bg-accent"
                }`}
              >
                {category.name}
              </Link>
            ))}
          </motion.div>
        )}

        <motion.div
          initial={{ opacity: 0, y: 10 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.15 }}
          className="max-w-xl mx-auto mb-10"
        >
          <div className="relative">
            <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
            <Input
              placeholder={`Поиск в «${currentCategory.name}»...`}
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              className="pl-11 h-12 rounded-xl"
            />
          </div>
        </motion.div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          {paginatedArticles.map((article, index) => (
            <motion.div
              key={article.slug}
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: index * 0.05, duration: 0.4 }}
              className="group relative"
            >
              <Link href={`/stati/${article.categorySlug}/${article.slug}/`} className="block">
                <div className="relative aspect-[16/10] rounded-2xl overflow-hidden mb-4">
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img
                    src={article.image}
                    alt={article.title}
                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                    loading="lazy"
                  />
                  <span className="absolute top-3 left-3 px-2.5 py-1 rounded-full bg-card/90 backdrop-blur-sm text-xs font-medium text-foreground">
                    {article.categoryName}
                  </span>
                </div>
                <div className="flex items-center gap-3 text-xs text-muted-foreground mb-2">
                  <span>{article.date}</span>
                  <span className="w-1 h-1 rounded-full bg-muted-foreground/40" />
                  <span className="flex items-center gap-1">
                    <Clock className="w-3 h-3" />
                    {article.readTime}
                  </span>
                </div>
                <h3 className="font-display font-semibold text-foreground text-lg mb-2 group-hover:text-primary transition-colors leading-snug">
                  {article.title}
                </h3>
                <p className="text-sm text-muted-foreground leading-relaxed line-clamp-2">
                  {article.excerpt}
                </p>
              </Link>
              <Link
                href={`/stati/${article.categorySlug}/${article.slug}/`}
                target="_blank"
                rel="noopener noreferrer"
                className="absolute top-3 right-3 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-card/80 opacity-0 backdrop-blur-sm transition-opacity group-hover:opacity-100"
                aria-label="Открыть статью в новой вкладке"
              >
                <ArrowUpRight className="w-4 h-4 text-foreground" />
              </Link>
            </motion.div>
          ))}
        </div>

        {totalPages > 1 && (
          <div className="flex items-center justify-center gap-2 mt-10">
            <Button
              variant="outline"
              size="icon"
              disabled={safeCurrentPage === 1}
              onClick={() => changePage(safeCurrentPage - 1)}
              className="h-9 w-9"
            >
              <ChevronLeft className="w-4 h-4" />
            </Button>
            {Array.from({ length: totalPages }, (_, index) => index + 1).map((page) => (
              <Button
                key={page}
                variant={safeCurrentPage === page ? "default" : "outline"}
                size="icon"
                onClick={() => changePage(page)}
                className="h-9 w-9"
              >
                {page}
              </Button>
            ))}
            <Button
              variant="outline"
              size="icon"
              disabled={safeCurrentPage === totalPages}
              onClick={() => changePage(safeCurrentPage + 1)}
              className="h-9 w-9"
            >
              <ChevronRight className="w-4 h-4" />
            </Button>
          </div>
        )}

        {filtered.length === 0 && (
          <div className="text-center py-16">
            <p className="text-lg text-muted-foreground">
              Статьи в категории «{currentCategory.name}» не найдены
            </p>
            {search && (
              <Button
                variant="outline"
                className="mt-4"
                onClick={() => setSearch("")}
              >
                Сбросить поиск
              </Button>
            )}
          </div>
        )}
      </div>
    </div>
  );
}
