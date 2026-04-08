import { cache } from "react";
import { notFound } from "next/navigation";
import type { Metadata } from "next";
import {
  parseSegments,
  isCatalogRoute,
  buildPageTitle,
  isPropertyId,
  REGION_SLUGS,
  DEAL_TYPE_SLUG_TO_VALUE,
  PROPERTY_TYPE_SLUG_TO_VALUE,
} from "@/features/catalog/slugs";
import { formatAddress, Property } from "@/features/properties/types";
import CatalogPage from "@/features/catalog/CatalogPage";
import HomePage from "@/features/home/HomePage";
import { fetchApi, fetchPublicApi, fetchPublicApiNullable } from "@/lib/server-api";
import { fetchFeaturedPropertiesForHome } from "@/lib/featured-properties-server";
import { HEADER_REGION_MINSK_SLUG } from "@/lib/region-header";
import type { Article } from "@/features/articles/types";
import { DEFAULT_EXCHANGE_RATES_FALLBACK, formatPropertyPrices } from "@/features/properties/price-display";
import PropertyDetailClient from "../../../features/properties/components/PropertyDetailClient";

interface PageProps {
  params: Promise<{ segments?: string[] }>;
}

interface MetroStationsResponse {
  data?: Array<{ slug: string; name: string }>;
}

/** Anonymous GET first (avoids 401 from invalid cookie JWT on SSR); then auth for unpublished owner views. */
const getPropertyById = cache(async (id: number): Promise<Property | null> => {
  try {
    const published = await fetchPublicApiNullable<Property>(`/properties/${id}`);
    if (published) return published;
    return await fetchApi<Property>(`/properties/${id}`);
  } catch {
    return null;
  }
});

async function resolveMetroStationName(slug?: string): Promise<string | undefined> {
  if (!slug) return undefined;
  const apiUrl = process.env.NEXT_PUBLIC_API_URL;
  if (!apiUrl) return undefined;

  try {
    const response = await fetch(`${apiUrl}/metro/stations?cityId=1`, {
      next: { revalidate: 3600 },
    });
    if (!response.ok) return undefined;

    const payload = (await response.json()) as MetroStationsResponse;
    return payload.data?.find((station) => station.slug === slug)?.name;
  } catch {
    return undefined;
  }
}

function validateCatalogSegments(segments: string[] = []): boolean {
  if (segments.length === 0) {
    return true;
  }

  let i = 0;

  if (REGION_SLUGS.has(segments[i])) i++;
  if (segments.length === i) return true;

  if (segments[i] in DEAL_TYPE_SLUG_TO_VALUE) i++;
  else return false;

  if (i < segments.length && segments[i] in PROPERTY_TYPE_SLUG_TO_VALUE) i++;

  if (i < segments.length) {
    if (segments[i] === 'vozle-metro') {
      i++;
    } else if (segments[i] === 'metro') {
      i++;
      if (i < segments.length) i++;
      else return false;
    } else {
      i++;
    }
  }

  return i === segments.length;
}

function validateSegments(segments: string[] = []): boolean {
  if (segments.length === 0) return true;

  const lastSegment = segments[segments.length - 1];
  if (isPropertyId(lastSegment)) {
    const catalogSegments = segments.slice(0, -1);
    if (!validateCatalogSegments(catalogSegments)) return false;

    const parsed = parseSegments(catalogSegments);
    return parsed.dealType !== undefined && parsed.propertyType !== undefined;
  }

  return validateCatalogSegments(segments);
}

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { segments } = await params;
  const propertyId = isPropertyId(segments?.[segments.length - 1])
    ? segments?.[segments.length - 1]
    : undefined;

  if (propertyId) {
    const property = await getPropertyById(Number(propertyId));

    if (!property) {
      notFound();
    }

    const address = formatAddress(property.address);
    const { primary: bynPrice } = formatPropertyPrices(property, DEFAULT_EXCHANGE_RATES_FALLBACK);
    const firstImage = property.images?.[0]?.url;

    return {
      title: `${property.title} | RNB.by`,
      description: `${address} — ${bynPrice}`,
      openGraph: {
        title: property.title,
        description: `${address} — ${bynPrice}`,
        images: firstImage ? [{ url: firstImage }] : undefined,
        type: "website",
      },
    };
  }

  const parsed = parseSegments(segments);

  if (!isCatalogRoute(parsed)) {
    const regionLabel = parsed.regionSlug
      ? { brest: 'Брест и область', vitebsk: 'Витебск и область', gomel: 'Гомель и область', grodno: 'Гродно и область', mogilev: 'Могилев и область' }[parsed.regionSlug]
      : undefined;

    return {
      title: regionLabel
        ? `Недвижимость — ${regionLabel}`
        : 'Недвижимость в Беларуси — RNB.by',
      description: regionLabel
        ? `Покупка, продажа и аренда недвижимости в ${regionLabel}. Квартиры, дома, коммерческая недвижимость.`
        : 'Тысячи объектов недвижимости по всей Беларуси. Покупка, продажа и аренда — быстро, удобно и безопасно.',
    };
  }

  const metroStationName = await resolveMetroStationName(parsed.metroStationSlug);
  const title = buildPageTitle(parsed, undefined, metroStationName);

  return {
    title: `${title} — RNB.by`,
    description: `Каталог недвижимости: ${title.toLowerCase()}. Актуальные объявления с ценами и фото.`,
  };
}

export default async function SegmentsPage({ params }: PageProps) {
  const { segments } = await params;

  if (!validateSegments(segments)) {
    notFound();
  }

  const propertyId = isPropertyId(segments?.[segments.length - 1])
    ? segments?.[segments.length - 1]
    : undefined;

  if (propertyId) {
    const numericPropertyId = Number(propertyId);
    const property = await getPropertyById(numericPropertyId);
    if (!property) {
      notFound();
    }
    return <PropertyDetailClient id={numericPropertyId} initialProperty={property} />;
  }

  const parsed = parseSegments(segments);

  if (!isCatalogRoute(parsed)) {
    let homeArticles: Article[] = [];
    try {
      homeArticles = await fetchPublicApi<Article[]>("/articles?limit=3", {
        next: { revalidate: 3600 },
      });
    } catch {
      homeArticles = [];
    }

    const featuredRegionSlug = parsed.regionSlug ?? HEADER_REGION_MINSK_SLUG;
    const featuredInitial = await fetchFeaturedPropertiesForHome(featuredRegionSlug);

    return (
      <HomePage
        regionSlug={parsed.regionSlug}
        articles={homeArticles}
        featuredInitial={featuredInitial ?? undefined}
      />
    );
  }

  const metroStationName = await resolveMetroStationName(parsed.metroStationSlug);
  const title = buildPageTitle(parsed, undefined, metroStationName);

  return <CatalogPage parsed={parsed} title={title} />;
}
