import { revalidatePath, revalidateTag } from "next/cache";
import { NextResponse } from "next/server";
import {
  buildCatalogUrl,
  CITY_PREFIX_SLUGS,
  MINSK_CITY_SLUG,
  REGION_SLUGS,
} from "@/features/catalog/slugs";

type RevalidateBody = {
  secret?: string;
  type?: string;
  slug?: string;
  /** Present for articles — used to revalidate the full `/stati/[category]/[slug]` route. */
  categorySlug?: string;
  /** Present for landmarks — city slug for catalog path building. */
  citySlug?: string;
};

/** Base apartment catalog path for a city slug (SEO block lives only there). */
function catalogApartmentPathForCitySlug(citySlug: string): string {
  if (citySlug === MINSK_CITY_SLUG) {
    return buildCatalogUrl({ propertyType: "apartment" });
  }
  if (REGION_SLUGS.has(citySlug)) {
    return buildCatalogUrl({ region: citySlug, propertyType: "apartment" });
  }
  if (CITY_PREFIX_SLUGS.has(citySlug)) {
    return buildCatalogUrl({ city: citySlug, propertyType: "apartment" });
  }
  return buildCatalogUrl({ propertyType: "apartment", city: citySlug });
}

function catalogLandmarkPathForCitySlug(citySlug: string, landmarkSlug: string): string {
  if (citySlug === MINSK_CITY_SLUG) {
    return buildCatalogUrl({ propertyType: "apartment", landmark: landmarkSlug });
  }
  if (REGION_SLUGS.has(citySlug)) {
    return buildCatalogUrl({ region: citySlug, propertyType: "apartment", landmark: landmarkSlug });
  }
  if (CITY_PREFIX_SLUGS.has(citySlug)) {
    return buildCatalogUrl({ city: citySlug, propertyType: "apartment", landmark: landmarkSlug });
  }
  return buildCatalogUrl({ propertyType: "apartment", landmark: landmarkSlug, city: citySlug });
}

function catalogCityDistrictPathForCitySlug(citySlug: string, districtSlug: string): string {
  if (citySlug === MINSK_CITY_SLUG) {
    return buildCatalogUrl({ propertyType: "apartment", cityDistrict: districtSlug });
  }
  if (REGION_SLUGS.has(citySlug)) {
    return buildCatalogUrl({ region: citySlug, propertyType: "apartment", cityDistrict: districtSlug });
  }
  if (CITY_PREFIX_SLUGS.has(citySlug)) {
    return buildCatalogUrl({ city: citySlug, propertyType: "apartment", cityDistrict: districtSlug });
  }
  return buildCatalogUrl({ propertyType: "apartment", cityDistrict: districtSlug, city: citySlug });
}

function catalogMicrodistrictPathForCitySlug(citySlug: string, microdistrictSlug: string): string {
  if (citySlug === MINSK_CITY_SLUG) {
    return buildCatalogUrl({ propertyType: "apartment", microdistrict: microdistrictSlug });
  }
  if (REGION_SLUGS.has(citySlug)) {
    return buildCatalogUrl({ region: citySlug, propertyType: "apartment", microdistrict: microdistrictSlug });
  }
  if (CITY_PREFIX_SLUGS.has(citySlug)) {
    return buildCatalogUrl({ city: citySlug, propertyType: "apartment", microdistrict: microdistrictSlug });
  }
  return buildCatalogUrl({ propertyType: "apartment", microdistrict: microdistrictSlug, city: citySlug });
}

function catalogResidentialComplexPathForCitySlug(citySlug: string, residentialComplexSlug: string): string {
  if (citySlug === MINSK_CITY_SLUG) {
    return buildCatalogUrl({ propertyType: "apartment", residentialComplex: residentialComplexSlug });
  }
  if (REGION_SLUGS.has(citySlug)) {
    return buildCatalogUrl({ region: citySlug, propertyType: "apartment", residentialComplex: residentialComplexSlug });
  }
  if (CITY_PREFIX_SLUGS.has(citySlug)) {
    return buildCatalogUrl({ city: citySlug, propertyType: "apartment", residentialComplex: residentialComplexSlug });
  }
  return buildCatalogUrl({
    propertyType: "apartment",
    residentialComplex: residentialComplexSlug,
    city: citySlug,
  });
}

function revalidateCatalogPlaceSeo(
  tagPrefix: "district-seo" | "microdistrict-seo" | "residential-complex-seo",
  citySlug: string,
  placeSlug: string,
  catalogPath: string,
) {
  revalidateTag("place-seo", { expire: 0 });
  revalidateTag(`${tagPrefix}-${citySlug}-${placeSlug}`, { expire: 0 });
  revalidatePath(catalogPath, "page");
}

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

  const { type, slug, categorySlug, citySlug } = body;

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

  if (type === "city") {
    if (!slug) {
      return NextResponse.json({ error: "slug is required for city" }, { status: 400 });
    }
    revalidateTag("city-seo", { expire: 0 });
    revalidateTag(`city-seo-${slug}`, { expire: 0 });
    const catalogPath = catalogApartmentPathForCitySlug(slug);
    revalidatePath(catalogPath, "page");
    return NextResponse.json({ revalidated: true, type: "city", slug, path: catalogPath });
  }

  if (type === "landmark") {
    if (!slug || !citySlug) {
      return NextResponse.json({ error: "slug and citySlug are required for landmark" }, { status: 400 });
    }
    revalidateTag(`city-landmarks-${citySlug}`, { expire: 0 });
    revalidateTag(`landmark-${citySlug}-${slug}`, { expire: 0 });
    const catalogPath = catalogLandmarkPathForCitySlug(citySlug, slug);
    revalidatePath(catalogPath, "page");
    return NextResponse.json({ revalidated: true, type: "landmark", slug, citySlug, path: catalogPath });
  }

  if (type === "city-district") {
    if (!slug || !citySlug) {
      return NextResponse.json({ error: "slug and citySlug are required for city-district" }, { status: 400 });
    }
    const catalogPath = catalogCityDistrictPathForCitySlug(citySlug, slug);
    revalidateCatalogPlaceSeo("district-seo", citySlug, slug, catalogPath);
    return NextResponse.json({ revalidated: true, type: "city-district", slug, citySlug, path: catalogPath });
  }

  if (type === "city-microdistrict") {
    if (!slug || !citySlug) {
      return NextResponse.json({ error: "slug and citySlug are required for city-microdistrict" }, { status: 400 });
    }
    const catalogPath = catalogMicrodistrictPathForCitySlug(citySlug, slug);
    revalidateCatalogPlaceSeo("microdistrict-seo", citySlug, slug, catalogPath);
    return NextResponse.json({ revalidated: true, type: "city-microdistrict", slug, citySlug, path: catalogPath });
  }

  if (type === "residential-complex") {
    if (!slug || !citySlug) {
      return NextResponse.json({ error: "slug and citySlug are required for residential-complex" }, { status: 400 });
    }
    const catalogPath = catalogResidentialComplexPathForCitySlug(citySlug, slug);
    revalidateCatalogPlaceSeo("residential-complex-seo", citySlug, slug, catalogPath);
    return NextResponse.json({ revalidated: true, type: "residential-complex", slug, citySlug, path: catalogPath });
  }

  return NextResponse.json({ error: "Invalid type" }, { status: 400 });
}
