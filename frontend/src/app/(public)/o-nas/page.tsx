import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { sanitizeArticleHtml } from "@/features/articles/sanitizeArticleHtml";
import { fetchStaticPage } from "@/features/staticPages/api";
import StaticPageLayoutClient from "@/features/staticPages/StaticPageLayoutClient";
import { ABOUT_FAQ } from "@/features/about/faq-data";
import { EmbeddedFaqSection } from "@/components/seo/EmbeddedFaqSection";
import { JsonLdScript } from "@/lib/json-ld/json-ld-script";
import { buildFaqPageJsonLd } from "@/lib/json-ld/faq";
import { buildPageMetadata } from "@/lib/seo/open-graph";

export const revalidate = false;

const SLUG = "o-nas";

export async function generateMetadata(): Promise<Metadata> {
  const page = await fetchStaticPage(SLUG);
  if (!page) {
    return buildPageMetadata({
      title: "О нас | Посутки.by",
      description:
        "Посутки.by — посуточная аренда квартир и домов в Беларуси: удобный поиск жилья для гостей и размещение объявлений для собственников.",
      path: `/${SLUG}/`,
    });
  }
  return buildPageMetadata({
    title: page.metaTitle ?? `${page.title} | Посутки.by`,
    description:
      page.metaDescription ??
      "Посуточная аренда квартир и домов в Беларуси на Posutki.by.",
    path: `/${SLUG}/`,
  });
}

export default async function AboutPage() {
  const page = await fetchStaticPage(SLUG);
  if (!page) {
    notFound();
  }

  const raw = typeof page.content === "string" ? page.content : "";
  const sanitizedHtml = sanitizeArticleHtml(raw) ?? "";

  return (
    <>
      <JsonLdScript data={buildFaqPageJsonLd(ABOUT_FAQ)} />
      <StaticPageLayoutClient
        title={page.title}
        sanitizedHtml={sanitizedHtml}
        afterContent={<EmbeddedFaqSection items={ABOUT_FAQ} />}
      />
    </>
  );
}
