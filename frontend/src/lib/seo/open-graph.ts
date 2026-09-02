import type { Metadata } from "next";
import { getSiteOrigin } from "@/lib/site-url";

export const SITE_NAME = "Posutki.by";
export const DEFAULT_OG_IMAGE = "/og/default.webp";
export const DEFAULT_OG_IMAGE_WIDTH = 1200;
export const DEFAULT_OG_IMAGE_HEIGHT = 630;
export const OG_LOCALE = "ru_RU";

export type OgImageInput = {
  url: string;
  width?: number;
  height?: number;
  alt?: string;
};

export type OpenGraphMetaInput = {
  title: string;
  description: string;
  path: string;
  images?: OgImageInput[];
  type?: "website" | "article";
};

function normalizePath(path: string): string {
  if (!path.startsWith("/")) {
    return `/${path}`;
  }
  return path.endsWith("/") ? path : `${path}/`;
}

function resolveOgImages(images?: OgImageInput[]) {
  if (images?.length) {
    return images.map((image) => ({
      url: image.url,
      ...(image.width ? { width: image.width } : {}),
      ...(image.height ? { height: image.height } : {}),
      ...(image.alt ? { alt: image.alt } : {}),
    }));
  }

  return [
    {
      url: DEFAULT_OG_IMAGE,
      width: DEFAULT_OG_IMAGE_WIDTH,
      height: DEFAULT_OG_IMAGE_HEIGHT,
      alt: SITE_NAME,
    },
  ];
}

export function buildOpenGraphMeta(
  input: OpenGraphMetaInput,
): Pick<Metadata, "openGraph" | "twitter"> {
  const path = normalizePath(input.path);
  const ogImages = resolveOgImages(input.images);
  const twitterImageUrls = ogImages.map((image) => image.url);

  return {
    openGraph: {
      title: input.title,
      description: input.description,
      url: `${getSiteOrigin()}${path}`,
      siteName: SITE_NAME,
      locale: OG_LOCALE,
      type: input.type ?? "website",
      images: ogImages,
    },
    twitter: {
      card: "summary_large_image",
      title: input.title,
      description: input.description,
      images: twitterImageUrls,
    },
  };
}

export function buildPageMetadata(input: OpenGraphMetaInput): Metadata {
  const path = normalizePath(input.path);

  return {
    title: input.title,
    description: input.description,
    alternates: {
      canonical: path,
    },
    ...buildOpenGraphMeta(input),
  };
}
