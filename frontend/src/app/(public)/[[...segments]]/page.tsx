import { cache } from "react";
import { notFound, permanentRedirect } from "next/navigation";
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
  isRoomCatalogPage,
  isRoomSeoBucket,
  buildCatalogCitySeoHeading,
  buildCatalogCityFaqHeading,
  buildCatalogApartmentFaqHeading,
  buildCatalogRoomSeoHeading,
  buildCatalogRoomFaqHeading,
  formatCityDistrictCatalogLocation,
  formatMicrodistrictCatalogLocation,
  formatResidentialComplexCatalogLocation,
  resolveCatalogCitySlug,
} from "@/features/catalog/slugs";
import {
  buildFourPlusRoomCatalogRedirectPath,
  buildRoomLandingRedirectPath,
  isDeprecatedFourPlusRoomCatalogPage,
} from "@/features/catalog/catalog-rooms-filter";
import {
  resolveMetroStationName,
  resolveCityDistrictName,
  resolveMicrodistrictPlace,
  resolveResidentialComplexPlace,
  resolveLandmark,
  resolveLandmarkPhrase,
  validatePublicSegments,
} from "@/features/catalog/validate-segments-server";
import { resolveLandmarkImageUrl } from "@/features/landmarks/image";
import { formatAddress, Property } from "@/features/properties/types";
import CatalogPage from "@/features/catalog/CatalogPage";
import HomePage from "@/features/home/HomePage";
import FeaturesSection from "@/components/FeaturesSection";
import { fetchApi, fetchPublicApiNullable } from "@/lib/server-api";
import { fetchFeaturedPropertiesForHome } from "@/lib/featured-properties-server";
import { fetchCityApartmentCountsForHome } from "@/lib/city-apartment-counts-server";
import { fetchApartmentCatalogSlugSets, ensureApartmentCatalogSlugsConfigured } from "@/lib/apartment-catalog-slugs-server";
import { CatalogSlugProviderFromSets } from "@/components/CatalogSlugProviderFromSets";
import { fetchCityCatalogContent } from "@/lib/city-catalog-seo-server";
import {
  fetchDistrictCatalogSeo,
  fetchMicrodistrictCatalogSeo,
  fetchResidentialComplexCatalogSeo,
} from "@/lib/place-catalog-seo-server";
import { fetchRoomCatalogSeo } from "@/lib/room-catalog-seo-server";
import { fetchRegionHouseCountsForHome } from "@/lib/region-house-counts-server";
import { fetchRecentArticlesForHome } from "@/lib/articles-server";
import { HEADER_REGION_MINSK_SLUG } from "@/lib/region-header";
import { DEFAULT_EXCHANGE_RATES_FALLBACK, formatPropertyPrices } from "@/features/properties/price-display";
import {
  buildApartmentPropertyMetaDescription,
  buildApartmentPropertyMetaTitle,
} from "@/features/properties/property-meta-title";
import { buildOpenGraphMeta } from "@/lib/seo/open-graph";
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
  searchParams: Promise<{ page?: string; rooms?: string; guests?: string; amenity?: string }>;
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

function redirectDeprecatedFourPlusRoomCatalog(segments: string[] | undefined): void {
  const parsed = parseSegments(segments ?? []);
  if (!isDeprecatedFourPlusRoomCatalogPage(parsed)) {
    return;
  }

  permanentRedirect(buildFourPlusRoomCatalogRedirectPath(parsed, new URLSearchParams()));
}

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { segments } = await params;

  // Before any parseSegments / propertyUrl* — metadata and page can run in parallel.
  if (segments?.length) {
    await ensureApartmentCatalogSlugsConfigured();
  }

  redirectDeprecatedFourPlusRoomCatalog(segments);

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
      ...buildOpenGraphMeta({
        title: metaTitle,
        description: metaDescription,
        path: canonicalPath,
        images: firstImage ? [{ url: firstImage }] : undefined,
        type: "website",
      }),
    };
  }

  const parsed = parseSegments(segments);

  if (!isCatalogRoute(parsed)) {
    const homeTitle =
      "Квартиры и дома на сутки в Беларуси - посуточная аренда в Минске и других городах";
    const homeDescription =
      "Снимайте квартиры и дома на сутки в Беларуси напрямую от владельцев на Posutki.by. Минск, Гродно, Брест, Витебск, Гомель, Могилёв — актуальные объявления, удобный поиск по городу, типу жилья и количеству гостей.";

    return {
      title: homeTitle,
      description: homeDescription,
      alternates: {
        canonical: "/",
      },
      ...buildOpenGraphMeta({
        title: homeTitle,
        description: homeDescription,
        path: "/",
      }),
    };
  }

  const metroStationName = await resolveMetroStationName(parsed.metroStationSlug);
  const cityDistrictName = await resolveCityDistrictName(parsed);
  const microdistrictPlace = await resolveMicrodistrictPlace(parsed);
  const residentialComplexPlace = await resolveResidentialComplexPlace(parsed);
  const landmark = await resolveLandmark(parsed);
  const landmarkPhrase = resolveLandmarkPhrase(landmark);
  const microdistrictName = microdistrictPlace?.name ?? undefined;
  const residentialComplexNamePrepositional = residentialComplexPlace?.namePrepositional ?? undefined;
  const h1Title = buildPageTitle(
    parsed,
    undefined,
    metroStationName,
    cityDistrictName,
    landmarkPhrase,
    microdistrictName,
    residentialComplexNamePrepositional,
  );
  const metaTitle = buildCatalogMetaTitle(
    parsed,
    metroStationName,
    cityDistrictName,
    landmarkPhrase,
    microdistrictName,
    residentialComplexNamePrepositional,
  );
  const metaDescription = buildCatalogMetaDescription(
    parsed,
    metroStationName,
    cityDistrictName,
    landmarkPhrase,
    microdistrictName,
    residentialComplexNamePrepositional,
  );

  const catalogPath = buildCatalogCanonicalPath(parsed);
  const catalogTitle = metaTitle ?? `${h1Title} | Посутки.by`;
  const catalogDescription =
    metaDescription ??
    `Каталог посуточной аренды: ${h1Title.toLowerCase()}. Актуальные объявления с ценами и фото.`;
  const landmarkImageUrl = landmark ? resolveLandmarkImageUrl(landmark.imageUrl) : null;

  return {
    title: catalogTitle,
    description: catalogDescription,
    alternates: {
      canonical: catalogPath,
    },
    ...buildOpenGraphMeta({
      title: catalogTitle,
      description: catalogDescription,
      path: catalogPath,
      images: landmarkImageUrl
        ? [{ url: landmarkImageUrl, alt: landmark?.name }]
        : undefined,
    }),
  };
}

