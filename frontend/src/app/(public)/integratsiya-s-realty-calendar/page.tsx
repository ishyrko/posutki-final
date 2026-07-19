import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { sanitizeArticleHtml } from "@/features/articles/sanitizeArticleHtml";
import { fetchStaticPage } from "@/features/staticPages/api";
import StaticPageLayoutClient from "@/features/staticPages/StaticPageLayoutClient";

export const revalidate = false;

const SLUG = "integratsiya-s-realty-calendar";

export async function generateMetadata(): Promise<Metadata> {
  const page = await fetchStaticPage(SLUG);
  if (!page) {
    return {
      title: "Интеграция с RealtyCalendar | Посутки.by",
      description:
        "Как синхронизировать календарь занятости на Posutki.by с RealtyCalendar через iCal.",
    };
  }
  return {
    title: page.metaTitle ?? `${page.title} | Посутки.by`,
    description: page.metaDescription ?? undefined,
  };
}

export default async function RealtyCalendarIntegrationPage() {
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
