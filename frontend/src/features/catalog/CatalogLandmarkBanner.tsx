import { MapPin } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { resolveLandmarkCategoryLabel } from "@/features/landmarks/categories";
import { resolveLandmarkImageUrl } from "@/features/landmarks/image";
import type { Landmark } from "@/features/landmarks/types";
import { resolveCatalogCityNominative } from "@/features/catalog/slugs";

type CatalogLandmarkBannerProps = {
  landmark: Landmark;
  citySlug: string;
  nearbyCount?: number | null;
  isLoading?: boolean;
};

function resolveCityLabel(citySlug: string): string {
  return resolveCatalogCityNominative(citySlug);
}

function pluralApartments(count: number): string {
  const mod10 = count % 10;
  const mod100 = count % 100;
  if (mod10 === 1 && mod100 !== 11) return "квартира";
  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return "квартиры";
  return "квартир";
}

/** Full-width hero banner for landmark catalog pages (design: AttractionCatalogPage). */
export default function CatalogLandmarkBanner({
  landmark,
  citySlug,
  nearbyCount,
  isLoading = false,
}: CatalogLandmarkBannerProps) {
  const imageUrl = resolveLandmarkImageUrl(landmark.imageUrl);
  const categoryLabel = resolveLandmarkCategoryLabel(landmark.category);
  const cityLabel = resolveCityLabel(citySlug);

  let subtitle = landmark.shortDescription;
  if (!isLoading && nearbyCount != null) {
    subtitle = `${nearbyCount} ${pluralApartments(nearbyCount)} рядом`;
  }

  return (
    <section className="relative h-[280px] md:h-[380px] w-full overflow-hidden">
      {imageUrl ? (
        // eslint-disable-next-line @next/next/no-img-element
        <img
          src={imageUrl}
          alt={landmark.name}
          className="absolute inset-0 h-full w-full object-cover"
          loading="eager"
        />
      ) : (
        <div className="absolute inset-0 bg-muted" />
      )}
      <div className="absolute inset-0 bg-gradient-to-t from-foreground/85 via-foreground/45 to-foreground/15" />
      <div className="container relative mx-auto h-full flex flex-col justify-end px-4 pb-8">
        <div className="mb-3 flex flex-wrap items-center gap-2">
          {categoryLabel ? (
            <Badge className="border-background/30 bg-background/20 text-background backdrop-blur">
              {categoryLabel}
            </Badge>
          ) : null}
          <span className="flex items-center gap-1 text-sm text-background/90">
            <MapPin className="h-4 w-4" />
            {cityLabel}
          </span>
        </div>
        <h1 className="font-display max-w-3xl text-2xl font-bold text-background md:text-4xl">
          Снять квартиру на сутки возле «{landmark.nameGenitive}»
        </h1>
        {subtitle ? (
          <p className="mt-2 max-w-2xl text-sm text-background/85 md:text-base">{subtitle}</p>
        ) : null}
      </div>
    </section>
  );
}
