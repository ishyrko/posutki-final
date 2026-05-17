import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { sanitizeArticleHtml } from "@/features/articles/sanitizeArticleHtml";
import { fetchStaticPage } from "@/features/staticPages/api";
import StaticPageLayoutClient from "@/features/staticPages/StaticPageLayoutClient";

export const revalidate = false;

const SLUG = "usloviya-ispolzovaniya";

export async function generateMetadata(): Promise<Metadata> {
  const page = await fetchStaticPage(SLUG);
  if (!page) {
    return {
      title: "Условия использования | Посутки.by",
      description:
        "Правила использования сайта Posutki.by: учётная запись, размещение объявлений и ответственность сторон.",
    };
  }
  return {
    title: page.metaTitle ?? `${page.title} | Посутки.by`,
    description: page.metaDescription ?? undefined,
  };
}

export default async function TermsOfUsePage() {
  const page = await fetchStaticPage(SLUG);
  if (!page) {
    notFound();
  }

  const raw = typeof page.content === "string" ? page.content : "";
  const sanitizedHtml = sanitizeArticleHtml(raw) ?? "";

  return (
    <StaticPageLayoutClient title={page.title} sanitizedHtml={sanitizedHtml} />
  );
}
