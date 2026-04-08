import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { sanitizeArticleHtml } from "@/features/articles/sanitizeArticleHtml";
import { fetchStaticPage } from "@/features/staticPages/api";
import StaticPageLayoutClient from "@/features/staticPages/StaticPageLayoutClient";

export const revalidate = false;

const SLUG = "o-nas";

export async function generateMetadata(): Promise<Metadata> {
  const page = await fetchStaticPage(SLUG);
  if (!page) {
    return {
      title: "О нас | RNB.by",
      description:
        "RNB.by — портал недвижимости в Беларуси: объявления, статьи и инструменты для покупателей, арендаторов и собственников.",
    };
  }
  return {
    title: page.metaTitle ?? `${page.title} | RNB.by`,
    description: page.metaDescription ?? undefined,
  };
}

export default async function AboutPage() {
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
