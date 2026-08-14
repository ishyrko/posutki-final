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
  formatCityDistrictCatalogLocation,
  formatMicrodistrictCatalogLocation,
  formatResidentialComplexCatalogLocation,
  resolveCatalogCitySlug,
} from "@/features/catalog/slugs";
import {
  resolveMetroStationName,
  resolveCityDistrictName,
  resolveMicrodistrictPlace,
  resolveResidentialComplexPlace,
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
import { fetchCityCatalogContent } from "@/lib/city-catalog-seo-server";
import {
  fetchDistrictCatalogSeo,
  fetchMicrodistrictCatalogSeo,
  fetchResidentialComplexCatalogSeo,
} from "@/lib/place-catalog-seo-server";
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
import { JsonLdScript } from "@/lib/json-ld/json-ld-script";
import { buildPropertyJsonLd } from "@/lib/json-ld/property";
import { buildCatalogBreadcrumbJsonLd } from "@/lib/json-ld/catalog";
import { buildFaqPageJsonLd } from "@/lib/json-ld/faq";
import type { FaqItem } from "@/lib/json-ld/faq";
import { PageBreadcrumbs } from "@/components/PageBreadcrumbs";
import {
  buildCatalogBreadcrumbTrail,
  buildPropertyBreadcrumbTrail,
  buildBreadcrumbJsonLd,
} from "@/lib/breadcrumbs";

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
    const firstImage =
      property.images?.[0]?.thumbnailUrl || property.images?.[0]?.url;
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
  const microdistrictPlace = await resolveMicrodistrictPlace(parsed);
  const residentialComplexPlace = await resolveResidentialComplexPlace(parsed);
  const landmark = await resolveLandmark(parsed);
  const landmarkPhrase = resolveLandmarkPhrase(landmark);
  const microdistrictNamePrepositional = microdistrictPlace?.namePrepositional ?? undefined;
  const residentialComplexNamePrepositional = residentialComplexPlace?.namePrepositional ?? undefined;
  const h1Title = buildPageTitle(
    parsed,
    undefined,
    metroStationName,
    cityDistrictName,
    landmarkPhrase,
    microdistrictNamePrepositional,
    residentialComplexNamePrepositional,
  );
  const metaTitle = buildCatalogMetaTitle(
    parsed,
    metroStationName,
    cityDistrictName,
    landmarkPhrase,
    microdistrictNamePrepositional,
    residentialComplexNamePrepositional,
  );
  const metaDescription = buildCatalogMetaDescription(
    parsed,
    metroStationName,
    cityDistrictName,
    landmarkPhrase,
    microdistrictNamePrepositional,
    residentialComplexNamePrepositional,
  );

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

    const propertyBreadcrumbs = buildPropertyBreadcrumbTrail(property);

    return (
      <>
        <JsonLdScript data={buildPropertyJsonLd(property)} />
        <JsonLdScript data={buildBreadcrumbJsonLd(propertyBreadcrumbs, canonicalPath)} />
        <PropertyDetailClient id={numericPropertyId} initialProperty={property}>
          <PageBreadcrumbs items={propertyBreadcrumbs} hideCurrentOnMobile />
        </PropertyDetailClient>
      </>
    );
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
      <>
        <link
          rel="preload"
          href="/hero-apartment.webp"
          as="image"
          type="image/webp"
          fetchPriority="high"
        />
        <HomePage
          featuredInitial={featuredInitial ?? undefined}
          articles={articles}
          cityApartmentCounts={cityApartmentCounts}
          regionHouseCounts={regionHouseCounts}
          features={<FeaturesSection />}
        />
      </>
    );
  }

  const metroStationName = await resolveMetroStationName(parsed.metroStationSlug);
  const cityDistrictName = await resolveCityDistrictName(parsed);
  const microdistrictPlace = await resolveMicrodistrictPlace(parsed);
  const residentialComplexPlace = await resolveResidentialComplexPlace(parsed);
  const landmark = await resolveLandmark(parsed);
  const landmarkPhrase = resolveLandmarkPhrase(landmark);
  const microdistrictNamePrepositional = microdistrictPlace?.namePrepositional ?? undefined;
  const residentialComplexNamePrepositional = residentialComplexPlace?.namePrepositional ?? undefined;
  const title = buildPageTitle(
    parsed,
    undefined,
    metroStationName,
    cityDistrictName,
    landmarkPhrase,
    microdistrictNamePrepositional,
    residentialComplexNamePrepositional,
  );

  const { page: pageParam } = await searchParams;
  const pageFromQuery = Number(pageParam ?? "1");
  const validPage =
    Number.isFinite(pageFromQuery) && pageFromQuery > 0 ? Math.floor(pageFromQuery) : 1;
  const isFirstPage = validPage <= 1;

  let citySeoFooter: { heading: string; html: string; faq?: FaqItem[] } | null = null;
  let placeSeoFooter: { heading: string; html: string; faq?: FaqItem[] } | null = null;
  let cityFaqJsonLd: Record<string, unknown> | null = null;
  let placeFaqJsonLd: Record<string, unknown> | null = null;
  const catalogCitySlug = resolveCatalogCitySlug(parsed);

  if (isFirstPage && isBaseCityApartmentCatalogPage(parsed)) {
    const cityCatalogContent = await fetchCityCatalogContent(catalogCitySlug);
    const rawSeoText = cityCatalogContent?.catalogSeoText?.trim() ?? null;
    const faqItems = (cityCatalogContent?.faq ?? []).filter(
      (item): item is FaqItem =>
        Boolean(item?.question?.trim()) && Boolean(item?.answer?.trim()),
    );
    const sanitizedHtml = rawSeoText ? sanitizeArticleHtml(rawSeoText) : null;

    if (sanitizedHtml || faqItems.length > 0) {
      citySeoFooter = {
        heading: buildCatalogCitySeoHeading(catalogCitySlug),
        html: sanitizedHtml ?? "",
        faq: faqItems.length > 0 ? faqItems : undefined,
      };
    }

    if (faqItems.length > 0) {
      cityFaqJsonLd = buildFaqPageJsonLd(faqItems);
    }
  }

  if (isFirstPage && (parsed.cityDistrictSlug || parsed.microdistrictSlug || parsed.residentialComplexSlug)) {
    const placeDetail = parsed.cityDistrictSlug
      ? await fetchDistrictCatalogSeo(catalogCitySlug, parsed.cityDistrictSlug)
      : parsed.microdistrictSlug
        ? await fetchMicrodistrictCatalogSeo(catalogCitySlug, parsed.microdistrictSlug)
        : parsed.residentialComplexSlug
          ? await fetchResidentialComplexCatalogSeo(catalogCitySlug, parsed.residentialComplexSlug)
          : null;

    if (placeDetail) {
      const namePrepositional = placeDetail.namePrepositional?.trim();
      const headingLocation = parsed.residentialComplexSlug && namePrepositional
        ? formatResidentialComplexCatalogLocation(namePrepositional)
        : parsed.microdistrictSlug && namePrepositional
          ? formatMicrodistrictCatalogLocation(namePrepositional, catalogCitySlug)
          : parsed.cityDistrictSlug && namePrepositional
            ? `${namePrepositional.startsWith("в ") || namePrepositional.startsWith("во ") ? namePrepositional : `в ${namePrepositional}`}`
            : parsed.cityDistrictSlug && cityDistrictName
              ? formatCityDistrictCatalogLocation(cityDistrictName, catalogCitySlug)
              : placeDetail.name;

      const sanitizedHtml = placeDetail.catalogSeoText
        ? sanitizeArticleHtml(placeDetail.catalogSeoText)
        : null;
      const faqItems = (placeDetail.faq ?? []).filter(
        (item): item is FaqItem =>
          Boolean(item?.question?.trim()) && Boolean(item?.answer?.trim()),
      );

      if (sanitizedHtml || faqItems.length > 0) {
        placeSeoFooter = {
          heading: `Аренда жилья посуточно ${headingLocation}`,
          html: sanitizedHtml ?? "",
          faq: faqItems.length > 0 ? faqItems : undefined,
        };
      }

      if (faqItems.length > 0) {
        placeFaqJsonLd = buildFaqPageJsonLd(faqItems);
      }
    }
  }

  let landmarkFooter = null;
  if (isFirstPage && landmark) {
    const citySlug = catalogCitySlug;
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

  const breadcrumbNames = {
    metroStationName,
    cityDistrictName,
    microdistrictName: microdistrictPlace?.name,
    residentialComplexName: residentialComplexPlace?.name,
    landmarkPhrase,
    landmarkName: landmark?.name,
  };
  const catalogBreadcrumbs = buildCatalogBreadcrumbTrail(parsed, breadcrumbNames);

  return (
    <>
      {isFirstPage ? (
        <JsonLdScript data={buildCatalogBreadcrumbJsonLd(parsed, breadcrumbNames)} />
      ) : null}
      {isFirstPage && cityFaqJsonLd ? <JsonLdScript data={cityFaqJsonLd} /> : null}
      {isFirstPage && placeFaqJsonLd ? <JsonLdScript data={placeFaqJsonLd} /> : null}
      <CatalogPage
        parsed={parsed}
        title={title}
        landmark={landmark}
        landmarkFooter={landmarkFooter}
        citySeoFooter={citySeoFooter}
        placeSeoFooter={placeSeoFooter}
      >
        <PageBreadcrumbs items={catalogBreadcrumbs} />
      </CatalogPage>
    </>
  );
}
