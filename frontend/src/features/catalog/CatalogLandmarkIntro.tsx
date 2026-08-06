import { resolveLandmarkImageUrl } from "@/features/landmarks/image";

type CatalogLandmarkIntroProps = {
  name: string;
  shortDescription?: string | null;
  imageUrl?: string | null;
};

/** SSR-only intro block above landmark catalog listings. */
export default function CatalogLandmarkIntro({
  name,
  shortDescription,
  imageUrl,
}: CatalogLandmarkIntroProps) {
  const resolvedImageUrl = resolveLandmarkImageUrl(imageUrl);

  if (!shortDescription && !resolvedImageUrl) {
    return null;
  }

  return (
    <section className="mb-6 rounded-xl border border-border bg-card overflow-hidden">
      {resolvedImageUrl ? (
        <div className="aspect-[21/9] w-full overflow-hidden bg-muted">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img
            src={resolvedImageUrl}
            alt={name}
            className="h-full w-full object-cover"
            loading="eager"
          />
        </div>
      ) : null}
      {shortDescription ? (
        <div className="px-4 py-4 md:px-6">
          <p className="text-sm md:text-base text-muted-foreground leading-relaxed">
            {shortDescription}
          </p>
        </div>
      ) : null}
    </section>
  );
}
