import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { sanitizeArticleHtml } from "@/features/articles/sanitizeArticleHtml";
import { fetchStaticPage } from "@/features/staticPages/api";
import StaticPageLayoutClient from "@/features/staticPages/StaticPageLayoutClient";
import { buildPageMetadata } from "@/lib/seo/open-graph";

export const revalidate = false;

const SLUG = "politika-konfidentsialnosti";

export async function generateMetadata(): Promise<Metadata> {
  const page = await fetchStaticPage(SLUG);
  if (!page) {
    return buildPageMetadata({
      title: "Политика конфиденциальности | Посутки.by",
      description:
        "Условия обработки персональных данных и использования cookie на Посутки.by.",
      path: `/${SLUG}/`,
    });
  }
  return buildPageMetadata({
    title: page.metaTitle ?? `${page.title} | Посутки.by`,
    description:
      page.metaDescription ??
      "Условия обработки персональных данных и использования cookie на Посутки.by.",
    path: `/${SLUG}/`,
  });
}

export default async function PrivacyPolicyPage() {
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
