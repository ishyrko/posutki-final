import Link from "next/link";
import { MapPin } from "lucide-react";
import { buildLandmarkCatalogUrl } from "@/features/catalog/slugs";
import { formatLandmarkDistance } from "@/features/landmarks/distance";
import { resolveLandmarkImageUrl } from "@/features/landmarks/image";
import type { PropertyNearbyLandmark } from "@/features/landmarks/types";

type PropertyNearbyLandmarksProps = {
  citySlug: string;
  landmarks: PropertyNearbyLandmark[];
};

export default function PropertyNearbyLandmarks({
  citySlug,
  landmarks,
}: PropertyNearbyLandmarksProps) {
  if (landmarks.length === 0) {
    return null;
  }

  return (
    <section>
      <h2 className="mb-4 text-xl font-bold text-foreground">Достопримечательности рядом</h2>
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {landmarks.map((landmark) => {
          const imageUrl = resolveLandmarkImageUrl(landmark.imageUrl);
          const href = buildLandmarkCatalogUrl(citySlug, landmark.slug);

          return (
            <Link
              key={landmark.id}
              href={href}
              className="group overflow-hidden rounded-2xl border border-border bg-card transition-shadow hover:shadow-card-hover"
            >
              <div className="aspect-[16/10] overflow-hidden bg-muted">
                {imageUrl ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img
                    src={imageUrl}
                    alt={landmark.name}
                    width={1600}
                    height={900}
                    loading="lazy"
                    className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                  />
                ) : null}
              </div>
              <div className="p-4">
                <h3 className="font-semibold text-foreground transition-colors group-hover:text-primary">
                  {landmark.name}
                </h3>
                <p className="mt-1 flex items-center gap-1 text-xs text-muted-foreground">
                  <MapPin className="h-3.5 w-3.5 shrink-0" />
                  {formatLandmarkDistance(landmark.distanceKm)}
                </p>
              </div>
            </Link>
          );
        })}
      </div>
    </section>
  );
}
