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
  isBaseCityApartmentCatalogPage,
  buildCatalogCitySeoHeading,
  resolveCatalogCitySlug,
} from "@/features/catalog/slugs";
import {
  resolveMetroStationName,
  resolveCityDistrictName,
  resolveLandmark,
  resolveLandmarkPhrase,
  validatePublicSegments,
} from "@/features/catalog/validate-segments-server";
import { formatAddress, Property } from "@/features/properties/types";
import CatalogPage from "@/features/catalog/CatalogPage";
import HomePage from "@/features/home/HomePage";
import FeaturesSection from "@/components/FeaturesSection";
import { fetchApi, fetchPublicApiNullable } from "@/lib/server-api";
import { fetchFeaturedPropertiesForHome } from "@/lib/featured-properties-server";
import { fetchCityApartmentCountsForHome } from "@/lib/city-apartment-counts-server";
import { fetchCityCatalogSeoText } from "@/lib/city-catalog-seo-server";
import { fetchRegionHouseCountsForHome } from "@/lib/region-house-counts-server";
import { fetchRecentArticlesForHome } from "@/lib/articles-server";
import { HEADER_REGION_MINSK_SLUG } from "@/lib/region-header";
import { DEFAULT_EXCHANGE_RATES_FALLBACK, formatPropertyPrices } from "@/features/properties/price-display";
import {
  buildApartmentPropertyMetaDescription,
  buildApartmentPropertyMetaTitle,
} from "@/features/properties/property-meta-title";
import { sanitizeArticleHtml } from "@/features/articles/sanitizeArticleHtml";
import { fetchCityLandmarks } from "@/lib/landmark-server";
import PropertyDetailClient from "../../../features/properties/components/PropertyDetailClient";

interface PageProps {
  params: Promise<{ segments?: string[] }>;
  searchParams: Promise<{ page?: string }>;
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

    if (!property || property.status === "archived" || property.status === "deleted") {
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
  const cityDistrictName = await resolveCityDistrictName(parsed);
  const landmark = await resolveLandmark(parsed);
  const landmarkPhrase = resolveLandmarkPhrase(landmark);
  const h1Title = buildPageTitle(parsed, undefined, metroStationName, cityDistrictName, landmarkPhrase);
  const metaTitle = buildCatalogMetaTitle(parsed, metroStationName, cityDistrictName, landmarkPhrase);
  const metaDescription = buildCatalogMetaDescription(parsed, metroStationName, cityDistrictName, landmarkPhrase);

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

export default async function SegmentsPage({ params, searchParams }: PageProps) {
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
    if (!property || property.status === "archived" || property.status === "deleted") {
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
    const [featuredInitial, articles, cityApartmentCounts, regionHouseCounts] = await Promise.all([
      fetchFeaturedPropertiesForHome(featuredRegionSlug),
      fetchRecentArticlesForHome(),
      fetchCityApartmentCountsForHome(),
      fetchRegionHouseCountsForHome(),
    ]);

    return (
      <HomePage
        featuredInitial={featuredInitial ?? undefined}
        articles={articles}
        cityApartmentCounts={cityApartmentCounts}
        regionHouseCounts={regionHouseCounts}
        features={<FeaturesSection />}
      />
    );
  }

  const metroStationName = await resolveMetroStationName(parsed.metroStationSlug);
  const cityDistrictName = await resolveCityDistrictName(parsed);
  const landmark = await resolveLandmark(parsed);
  const landmarkPhrase = resolveLandmarkPhrase(landmark);
  const title = buildPageTitle(parsed, undefined, metroStationName, cityDistrictName, landmarkPhrase);

  const { page: pageParam } = await searchParams;
  const pageFromQuery = Number(pageParam ?? "1");
  const validPage =
    Number.isFinite(pageFromQuery) && pageFromQuery > 0 ? Math.floor(pageFromQuery) : 1;
  const isFirstPage = validPage <= 1;

  let citySeoFooter = null;
  if (isFirstPage && isBaseCityApartmentCatalogPage(parsed)) {
    const citySlug = resolveCatalogCitySlug(parsed);
    const rawSeoText = await fetchCityCatalogSeoText(citySlug);
    if (rawSeoText) {
      const sanitizedHtml = sanitizeArticleHtml(rawSeoText);
      if (sanitizedHtml) {
        citySeoFooter = {
          heading: buildCatalogCitySeoHeading(citySlug),
          html: sanitizedHtml,
        };
      }
    }
  }

  let landmarkFooter = null;
  if (isFirstPage && landmark) {
    const citySlug = resolveCatalogCitySlug(parsed);
    const cityLandmarks = await fetchCityLandmarks(citySlug);
    const relatedLandmarks = cityLandmarks
      .filter((item) => item.slug !== landmark.slug)
      .slice(0, 3);
    const sanitizedDescription = landmark.description
      ? sanitizeArticleHtml(landmark.description)
      : null;
    landmarkFooter = {
      landmark,
      citySlug,
      relatedLandmarks,
      descriptionHtml: sanitizedDescription,
    };
  }

  return (
    <CatalogPage
      parsed={parsed}
      title={title}
      landmark={landmark}
      landmarkFooter={landmarkFooter}
      citySeoFooter={citySeoFooter}
    />
  );
}
