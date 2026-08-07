import {
  buildPropertyUrlFromRegionName,
  PROPERTY_TYPE_VALUE_TO_SLUG,
} from "@/features/catalog/slugs";
import { DEFAULT_EXCHANGE_RATES_FALLBACK } from "@/features/properties/price-display";
import { formatAddress, type Property } from "@/features/properties/types";
import { getSiteOrigin } from "@/lib/site-url";

function toAbsoluteUrl(path: string): string {
  const origin = getSiteOrigin();
  return path.startsWith("http") ? path : `${origin}${path.startsWith("/") ? path : `/${path}`}`;
}

function propertyTypeLabel(type: string): string {
  if (type === "house") return "House";
  if (type === "apartment") return "Apartment";
  return "Accommodation";
}

export function buildPropertyJsonLd(property: Property): Record<string, unknown> {
  const canonicalPath = buildPropertyUrlFromRegionName(
    property.type,
    property.id,
    property.address.regionName,
    property.address.citySlug,
  );
  const url = toAbsoluteUrl(canonicalPath);
  const addressText = formatAddress(property.address);
  const priceAmount =
    property.priceByn ??
    (property.price.currency === "BYN"
      ? property.price.amount
      : Math.round(
          property.price.amount *
            (DEFAULT_EXCHANGE_RATES_FALLBACK[property.price.currency as "USD" | "RUB"] ?? 1),
        ));

  const images = property.images.map((image) => toAbsoluteUrl(image.url)).filter(Boolean);

  const jsonLd: Record<string, unknown> = {
    "@context": "https://schema.org",
    "@type": "RealEstateListing",
    "@id": url,
    name: property.title,
    description: property.description?.trim() || addressText,
    url,
    datePosted: property.publishedAt ?? property.createdAt,
    image: images.length > 0 ? images : undefined,
    offers: {
      "@type": "Offer",
      price: priceAmount,
      priceCurrency: "BYN",
      availability: "https://schema.org/InStock",
      url,
    },
    address: {
      "@type": "PostalAddress",
      streetAddress: addressText,
      addressLocality: property.address.cityName,
      addressCountry: "BY",
    },
  };

  if (property.coordinates) {
    jsonLd.geo = {
      "@type": "GeoCoordinates",
      latitude: property.coordinates.latitude,
      longitude: property.coordinates.longitude,
    };
  }

  if (property.specifications.rooms) {
    jsonLd.numberOfRooms = property.specifications.rooms;
  }

  const typeSlug = PROPERTY_TYPE_VALUE_TO_SLUG[property.type];
  if (typeSlug) {
    jsonLd.additionalType = `https://posutki.by/${typeSlug}/`;
  }

  jsonLd.category = propertyTypeLabel(property.type);

  return jsonLd;
}
