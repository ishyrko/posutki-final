import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { sanitizeArticleHtml } from "@/features/articles/sanitizeArticleHtml";
import { fetchStaticPage } from "@/features/staticPages/api";
import StaticPageLayoutClient from "@/features/staticPages/StaticPageLayoutClient";

export const revalidate = false;

const SLUG = "politika-konfidentsialnosti";

export async function generateMetadata(): Promise<Metadata> {
  const page = await fetchStaticPage(SLUG);
  if (!page) {
    return {
      title: "Политика конфиденциальности | RNB.by",
      description: "Условия обработки персональных данных и использования cookie на RNB.by.",
    };
  }
  return {
    title: page.metaTitle ?? `${page.title} | RNB.by`,
    description: page.metaDescription ?? undefined,
  };
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