export default async function SegmentsPage({ params, searchParams }: PageProps) {
  const { segments } = await params;

  // Must run before redirectDeprecatedFourPlusRoomCatalog → parseSegments.
  // Do not rely on generateMetadata: Next may render the page in parallel.
  if (segments?.length) {
    await ensureApartmentCatalogSlugsConfigured();
  }

  redirectDeprecatedFourPlusRoomCatalog(segments);

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
    const slugSets = await fetchApartmentCatalogSlugSets();

    return (
      <CatalogSlugProviderFromSets sets={slugSets}>
        <JsonLdScript data={buildPropertyJsonLd(property)} />
        <JsonLdScript data={buildBreadcrumbJsonLd(propertyBreadcrumbs, canonicalPath)} />
        <PropertyDetailClient id={numericPropertyId} initialProperty={property}>
          <PageBreadcrumbs items={propertyBreadcrumbs} hideCurrentOnMobile />
        </PropertyDetailClient>
      </CatalogSlugProviderFromSets>
    );
  }

  const parsed = parseSegments(segments);

  if (!isCatalogRoute(parsed)) {
    const featuredRegionSlug = HEADER_REGION_MINSK_SLUG;
    const [featuredInitial, articles, slugSets, cityApartmentCounts, regionHouseCounts] = await Promise.all([
      fetchFeaturedPropertiesForHome(featuredRegionSlug),
      fetchRecentArticlesForHome(),
      fetchApartmentCatalogSlugSets(),
      fetchCityApartmentCountsForHome(),
      fetchRegionHouseCountsForHome(),
    ]);

    return (
      <CatalogSlugProviderFromSets sets={slugSets}>
        <HomePage
          featuredInitial={featuredInitial ?? undefined}
          articles={articles}
          apartmentCatalogCities={slugSets.cities}
          cityApartmentCounts={cityApartmentCounts}
          regionHouseCounts={regionHouseCounts}
          features={<FeaturesSection />}
        />
      </CatalogSlugProviderFromSets>
    );
  }

  const metroStationName = await resolveMetroStationName(parsed.metroStationSlug);
  const cityDistrictName = await resolveCityDistrictName(parsed);
  const microdistrictPlace = await resolveMicrodistrictPlace(parsed);
  const residentialComplexPlace = await resolveResidentialComplexPlace(parsed);
  const landmark = await resolveLandmark(parsed);
  const landmarkPhrase = resolveLandmarkPhrase(landmark);
  const microdistrictName = microdistrictPlace?.name ?? undefined;
  const residentialComplexNamePrepositional = residentialComplexPlace?.namePrepositional ?? undefined;
  const title = buildPageTitle(
    parsed,
    undefined,
    metroStationName,
    cityDistrictName,
    landmarkPhrase,
    microdistrictName,
    residentialComplexNamePrepositional,
  );

  const resolvedSearchParams = await searchParams;
  const redirectParams = new URLSearchParams();
  for (const [key, value] of Object.entries(resolvedSearchParams)) {
    if (value != null && value !== "") {
      redirectParams.set(key, value);
    }
  }
  const roomRedirectPath = buildRoomLandingRedirectPath(
    parsed,
    resolvedSearchParams.rooms ?? null,
    redirectParams,
  );
  if (roomRedirectPath) {
    permanentRedirect(roomRedirectPath);
  }

  const { page: pageParam } = resolvedSearchParams;
  const pageFromQuery = Number(pageParam ?? "1");
  const validPage =
    Number.isFinite(pageFromQuery) && pageFromQuery > 0 ? Math.floor(pageFromQuery) : 1;
  const isFirstPage = validPage <= 1;

  let citySeoFooter: { heading: string; html: string; faq?: FaqItem[]; faqTitle?: string } | null = null;
  let placeSeoFooter: { heading: string; html: string; faq?: FaqItem[]; faqTitle?: string } | null = null;
  let roomSeoFooter: { heading: string; html: string; faq?: FaqItem[]; faqTitle?: string } | null = null;
  let cityFaqJsonLd: Record<string, unknown> | null = null;
  let placeFaqJsonLd: Record<string, unknown> | null = null;
  let roomFaqJsonLd: Record<string, unknown> | null = null;
  const catalogCitySlug = resolveCatalogCitySlug(parsed);

  if (isFirstPage && isBaseCityApartmentCatalogPage(parsed)) {
    const cityCatalogContent = await fetchCityCatalogContent(catalogCitySlug);
    if (cityCatalogContent?.catalogSeoVisible) {
      const rawSeoText = cityCatalogContent.catalogSeoText?.trim() ?? null;
      const faqItems = (cityCatalogContent.faq ?? []).filter(
        (item): item is FaqItem =>
          Boolean(item?.question?.trim()) && Boolean(item?.answer?.trim()),
      );
      const sanitizedHtml = rawSeoText ? sanitizeArticleHtml(rawSeoText) : null;

      if (sanitizedHtml || faqItems.length > 0) {
        citySeoFooter = {
          heading: buildCatalogCitySeoHeading(catalogCitySlug),
          html: sanitizedHtml ?? "",
          faq: faqItems.length > 0 ? faqItems : undefined,
          faqTitle: faqItems.length > 0 ? buildCatalogCityFaqHeading(catalogCitySlug) : undefined,
        };
      }

      if (faqItems.length > 0) {
        cityFaqJsonLd = buildFaqPageJsonLd(faqItems);
      }
    }
  }

  if (isFirstPage && isRoomCatalogPage(parsed) && isRoomSeoBucket(parsed.roomsBucket)) {
    const roomsBucket = parsed.roomsBucket;
    const roomDetail = await fetchRoomCatalogSeo(catalogCitySlug, roomsBucket);
    if (roomDetail?.catalogSeoVisible) {
      const sanitizedHtml = roomDetail.catalogSeoText
        ? sanitizeArticleHtml(roomDetail.catalogSeoText)
        : null;
      const faqItems = (roomDetail.faq ?? []).filter(
        (item): item is FaqItem =>
          Boolean(item?.question?.trim()) && Boolean(item?.answer?.trim()),
      );

      if (sanitizedHtml || faqItems.length > 0) {
        roomSeoFooter = {
          heading: buildCatalogRoomSeoHeading(catalogCitySlug, roomsBucket),
          html: sanitizedHtml ?? "",
          faq: faqItems.length > 0 ? faqItems : undefined,
          faqTitle:
            faqItems.length > 0
              ? buildCatalogRoomFaqHeading(catalogCitySlug, roomsBucket)
              : undefined,
        };
      }

      if (faqItems.length > 0) {
        roomFaqJsonLd = buildFaqPageJsonLd(faqItems);
      }
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

    if (placeDetail?.catalogSeoVisible) {
      const namePrepositional = placeDetail.namePrepositional?.trim();
      const headingLocation = parsed.residentialComplexSlug && namePrepositional
        ? formatResidentialComplexCatalogLocation(namePrepositional, catalogCitySlug)
        : parsed.microdistrictSlug && placeDetail.name
          ? formatMicrodistrictCatalogLocation(placeDetail.name, catalogCitySlug)
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
          heading: `Снять квартиру ${headingLocation} посуточно`,
          html: sanitizedHtml ?? "",
          faq: faqItems.length > 0 ? faqItems : undefined,
          faqTitle:
            faqItems.length > 0 ? buildCatalogApartmentFaqHeading(headingLocation) : undefined,
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
  const slugSets = await fetchApartmentCatalogSlugSets();

  return (
    <CatalogSlugProviderFromSets sets={slugSets}>
      {isFirstPage ? (
        <JsonLdScript data={buildCatalogBreadcrumbJsonLd(parsed, breadcrumbNames)} />
      ) : null}
      {isFirstPage && cityFaqJsonLd ? <JsonLdScript data={cityFaqJsonLd} /> : null}
      {isFirstPage && placeFaqJsonLd ? <JsonLdScript data={placeFaqJsonLd} /> : null}
      {isFirstPage && roomFaqJsonLd ? <JsonLdScript data={roomFaqJsonLd} /> : null}
      <CatalogPage
        parsed={parsed}
        title={title}
        landmark={landmark}
        landmarkFooter={landmarkFooter}
        citySeoFooter={citySeoFooter}
        placeSeoFooter={placeSeoFooter}
        roomSeoFooter={roomSeoFooter}
      >
        <PageBreadcrumbs items={catalogBreadcrumbs} />
      </CatalogPage>
    </CatalogSlugProviderFromSets>
  );
}
