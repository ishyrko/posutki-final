import { cache } from "react";
import { notFound } from "next/navigation";
import type { Metadata } from "next";
import {
  parseSegments,
  isCatalogRoute,
  buildPageTitle,
  buildCatalogMetaTitle,
  buildCatalogMetaDescription,
  buildCatalogCanonicalPath,
  buildPropertyUrlFromRegionName,
  buildSegmentsCanonicalPath,
  isPropertyId,
} from "@/features/catalog/slugs";
import {
  resolveMetroStationName,
  validatePublicSegments,
} from "@/features/catalog/validate-segments-server";
import { formatAddress, Property } from "@/features/properties/types";
import CatalogPage from "@/features/catalog/CatalogPage";
import HomePage from "@/features/home/HomePage";
import { fetchApi, fetchPublicApiNullable } from "@/lib/server-api";
import { fetchFeaturedPropertiesForHome } from "@/lib/featured-properties-server";
import { fetchCityApartmentCountsForHome } from "@/lib/city-apartment-counts-server";
import { fetchRecentArticlesForHome } from "@/lib/articles-server";
import { HEADER_REGION_MINSK_SLUG } from "@/lib/region-header";
import { DEFAULT_EXCHANGE_RATES_FALLBACK, formatPropertyPrices } from "@/features/properties/price-display";
import {
  buildApartmentPropertyMetaDescription,
  buildApartmentPropertyMetaTitle,
} from "@/features/properties/property-meta-title";
import PropertyDetailClient from "../../../features/properties/components/PropertyDetailClient";

interface PageProps {
  params: Promise<{ segments?: string[] }>;
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

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { segments } = await params;

  if (!(await validatePublicSegments(segments))) {
    notFound();
  }

  const propertyId = isPropertyId(segments?.[segments.length - 1])
    ? segments?.[segments.length - 1]
    : undefined;

  if (propertyId) {
    const property = await getPropertyById(Number(propertyId));

    if (!property) {
      notFound();
    }

    const address = formatAddress(property.address);
    const { primaryPlain: bynPrice } = formatPropertyPrices(property, DEFAULT_EXCHANGE_RATES_FALLBACK);
    const metaTitle =
      buildApartmentPropertyMetaTitle(property) ?? `${property.title} | Посутки.by`;
    const metaDescription =
      buildApartmentPropertyMetaDescription(property) ?? `${address} — ${bynPrice}`;
    const firstImage = property.images?.[0]?.url;
    const canonicalPath = buildPropertyUrlFromRegionName(
      property.type,
      Number(propertyId),
      property.address.regionName,
      property.address.citySlug,
    );
    const currentPath = buildSegmentsCanonicalPath(segments ?? []);
    if (canonicalPath !== currentPath) {
      notFound();
    }

    return {
      title: metaTitle,
      description: metaDescription,
      alternates: {
        canonical: canonicalPath,
      },
      openGraph: {
        title: metaTitle,
        description: metaDescription,
        images: firstImage ? [{ url: firstImage }] : undefined,
        type: "website",
      },
    };
  }

  const parsed = parseSegments(segments);

  if (!isCatalogRoute(parsed)) {
    return {
      title:
        "Квартиры и дома на сутки в Беларуси - посуточная аренда в Минске и других городах",
      description:
        "Снимайте квартиры и дома на сутки в Беларуси напрямую от владельцев на Posutki.by. Минск, Гродно, Брест, Витебск, Гомель, Могилёв — актуальные объявления, удобный поиск по городу, типу жилья и количеству гостей.",
    };
  }

  const metroStationName = await resolveMetroStationName(parsed.metroStationSlug);
  const h1Title = buildPageTitle(parsed, undefined, metroStationName);
  const metaTitle = buildCatalogMetaTitle(parsed, metroStationName);
  const metaDescription = buildCatalogMetaDescription(parsed, metroStationName);

  return {
    title: metaTitle ?? `${h1Title} | Посутки.by`,
    description:
      metaDescription ??
      `Каталог посуточной аренды: ${h1Title.toLowerCase()}. Актуальные объявления с ценами и фото.`,
    alternates: {
      canonical: buildCatalogCanonicalPath(parsed),
    },
  };
}

export default async function SegmentsPage({ params }: PageProps) {
  const { segments } = await params;

  if (!(await validatePublicSegments(segments))) {
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

    const canonicalPath = buildPropertyUrlFromRegionName(
      property.type,
      numericPropertyId,
      property.address.regionName,
      property.address.citySlug,
    );
    const currentPath = buildSegmentsCanonicalPath(segments ?? []);
    if (canonicalPath !== currentPath) {
      notFound();
    }

    return <PropertyDetailClient id={numericPropertyId} initialProperty={property} />;
  }

  const parsed = parseSegments(segments);

  if (!isCatalogRoute(parsed)) {
    const featuredRegionSlug = HEADER_REGION_MINSK_SLUG;
    const [featuredInitial, articles, cityApartmentCounts] = await Promise.all([
      fetchFeaturedPropertiesForHome(featuredRegionSlug),
      fetchRecentArticlesForHome(),
      fetchCityApartmentCountsForHome(),
    ]);

    return (
      <HomePage
        featuredInitial={featuredInitial ?? undefined}
        articles={articles}
        cityApartmentCounts={cityApartmentCounts}
      />
    );
  }

  const metroStationName = await resolveMetroStationName(parsed.metroStationSlug);
  const title = buildPageTitle(parsed, undefined, metroStationName);

  return <CatalogPage parsed={parsed} title={title} />;
}
