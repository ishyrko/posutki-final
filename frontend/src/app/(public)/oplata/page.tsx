import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { sanitizeArticleHtml } from "@/features/articles/sanitizeArticleHtml";
import { fetchStaticPage } from "@/features/staticPages/api";
import StaticPageLayoutClient from "@/features/staticPages/StaticPageLayoutClient";
import { buildPageMetadata } from "@/lib/seo/open-graph";

export const revalidate = false;

const SLUG = "oplata";

export async function generateMetadata(): Promise<Metadata> {
  const page = await fetchStaticPage(SLUG);
  if (!page) {
    return buildPageMetadata({
      title: "Оплата | Посутки.by",
      description:
        "Процесс и способы оплаты услуг Posutki.by, условия возврата денежных средств.",
      path: `/${SLUG}/`,
    });
  }
  return buildPageMetadata({
    title: page.metaTitle ?? `${page.title} | Посутки.by`,
    description:
      page.metaDescription ??
      "Процесс и способы оплаты услуг Posutki.by, условия возврата денежных средств.",
    path: `/${SLUG}/`,
  });
}

export default async function PaymentPage() {
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
