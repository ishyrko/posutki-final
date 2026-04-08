import { revalidatePath, revalidateTag } from "next/cache";
import { NextResponse } from "next/server";

type RevalidateBody = {
  secret?: string;
  type?: string;
  slug?: string;
  /** Present for articles — used to revalidate the full `/stati/[category]/[slug]` route. */
  categorySlug?: string;
};

/** POST /revalidate — intentionally NOT under /api (Symfony nginx sends all /api/* to PHP). */
export async function POST(request: Request) {
  const configuredSecret = process.env.REVALIDATION_SECRET;
  if (!configuredSecret) {
    return NextResponse.json({ error: "REVALIDATION_SECRET is not configured" }, { status: 500 });
  }

  let body: RevalidateBody;
  try {
    body = (await request.json()) as RevalidateBody;
  } catch {
    return NextResponse.json({ error: "Invalid JSON body" }, { status: 400 });
  }

  if (body.secret !== configuredSecret) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  const { type, slug, categorySlug } = body;

  if (type === "article") {
    revalidateTag("articles", { expire: 0 });
    if (slug) {
      revalidateTag(`article-${slug}`, { expire: 0 });
    }
    revalidatePath("/stati", "layout");
    revalidatePath("/stati", "page");
    if (categorySlug && slug) {
      revalidatePath(`/stati/${categorySlug}`, "page");
      revalidatePath(`/stati/${categorySlug}/${slug}`, "page");
    }
    return NextResponse.json({
      revalidated: true,
      type: "article",
      slug: slug ?? null,
      categorySlug: categorySlug ?? null,
    });
  }

  if (type === "static-page") {
    if (!slug) {
      return NextResponse.json({ error: "slug is required for static-page" }, { status: 400 });
    }
    revalidateTag("static-pages", { expire: 0 });
    revalidateTag(`static-page-${slug}`, { expire: 0 });
    revalidatePath(`/${slug}`, "page");
    return NextResponse.json({ revalidated: true, type: "static-page", slug });
  }

  return NextResponse.json({ error: "Invalid type" }, { status: 400 });
}
