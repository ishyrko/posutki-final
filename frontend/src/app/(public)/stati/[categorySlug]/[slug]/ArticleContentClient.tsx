"use client";

import { motion } from "framer-motion";
import { Clock, ArrowLeft, Tag, Calendar } from "lucide-react";
import Link from "next/link";
import { Article } from "@/features/articles/types";
import { estimateArticleReadMinutes } from "@/features/articles/articleHtmlUtils";
import { resolveArticleThumbnailUrl } from "@/features/articles/image";

const FALLBACK_IMAGE = "/rnb-logo.png";

type ArticleContentClientProps = {
  article: Article;
  /** Pre-sanitized HTML from the server when content is HTML; otherwise null. */
  sanitizedHtml: string | null;
};

function formatDate(dateStr: string): string {
  const date = new Date(dateStr);
  const months = [
    "января", "февраля", "марта", "апреля", "мая", "июня",
    "июля", "августа", "сентября", "октября", "ноября", "декабря",
  ];
  return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
}

function renderContent(content: string) {
  const paragraphs = content.split(/\n\n+/);

  return paragraphs.map((paragraph, i) => {
    const trimmed = paragraph.trim();
    if (!trimmed) return null;

    const lines = trimmed.split("\n");
    const isNumberedList = lines.every((line) => /^\d+\.\s/.test(line.trim()));
    if (isNumberedList) {
      return (
        <ol key={i} className="list-decimal list-inside space-y-2 my-4 text-foreground/90 leading-relaxed">
          {lines.map((line, j) => (
            <li key={j}>{line.replace(/^\d+\.\s*/, "")}</li>
          ))}
        </ol>
      );
    }

    const isBulletList = lines.every((line) => /^[-•]\s/.test(line.trim()));
    if (isBulletList) {
      return (
        <ul key={i} className="list-disc list-inside space-y-2 my-4 text-foreground/90 leading-relaxed">
          {lines.map((line, j) => (
            <li key={j}>{line.replace(/^[-•]\s*/, "")}</li>
          ))}
        </ul>
      );
    }

    return (
      <p key={i} className="text-foreground/90 leading-relaxed mb-4">
        {trimmed}
      </p>
    );
  });
}

function ArticleBody({
  content,
  sanitizedHtml,
}: {
  content: string;
  sanitizedHtml: string | null;
}) {
  if (sanitizedHtml) {
    return (
      <div
        className="prose prose-lg max-w-none text-foreground/90 leading-relaxed [&_a]:text-primary [&_a]:underline"
        dangerouslySetInnerHTML={{ __html: sanitizedHtml }}
      />
    );
  }

  return <>{renderContent(content)}</>;
}

export default function ArticleContentClient({
  article,
  sanitizedHtml,
}: ArticleContentClientProps) {
  const content = typeof article.content === "string" ? article.content : "";
  const tags = Array.isArray(article.tags) ? article.tags : [];
  const readTime = estimateArticleReadMinutes(content);

  return (
    <div className="min-h-screen bg-background pt-24 pb-16">
      <div className="container mx-auto px-4">
        <article className="max-w-3xl mx-auto">
          <motion.div initial={{ opacity: 0, x: -10 }} animate={{ opacity: 1, x: 0 }} className="mb-8">
            <Link
              href={article.categorySlug ? `/stati/${article.categorySlug}/` : "/stati/"}
              className="inline-flex items-center gap-2 text-sm font-medium text-primary hover:text-primary/80 transition-colors"
            >
              <ArrowLeft className="w-4 h-4 shrink-0" />
              {article.categoryName || "Все статьи"}
            </Link>
          </motion.div>

          <motion.header initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="mb-8">
            <h1 className="text-3xl md:text-4xl lg:text-5xl font-bold font-display text-foreground mb-6 leading-tight">
              {article.title}
            </h1>

            {article.excerpt && (
              <p className="text-lg text-muted-foreground leading-relaxed mb-6">
                {article.excerpt}
              </p>
            )}

            <div className="flex flex-wrap items-center gap-4 text-sm text-muted-foreground pb-6 border-b border-border">
              {article.publishedAt && (
                <span className="flex items-center gap-1.5">
                  <Calendar className="w-4 h-4" />
                  {formatDate(article.publishedAt)}
                </span>
              )}
              <span className="flex items-center gap-1.5">
                <Clock className="w-4 h-4" />
                {readTime} мин чтения
              </span>
              {Number(article.views) > 0 && <span>{article.views} просмотров</span>}
            </div>
          </motion.header>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.1 }}
            className="mb-10"
          >
            <div className="aspect-[16/9] rounded-2xl overflow-hidden">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img
                src={resolveArticleThumbnailUrl(article.coverImage) || FALLBACK_IMAGE}
                alt={article.title}
                className="w-full h-full object-cover"
              />
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.15 }}
            className="text-base md:text-lg"
          >
            <ArticleBody content={content} sanitizedHtml={sanitizedHtml} />
          </motion.div>

          {tags.length > 0 && (
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              transition={{ delay: 0.2 }}
              className="mt-10 pt-6 border-t border-border"
            >
              <div className="flex items-center gap-2 flex-wrap">
                <Tag className="w-4 h-4 text-muted-foreground" />
                {tags.map((tag) => (
                  <span
                    key={tag}
                    className="px-3 py-1 rounded-full bg-muted text-muted-foreground text-xs font-medium"
                  >
                    {tag}
                  </span>
                ))}
              </div>
            </motion.div>
          )}

          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ delay: 0.25 }}
            className="mt-10 pt-6 border-t border-border"
          >
            <Link
              href="/stati/"
              className="inline-flex items-center gap-2 text-sm font-medium text-primary hover:text-primary/80 transition-colors"
            >
              <ArrowLeft className="w-4 h-4" />
              Все статьи
            </Link>
          </motion.div>
        </article>
      </div>
    </div>
  );
}
