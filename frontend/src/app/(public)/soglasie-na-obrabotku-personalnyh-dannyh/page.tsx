import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { sanitizeArticleHtml } from "@/features/articles/sanitizeArticleHtml";
import { fetchStaticPage } from "@/features/staticPages/api";
import StaticPageLayoutClient from "@/features/staticPages/StaticPageLayoutClient";

export const revalidate = false;

const SLUG = "soglasie-na-obrabotku-personalnyh-dannyh";

export async function generateMetadata(): Promise<Metadata> {
  const page = await fetchStaticPage(SLUG);
  if (!page) {
    return {
      title: "Согласие на обработку персональных данных | Посутки.by",
      description:
        "Согласие субъекта персональных данных на обработку данных при использовании Posutki.by.",
    };
  }
  return {
    title: page.metaTitle ?? `${page.title} | Посутки.by`,
    description: page.metaDescription ?? undefined,
  };
}

export default async function PersonalDataConsentPage() {
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
